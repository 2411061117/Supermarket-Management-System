<?php
namespace App\Models;

use App\Core\Model;

class Supplier extends Model
{
    protected string $table = 'suppliers';

    public function countFiltered(string $q, string $status): int
    {
        [$where, $params] = $this->filter($q, $status);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** $limit/$offset là int do Paginator tạo ra nên nối thẳng vào SQL là an toàn. */
    public function search(string $q, string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($q, $status);
        $stmt = $this->db->prepare(
            "SELECT s.*, (SELECT COUNT(*) FROM goods_receipts r WHERE r.supplier_id = s.id) AS receipt_count
               FROM suppliers s $where
              ORDER BY s.is_active DESC, s.name
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** [id => tên] các nhà cung cấp đang hợp tác (cho ô chọn khi lập phiếu nhập). */
    public function activeOptions(): array
    {
        return array_column($this->db->query('SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll(), 'name', 'id');
    }

    public function receiptCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM goods_receipts WHERE supplier_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    /** Điều kiện WHERE cho tìm kiếm (tên/người liên hệ/SĐT/email) và lọc trạng thái. */
    private function filter(string $q, string $status): array
    {
        $conds = [];
        $params = [];
        if ($q !== '') {
            $like = self::likePattern($q);
            $conds[] = "(name LIKE ? ESCAPE '!' OR contact LIKE ? ESCAPE '!' OR phone LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
            array_push($params, $like, $like, $like, $like);
        }
        if ($status === 'active' || $status === 'inactive') {
            $conds[] = 'is_active = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }
        return [$conds ? 'WHERE ' . implode(' AND ', $conds) : '', $params];
    }
}
