<?php
namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    /** Danh sách loại hàng kèm số sản phẩm thuộc loại. */
    public function withProductCount(): array
    {
        return $this->db->query(
            'SELECT c.id, c.name, c.description, COUNT(p.id) AS product_count
               FROM categories c LEFT JOIN products p ON p.category_id = c.id
              GROUP BY c.id, c.name, c.description
              ORDER BY c.name'
        )->fetchAll();
    }

    public function productCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
}
