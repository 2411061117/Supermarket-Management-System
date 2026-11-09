<?php
namespace App\Models;

use App\Core\Model;

/**
 * Lô hàng: mỗi lần nhập tạo 1 lô; tồn kho của sản phẩm = tổng qty_remaining các lô CÒN HẠN
 * (HSD trống hoặc >= hôm nay; lô hết hạn không bán được nên không tính vào tồn).
 */
class Batch extends Model
{
    protected string $table = 'product_batches';

    /** Cảnh báo sắp hết hạn trong bao nhiêu ngày (khớp với dashboard). */
    public const WARN_DAYS = 7;

    /** Mã lô đã tồn tại cho sản phẩm này chưa (không phân biệt hoa/thường theo collation). */
    public function codeExists(int $productId, string $batchCode): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM product_batches WHERE product_id = ? AND batch_code = ? LIMIT 1');
        $stmt->execute([$productId, $batchCode]);
        return (bool) $stmt->fetchColumn();
    }

    public function countStock(int $storeId, string $q, int $categoryId, string $status): int
    {
        [$where, $params] = $this->stockFilter($q, $categoryId, $status);
        $stmt = $this->db->prepare('SELECT COUNT(*) ' . $this->stockFrom() . " $where");
        $stmt->execute([$storeId, ...$params]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tồn kho theo sản phẩm: stock (còn hạn), expiring_qty (còn hạn nhưng <= WARN_DAYS ngày nữa hết),
     * expired_qty (đã hết hạn còn nằm trong kho). $limit/$offset là int do Paginator tạo nên nối thẳng là an toàn.
     */
    public function stockSummary(int $storeId, string $q, int $categoryId, string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->stockFilter($q, $categoryId, $status);
        $stmt = $this->db->prepare(
            'SELECT p.id, p.sku, p.name, p.min_stock, p.is_active, c.name AS category_name, u.name AS unit_name,
                    COALESCE(s.stock, 0) AS stock, COALESCE(s.expiring_qty, 0) AS expiring_qty,
                    COALESCE(s.expired_qty, 0) AS expired_qty '
            . $this->stockFrom() . " $where ORDER BY p.is_active DESC, p.name LIMIT $limit OFFSET $offset"
        );
        $stmt->execute([$storeId, ...$params]);
        return $stmt->fetchAll();
    }

    /** Số sản phẩm theo từng cảnh báo: out (hết hàng), low (tồn <= tối thiểu, gồm hết hàng), expiring, expired. */
    public function alertCounts(int $storeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(COALESCE(s.stock, 0) = 0), 0) AS out_count,
                    COALESCE(SUM(COALESCE(s.stock, 0) <= p.min_stock), 0) AS low_count,
                    COALESCE(SUM(COALESCE(s.expiring_qty, 0) > 0), 0) AS expiring_count,
                    COALESCE(SUM(COALESCE(s.expired_qty, 0) > 0), 0) AS expired_count '
            . $this->stockFrom()
        );
        $stmt->execute([$storeId]);
        $r = $stmt->fetch();
        return ['out' => (int) $r['out_count'], 'low' => (int) $r['low_count'],
                'expiring' => (int) $r['expiring_count'], 'expired' => (int) $r['expired_count']];
    }

    /** Các lô của 1 sản phẩm theo thứ tự FEFO (HSD gần nhất trước, không HSD xếp cuối). */
    public function forProduct(int $productId, int $storeId, bool $includeEmpty = false): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, batch_code, expiry_date, cost_price, qty_received, qty_remaining, received_at
               FROM product_batches
              WHERE product_id = ? AND store_id = ?' . ($includeEmpty ? '' : ' AND qty_remaining > 0') . '
              ORDER BY expiry_date IS NULL, expiry_date, id'
        );
        $stmt->execute([$productId, $storeId]);
        return $stmt->fetchAll();
    }

    /**
     * FROM + JOIN dùng chung (tham số đầu tiên là store_id). Hiện sản phẩm đang bán, và cả sản phẩm
     * đã ngừng bán nếu còn hàng trong kho (để vẫn thấy và xử lý).
     */
    private function stockFrom(): string
    {
        $days = self::WARN_DAYS;
        return "FROM products p
                JOIN categories c ON c.id = p.category_id
                JOIN units u ON u.id = p.unit_id
           LEFT JOIN (
                SELECT product_id,
                       SUM(CASE WHEN expiry_date IS NULL OR expiry_date >= CURDATE() THEN qty_remaining ELSE 0 END) AS stock,
                       SUM(CASE WHEN expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL $days DAY)
                                THEN qty_remaining ELSE 0 END) AS expiring_qty,
                       SUM(CASE WHEN expiry_date < CURDATE() THEN qty_remaining ELSE 0 END) AS expired_qty
                  FROM product_batches WHERE store_id = ? GROUP BY product_id
                ) s ON s.product_id = p.id";
    }

    /** status: out | low | ok | expiring | expired ('' = tất cả). */
    private function stockFilter(string $q, int $categoryId, string $status): array
    {
        $conds = ['(p.is_active = 1 OR COALESCE(s.stock, 0) + COALESCE(s.expired_qty, 0) > 0)'];
        $params = [];
        if ($q !== '') {
            $like = self::likePattern($q);
            $conds[] = "(p.name LIKE ? ESCAPE '!' OR p.sku LIKE ? ESCAPE '!' OR p.barcode LIKE ? ESCAPE '!')";
            array_push($params, $like, $like, $like);
        }
        if ($categoryId > 0) {
            $conds[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        $byStatus = [
            'out'      => 'COALESCE(s.stock, 0) = 0',
            'low'      => 'COALESCE(s.stock, 0) <= p.min_stock',
            'ok'       => 'COALESCE(s.stock, 0) > p.min_stock',
            'expiring' => 'COALESCE(s.expiring_qty, 0) > 0',
            'expired'  => 'COALESCE(s.expired_qty, 0) > 0',
        ];
        if (isset($byStatus[$status])) {
            $conds[] = $byStatus[$status];
        }
        return ['WHERE ' . implode(' AND ', $conds), $params];
    }
}
