<?php
namespace App\Models;

use App\Core\Database;

/** Các truy vấn thống kê cho dashboard và báo cáo (/reports). Chỉ tính hóa đơn completed. */
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

    /** Sản phẩm đang bán có tồn bán được (chỉ tính lô còn hạn) <= mức tồn tối thiểu. */
    public function lowStockProducts(int $storeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.sku, p.name, p.min_stock, COALESCE(SUM(b.qty_remaining), 0) AS stock
               FROM products p
          LEFT JOIN product_batches b ON b.product_id = p.id AND b.store_id = ?
                      AND (b.expiry_date IS NULL OR b.expiry_date >= CURDATE())
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

    // ---------- Báo cáo theo khoảng ngày ($from/$to dạng Y-m-d, tính cả ngày $to) ----------

    /** [từ 00:00 ngày $from, trước 00:00 ngày sau $to): dùng được chỉ mục created_at. */
    private static function range(string $from, string $to): array
    {
        return [$from . ' 00:00:00', date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
    }

    /** Thẻ tổng: số hóa đơn, doanh thu, trung bình/hóa đơn, giá vốn và lợi nhuận gộp (= doanh thu − giá vốn theo lô đã bán). */
    public function summary(int $storeId, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue
               FROM orders WHERE store_id = ? AND status = 'completed' AND created_at >= ? AND created_at < ?"
        );
        $stmt->execute([$storeId, $a, $b]);
        $row = $stmt->fetch();

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(oi.qty * b.cost_price), 0)
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
               JOIN product_batches b ON b.id = oi.batch_id
              WHERE o.store_id = ? AND o.status = 'completed' AND o.created_at >= ? AND o.created_at < ?"
        );
        $stmt->execute([$storeId, $a, $b]);

        $count = (int) $row['orders_count'];
        $revenue = (int) $row['revenue'];
        $cost = (int) $stmt->fetchColumn();
        return [
            'orders_count' => $count,
            'revenue'      => $revenue,
            'average'      => $count > 0 ? (int) round($revenue / $count) : 0,
            'cost'         => $cost,
            'profit'       => $revenue - $cost,
        ];
    }

    /** Doanh thu theo ngày, đủ mọi ngày trong khoảng (ngày không bán có 0). Trả về [Y-m-d => [orders_count, revenue]]. */
    public function revenueByDay(int $storeId, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $stmt = Database::connection()->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS orders_count, SUM(total) AS revenue
               FROM orders WHERE store_id = ? AND status = 'completed' AND created_at >= ? AND created_at < ?
              GROUP BY DATE(created_at)"
        );
        $stmt->execute([$storeId, $a, $b]);
        $found = [];
        foreach ($stmt->fetchAll() as $r) {
            $found[$r['d']] = ['orders_count' => (int) $r['orders_count'], 'revenue' => (int) $r['revenue']];
        }
        $days = [];
        for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
            $d = date('Y-m-d', $t);
            $days[$d] = $found[$d] ?? ['orders_count' => 0, 'revenue' => 0];
        }
        return $days;
    }

    /** Tổng thu theo phương thức thanh toán (số tiền thực nhận ở bảng payments). */
    public function byPaymentMethod(int $storeId, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $stmt = Database::connection()->prepare(
            "SELECT m.name, COUNT(DISTINCT o.id) AS orders_count, SUM(p.amount) AS amount
               FROM payments p
               JOIN payment_methods m ON m.id = p.method_id
               JOIN orders o ON o.id = p.order_id
              WHERE o.store_id = ? AND o.status = 'completed' AND o.created_at >= ? AND o.created_at < ?
              GROUP BY m.id, m.name ORDER BY amount DESC"
        );
        $stmt->execute([$storeId, $a, $b]);
        return $stmt->fetchAll();
    }

    /** Doanh thu theo thu ngân (nhân viên lập hóa đơn). */
    public function byCashier(int $storeId, string $from, string $to): array
    {
        [$a, $b] = self::range($from, $to);
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(u.full_name, '(không rõ)') AS name, COUNT(*) AS orders_count, SUM(o.total) AS revenue
               FROM orders o LEFT JOIN users u ON u.id = o.user_id
              WHERE o.store_id = ? AND o.status = 'completed' AND o.created_at >= ? AND o.created_at < ?
              GROUP BY o.user_id, u.full_name ORDER BY revenue DESC"
        );
        $stmt->execute([$storeId, $a, $b]);
        return $stmt->fetchAll();
    }

    /**
     * Top sản phẩm bán chạy, gộp các dòng tách theo lô. $by = 'qty' (theo số lượng) hoặc 'revenue' (theo doanh thu).
     * Doanh thu sản phẩm = SL × đơn giá, chưa trừ giảm giá cấp hóa đơn.
     */
    public function topProducts(int $storeId, string $from, string $to, string $by, int $limit = 10): array
    {
        [$a, $b] = self::range($from, $to);
        $order = $by === 'revenue' ? 'revenue DESC, qty DESC' : 'qty DESC, revenue DESC';
        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.sku, p.name, SUM(oi.qty) AS qty, SUM(oi.qty * oi.unit_price) AS revenue
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
               JOIN product_batches b ON b.id = oi.batch_id
               JOIN products p ON p.id = b.product_id
              WHERE o.store_id = ? AND o.status = 'completed' AND o.created_at >= ? AND o.created_at < ?
              GROUP BY p.id, p.sku, p.name
              ORDER BY $order, p.id
              LIMIT " . (int) $limit
        );
        $stmt->execute([$storeId, $a, $b]);
        return $stmt->fetchAll();
    }
}
