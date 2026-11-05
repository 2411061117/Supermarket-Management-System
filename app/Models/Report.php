<?php
namespace App\Models;

use App\Core\Database;

/** Các truy vấn thống kê cho dashboard. */
class Report
{
    public function todayRevenue(int $storeId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM orders
              WHERE store_id = ? AND status = 'completed' AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute([$storeId]);
        return (float) $stmt->fetchColumn();
    }

    public function todayOrderCount(int $storeId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM orders
              WHERE store_id = ? AND status = 'completed' AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute([$storeId]);
        return (int) $stmt->fetchColumn();
    }

    /** Sản phẩm có tổng tồn (các lô) <= mức tồn tối thiểu. */
    public function lowStockProducts(int $storeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.sku, p.name, p.min_stock, COALESCE(SUM(b.qty_remaining), 0) AS stock
               FROM products p
          LEFT JOIN product_batches b ON b.product_id = p.id AND b.store_id = ?
              WHERE p.is_active = 1
           GROUP BY p.id, p.sku, p.name, p.min_stock
             HAVING stock <= p.min_stock
           ORDER BY stock'
        );
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    /** Lô còn hàng sắp hết hạn trong $days ngày (kể cả đã hết hạn). */
    public function expiringBatches(int $storeId, int $days = 7): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.batch_code, b.expiry_date, b.qty_remaining, p.name
               FROM product_batches b JOIN products p ON p.id = b.product_id
              WHERE b.store_id = ? AND b.qty_remaining > 0
                AND b.expiry_date IS NOT NULL
                AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
           ORDER BY b.expiry_date'
        );
        $stmt->execute([$storeId, $days]);
        return $stmt->fetchAll();
    }
}
