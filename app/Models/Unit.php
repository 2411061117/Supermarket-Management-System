<?php
namespace App\Models;

use App\Core\Model;

class Unit extends Model
{
    protected string $table = 'units';

    /** Danh sách đơn vị kèm số sản phẩm đang dùng. */
    public function withProductCount(): array
    {
        return $this->db->query(
            'SELECT u.id, u.name, COUNT(p.id) AS product_count
               FROM units u LEFT JOIN products p ON p.unit_id = u.id
              GROUP BY u.id, u.name
              ORDER BY u.name'
        )->fetchAll();
    }

    public function productCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE unit_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
}
