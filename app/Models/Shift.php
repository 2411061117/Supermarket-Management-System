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

    /** Tiền mặt dự kiến trong két = tiền đầu ca + tiền mặt thu từ các hóa đơn hoàn thành của ca (0 nếu chưa có POS). */
    public function expectedCash(array $shift): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(p.amount), 0)
               FROM payments p
               JOIN payment_methods m ON m.id = p.method_id AND m.code = 'cash'
               JOIN orders o ON o.id = p.order_id AND o.status = 'completed'
              WHERE o.shift_id = ?"
        );
        $stmt->execute([$shift['id']]);
        return (int) $shift['opening_cash'] + (int) $stmt->fetchColumn();
    }

    /**
     * Doanh thu của ca: số hóa đơn hoàn thành, tổng tiền, số hóa đơn đã hủy (không tính vào doanh thu)
     * và tổng thu theo từng phương thức thanh toán (chỉ hóa đơn hoàn thành).
     */
    public function summary(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(status = 'completed'), 0) AS orders_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) AS revenue,
                    COALESCE(SUM(status = 'cancelled'), 0) AS cancelled_count
               FROM orders WHERE shift_id = ?"
        );
        $stmt->execute([$shiftId]);
        $row = $stmt->fetch();

        $stmt = $this->db->prepare(
            "SELECT m.code, m.name, COUNT(DISTINCT o.id) AS orders_count, SUM(p.amount) AS amount
               FROM payments p
               JOIN payment_methods m ON m.id = p.method_id
               JOIN orders o ON o.id = p.order_id AND o.status = 'completed'
              WHERE o.shift_id = ?
              GROUP BY m.id, m.code, m.name
              ORDER BY m.id"
        );
        $stmt->execute([$shiftId]);

        return [
            'orders_count'    => (int) $row['orders_count'],
            'revenue'         => (int) $row['revenue'],
            'cancelled_count' => (int) $row['cancelled_count'],
            'methods'         => $stmt->fetchAll(),
        ];
    }

    /** Hóa đơn của ca (mới nhất trước), kèm phương thức thanh toán, để hiển thị ở trang chi tiết ca. */
    public function orders(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id, o.code, o.total, o.status, o.created_at,
                    (SELECT GROUP_CONCAT(m.name ORDER BY m.id SEPARATOR ', ')
                       FROM payments p JOIN payment_methods m ON m.id = p.method_id
                      WHERE p.order_id = o.id) AS method_name
               FROM orders o WHERE o.shift_id = ?
              ORDER BY o.created_at DESC, o.id DESC"
        );
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll();
    }

    /** Ca kèm tên nhân viên (null nếu không có). Không lọc cửa hàng: controller tự kiểm tra. */
    public function findWithUser(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.full_name AS user_name, u.username,
                    TIMESTAMPDIFF(MINUTE, s.opened_at, COALESCE(s.closed_at, NOW())) AS minutes
               FROM shifts s JOIN users u ON u.id = s.user_id WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Đóng ca. Trả về tiền mặt dự kiến tại thời điểm đóng, hoặc null nếu ca không còn mở (đã đóng ở nơi khác).
     * Khóa dòng ca trong transaction: thanh toán/hủy hóa đơn cũng khóa dòng ca nên đóng ca phải đợi giao dịch
     * đang dở xong, và sau khi đóng không còn hóa đơn nào vào ca được nữa. Tiền dự kiến được tính và lưu
     * (expected_cash) trong cùng transaction; chênh lệch = closing_cash − expected_cash.
     */
    public function close(int $shiftId, int $closingCash): ?int
    {
        return Database::transaction(function () use ($shiftId, $closingCash) {
            $stmt = $this->db->prepare('SELECT * FROM shifts WHERE id = ? FOR UPDATE');
            $stmt->execute([$shiftId]);
            $shift = $stmt->fetch();
            if (!$shift || $shift['status'] !== 'open') {
                return null;
            }
            $expected = $this->expectedCash($shift);
            $this->db->prepare(
                "UPDATE shifts SET status = 'closed', closed_at = NOW(), closing_cash = ?, expected_cash = ? WHERE id = ?"
            )->execute([$closingCash, $expected, $shiftId]);
            return $expected;
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
