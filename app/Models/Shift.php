<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Ca làm việc của thu ngân. Mỗi người tối đa 1 ca đang mở (kiểm tra ở ứng dụng, có khóa dòng người dùng). */
class Shift extends Model
{
    protected string $table = 'shifts';

    /** Ca đang mở của nhân viên (null nếu chưa mở), kèm minutes_open = số phút đã mở (tính theo đồng hồ CSDL). */
    public function findOpenByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *, TIMESTAMPDIFF(MINUTE, opened_at, NOW()) AS minutes_open
               FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Mở ca mới. Trả về id ca, hoặc null nếu nhân viên đã có ca đang mở.
     * Khóa dòng người dùng trong transaction để 2 request đồng thời không tạo được 2 ca.
     */
    public function open(int $storeId, int $userId, int $openingCash, ?string $note): ?int
    {
        return Database::transaction(function () use ($storeId, $userId, $openingCash, $note) {
            $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            if ($this->findOpenByUser($userId) !== null) {
                return null;
            }
            return $this->create([
                'store_id'     => $storeId,
                'user_id'      => $userId,
                'opening_cash' => $openingCash,
                'status'       => 'open',
                'note'         => $note,
            ]);
        });
    }

    public function countFiltered(int $storeId, int $userId, string $status, string $from, string $to): int
    {
        [$where, $params] = $this->filter($storeId, $userId, $status, $from, $to);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM shifts s $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** $limit/$offset là int do Paginator tạo nên nối thẳng vào SQL là an toàn. */
    public function search(int $storeId, int $userId, string $status, string $from, string $to, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($storeId, $userId, $status, $from, $to);
        $stmt = $this->db->prepare(
            "SELECT s.*, u.full_name AS user_name, u.username,
                    TIMESTAMPDIFF(MINUTE, s.opened_at, COALESCE(s.closed_at, NOW())) AS minutes
               FROM shifts s JOIN users u ON u.id = s.user_id
               $where
              ORDER BY s.opened_at DESC, s.id DESC
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** $userId = 0: mọi nhân viên; $from/$to dạng Y-m-d theo ngày mở ca ('' = không giới hạn). */
    private function filter(int $storeId, int $userId, string $status, string $from, string $to): array
    {
        $conds = ['s.store_id = ?'];
        $params = [$storeId];
        if ($userId > 0) {
            $conds[] = 's.user_id = ?';
            $params[] = $userId;
        }
        if ($status === 'open' || $status === 'closed') {
            $conds[] = 's.status = ?';
            $params[] = $status;
        }
        if ($from !== '') {
            $conds[] = 's.opened_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $conds[] = 's.opened_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        return ['WHERE ' . implode(' AND ', $conds), $params];
    }
}
