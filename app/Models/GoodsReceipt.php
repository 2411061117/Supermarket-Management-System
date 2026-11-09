<?php
namespace App\Models;

use App\Core\Model;

/** Phiếu nhập kho. Các dòng nằm ở goods_receipt_items (mỗi dòng gắn 1 lô). */
class GoodsReceipt extends Model
{
    protected string $table = 'goods_receipts';

    /**
     * Mã phiếu kế tiếp trong ngày: PN-YYYYMMDD-NNN. Gọi trong transaction;
     * nếu 2 người cùng lấy 1 mã, cột UNIQUE sẽ chặn và caller cần thử lại.
     */
    public function nextCode(?string $date = null): string
    {
        $prefix = 'PN-' . date('Ymd', $date ? strtotime($date) : time()) . '-';
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)), 0) FROM goods_receipts WHERE code LIKE ?');
        $stmt->execute([strlen($prefix) + 1, $prefix . '%']);
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Gắn 1 lô vào phiếu (mỗi dòng của phiếu = 1 lô). */
    public function addItem(int $receiptId, int $batchId): void
    {
        $this->db->prepare('INSERT INTO goods_receipt_items (receipt_id, batch_id) VALUES (?, ?)')->execute([$receiptId, $batchId]);
    }

    public function countFiltered(int $storeId, string $q, int $supplierId, string $from, string $to): int
    {
        [$where, $params] = $this->filter($storeId, $q, $supplierId, $from, $to);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM goods_receipts r $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Mỗi phiếu kèm NCC, người lập, số dòng và tổng giá trị nhập. $limit/$offset là int do Paginator tạo nên an toàn. */
    public function search(int $storeId, string $q, int $supplierId, string $from, string $to, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($storeId, $q, $supplierId, $from, $to);
        $stmt = $this->db->prepare(
            "SELECT r.id, r.code, r.note, r.created_at, s.name AS supplier_name, u.full_name AS user_name,
                    (SELECT COUNT(*) FROM goods_receipt_items i WHERE i.receipt_id = r.id) AS line_count,
                    (SELECT COALESCE(SUM(b.cost_price * b.qty_received), 0)
                       FROM goods_receipt_items i JOIN product_batches b ON b.id = i.batch_id
                      WHERE i.receipt_id = r.id) AS total_cost
               FROM goods_receipts r
               JOIN suppliers s ON s.id = r.supplier_id
               JOIN users u ON u.id = r.user_id
               $where
              ORDER BY r.created_at DESC, r.id DESC
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Phiếu kèm tên NCC, người lập; null nếu không có hoặc thuộc chi nhánh khác. */
    public function findForStore(int $id, int $storeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, s.name AS supplier_name, s.phone AS supplier_phone, u.full_name AS user_name
               FROM goods_receipts r
               JOIN suppliers s ON s.id = r.supplier_id
               JOIN users u ON u.id = r.user_id
              WHERE r.id = ? AND r.store_id = ?'
        );
        $stmt->execute([$id, $storeId]);
        return $stmt->fetch() ?: null;
    }

    /** Các dòng của phiếu (mỗi dòng = 1 lô). */
    public function items(int $receiptId): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.batch_code, b.expiry_date, b.cost_price, b.qty_received, b.qty_remaining,
                    p.id AS product_id, p.sku, p.name AS product_name, un.name AS unit_name
               FROM goods_receipt_items i
               JOIN product_batches b ON b.id = i.batch_id
               JOIN products p ON p.id = b.product_id
               JOIN units un ON un.id = p.unit_id
              WHERE i.receipt_id = ?
              ORDER BY i.id'
        );
        $stmt->execute([$receiptId]);
        return $stmt->fetchAll();
    }

    /** Lọc theo mã phiếu, NCC, khoảng ngày lập (from/to dạng Y-m-d, '' = không giới hạn; cả ngày `to`). */
    private function filter(int $storeId, string $q, int $supplierId, string $from, string $to): array
    {
        $conds = ['r.store_id = ?'];
        $params = [$storeId];
        if ($q !== '') {
            $conds[] = "r.code LIKE ? ESCAPE '!'";
            $params[] = self::likePattern($q);
        }
        if ($supplierId > 0) {
            $conds[] = 'r.supplier_id = ?';
            $params[] = $supplierId;
        }
        if ($from !== '') {
            $conds[] = 'r.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $conds[] = 'r.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        return ['WHERE ' . implode(' AND ', $conds), $params];
    }
}
