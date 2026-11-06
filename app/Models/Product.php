<?php
namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function countFiltered(string $q, int $categoryId, string $status): int
    {
        [$where, $params] = $this->filter($q, $categoryId, $status);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** $limit/$offset là int do Paginator tạo ra nên nối thẳng vào SQL là an toàn. */
    public function search(string $q, int $categoryId, string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($q, $categoryId, $status);
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS category_name, u.name AS unit_name
               FROM products p
               JOIN categories c ON c.id = p.category_id
               JOIN units u ON u.id = p.unit_id
               $where
              ORDER BY p.is_active DESC, p.name
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Số lô hàng đã nhập của sản phẩm. Phiếu nhập và hóa đơn đều gắn với sản phẩm qua lô nên >0 nghĩa là đã phát sinh giao dịch. */
    public function batchCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM product_batches WHERE product_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tìm sản phẩm đang kinh doanh theo tên/SKU/barcode cho POS và form nhập kho.
     * Khớp chính xác barcode/SKU được xếp lên đầu (máy quét mã vạch). `stock` chỉ tính lô còn hạn.
     */
    public function searchActive(string $q, int $limit = 20): array
    {
        $like = self::likePattern($q);
        $stmt = $this->db->prepare(
            "SELECT p.id, p.sku, p.barcode, p.name, p.sale_price, p.image, u.name AS unit_name,
                    COALESCE((SELECT SUM(b.qty_remaining) FROM product_batches b
                               WHERE b.product_id = p.id AND (b.expiry_date IS NULL OR b.expiry_date >= CURDATE())), 0) AS stock
               FROM products p
               JOIN units u ON u.id = p.unit_id
              WHERE p.is_active = 1
                AND (p.name LIKE ? ESCAPE '!' OR p.sku LIKE ? ESCAPE '!' OR p.barcode LIKE ? ESCAPE '!')
              ORDER BY (p.barcode = ?) DESC, (p.sku = ?) DESC, p.name
              LIMIT $limit"
        );
        $stmt->execute([$like, $like, $like, $q, $q]);
        return $stmt->fetchAll();
    }

    /** Điều kiện WHERE: tìm theo tên/SKU/barcode, lọc loại hàng ($categoryId = 0: tất cả) và trạng thái. */
    private function filter(string $q, int $categoryId, string $status): array
    {
        $conds = [];
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
        if ($status === 'active' || $status === 'inactive') {
            $conds[] = 'p.is_active = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }
        return [$conds ? 'WHERE ' . implode(' AND ', $conds) : '', $params];
    }
}
