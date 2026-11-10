<?php
namespace App\Models;

use App\Core\BusinessException;
use App\Core\Database;
use App\Core\Model;
use PDOException;

/**
 * Hóa đơn bán hàng. Mỗi dòng (order_items) gắn 1 lô; 1 sản phẩm lấy từ nhiều lô thì tách nhiều dòng.
 * Tiền là số nguyên (DECIMAL(12,0)) – không dùng float.
 */
class Order extends Model
{
    protected string $table = 'orders';

    /** Điểm thưởng: 1 điểm cho mỗi 10.000 ₫ của tổng tiền. */
    public const POINT_UNIT = 10000;

    public static function pointsFor(int $total): int
    {
        return intdiv(max(0, $total), self::POINT_UNIT);
    }

    /**
     * Mã hóa đơn kế tiếp trong ngày: HD-YYYYMMDD-NNNN. Gọi trong transaction;
     * nếu 2 người cùng lấy 1 mã, cột UNIQUE sẽ chặn và caller cần thử lại.
     */
    public function nextCode(?string $date = null): string
    {
        $prefix = 'HD-' . date('Ymd', $date ? strtotime($date) : time()) . '-';
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)), 0) FROM orders WHERE code LIKE ?');
        $stmt->execute([strlen($prefix) + 1, $prefix . '%']);
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Hóa đơn kèm thu ngân, khách, phương thức thanh toán; null nếu không có hoặc thuộc chi nhánh khác. */
    public function findForStore(int $id, int $storeId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, u.full_name AS user_name, c.full_name AS customer_name, c.phone AS customer_phone,
                    m.code AS method_code, m.name AS method_name, p.amount AS paid_amount,
                    sh.status AS shift_status, sh.opened_at AS shift_opened_at
               FROM orders o
               LEFT JOIN shifts sh ON sh.id = o.shift_id
               LEFT JOIN users u ON u.id = o.user_id
               LEFT JOIN customers c ON c.id = o.customer_id
               LEFT JOIN payments p ON p.order_id = o.id
               LEFT JOIN payment_methods m ON m.id = p.method_id
              WHERE o.id = ? AND o.store_id = ?
              LIMIT 1"
        );
        $stmt->execute([$id, $storeId]);
        return $stmt->fetch() ?: null;
    }

    /** Các dòng của hóa đơn (mỗi dòng = 1 lô), kèm sản phẩm. */
    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.id, i.qty, i.unit_price, i.batch_id, b.batch_code, b.expiry_date, b.cost_price,
                    p.id AS product_id, p.sku, p.name AS product_name, u.name AS unit_name
               FROM order_items i
               JOIN product_batches b ON b.id = i.batch_id
               JOIN products p ON p.id = b.product_id
               JOIN units u ON u.id = p.unit_id
              WHERE i.order_id = ?
              ORDER BY p.name, b.expiry_date IS NULL, b.expiry_date, i.id'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /** Phương thức thanh toán đang bật (cho POS và bộ lọc). */
    public function paymentMethods(): array
    {
        return $this->db->query('SELECT id, code, name FROM payment_methods WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    /** Số lượng tối đa mỗi sản phẩm trong 1 hóa đơn (chặn nhập nhầm). */
    public const MAX_QTY = 9999;

    /**
     * Bán hàng tại quầy trong MỘT transaction. Chỉ nhận product_id + qty; giá, tổng, lô đều lấy từ CSDL.
     * Thứ tự khóa (luôn như nhau để tránh deadlock): ca → sản phẩm theo id tăng dần → lô (FEFO) → khách.
     *
     * @param array       $lines       [['product_id' => int, 'qty' => int], ...] (trùng sản phẩm sẽ được gộp)
     * @param string      $methodCode  cash | bank | ewallet
     * @param int         $discount    giảm giá theo số tiền; việc "chỉ admin được nhập" do controller kiểm tra
     * @param string|null $phone       SĐT khách (tùy chọn); không tồn tại thì báo lỗi
     * @param int|null    $cashGiven   tiền khách đưa, bắt buộc và >= tổng khi trả tiền mặt
     * @return array id, code, subtotal, discount, total, change, points, lines
     * @throws BusinessException khi vi phạm nghiệp vụ (chưa mở ca, hết hàng, tiền thiếu...); không ghi gì
     */
    public function checkout(int $storeId, int $userId, array $lines, string $methodCode, int $discount = 0,
                             ?string $phone = null, ?int $cashGiven = null): array
    {
        $wanted = $this->mergeLines($lines);
        for ($attempt = 1; ; $attempt++) {
            try {
                return Database::transaction(fn() => $this->doCheckout(
                    $storeId, $userId, $wanted, $methodCode, $discount, $phone, $cashGiven
                ));
            } catch (PDOException $e) {
                // Trùng mã hóa đơn (UNIQUE, 1062) hoặc deadlock (1213): MySQL đã hủy transaction, thử lại cả giao dịch
                if (!in_array($e->errorInfo[1] ?? 0, [1062, 1213], true) || $attempt >= 5) {
                    throw $e;
                }
            }
        }
    }

    /** Gộp dòng trùng sản phẩm, kiểm tra kiểu/khoảng số lượng, sắp theo product_id tăng dần: [productId => qty]. */
    private function mergeLines(array $lines): array
    {
        $wanted = [];
        foreach ($lines as $line) {
            $pid = $line['product_id'] ?? null;
            $qty = $line['qty'] ?? null;
            if (!is_int($pid) && !(is_string($pid) && ctype_digit($pid))) {
                throw new BusinessException('Sản phẩm trong giỏ không hợp lệ.');
            }
            if (!is_int($qty) && !(is_string($qty) && ctype_digit($qty))) {
                throw new BusinessException('Số lượng phải là số nguyên dương.');
            }
            $wanted[(int) $pid] = ($wanted[(int) $pid] ?? 0) + (int) $qty;
        }
        if (!$wanted) {
            throw new BusinessException('Giỏ hàng đang trống.');
        }
        foreach ($wanted as $qty) {
            if ($qty < 1 || $qty > self::MAX_QTY) {
                throw new BusinessException('Số lượng mỗi sản phẩm phải từ 1 đến ' . self::MAX_QTY . '.');
            }
        }
        ksort($wanted);
        return $wanted;
    }

    private function doCheckout(int $storeId, int $userId, array $wanted, string $methodCode, int $discount,
                                ?string $phone, ?int $cashGiven): array
    {
        // 1. Khóa ca đang mở của người bán (đóng ca cũng khóa dòng này nên không bán được lúc đang đóng ca)
        $stmt = $this->db->prepare("SELECT id FROM shifts WHERE user_id = ? AND store_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $stmt->execute([$userId, $storeId]);
        $shiftId = $stmt->fetchColumn();
        if ($shiftId === false) {
            throw new BusinessException('Bạn chưa mở ca (hoặc ca đã đóng), không thể bán hàng.');
        }

        // 2. Khóa sản phẩm theo id tăng dần, lấy giá hiện tại từ CSDL
        $ids = array_keys($wanted);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, name, sale_price, is_active FROM products WHERE id IN ($marks) ORDER BY id FOR UPDATE");
        $stmt->execute($ids);
        $products = array_column($stmt->fetchAll(), null, 'id');

        // 3. Phân bổ FEFO; gom hết các sản phẩm lỗi để báo một lần
        $items = [];
        $problems = [];
        $subtotal = 0;
        $batches = new Batch();
        foreach ($wanted as $pid => $qty) {
            $p = $products[$pid] ?? null;
            if (!$p) {
                throw new BusinessException('Có sản phẩm trong giỏ không tồn tại.');
            }
            if (!(int) $p['is_active']) {
                $problems[] = ['product_id' => $pid, 'name' => $p['name'], 'available' => 0, 'reason' => 'inactive'];
                continue;
            }
            try {
                $allocs = $batches->allocateFefo($pid, $storeId, $qty);
            } catch (BusinessException $e) {
                $problems[] = ['product_id' => $pid, 'name' => $p['name'], 'available' => $e->details()['available'] ?? 0, 'reason' => 'stock'];
                continue;
            }
            $price = (int) $p['sale_price'];
            foreach ($allocs as $a) {
                $items[] = ['batch_id' => $a['batch_id'], 'qty' => $a['qty'], 'unit_price' => $price];
            }
            $subtotal += $price * $qty;
        }
        if ($problems) {
            $names = implode(', ', array_map(
                fn($x) => $x['reason'] === 'inactive' ? $x['name'] . ' (ngừng bán)' : $x['name'] . ' (còn ' . $x['available'] . ')',
                $problems
            ));
            throw new BusinessException('Không đủ hàng hoặc ngừng bán: ' . $names . '.', ['problems' => $problems]);
        }

        // 4. Giảm giá, tổng, thanh toán – mọi số tiền do server tính
        if ($discount < 0 || $discount > $subtotal) {
            throw new BusinessException('Giảm giá phải từ 0 đến tạm tính (' . $subtotal . ').');
        }
        $total = $subtotal - $discount;
        if ($total <= 0) {
            throw new BusinessException('Tổng thanh toán phải lớn hơn 0.');
        }
        $stmt = $this->db->prepare('SELECT id, code FROM payment_methods WHERE code = ? AND is_active = 1');
        $stmt->execute([$methodCode]);
        $method = $stmt->fetch();
        if (!$method) {
            throw new BusinessException('Phương thức thanh toán không hợp lệ.');
        }
        $change = 0;
        if ($method['code'] === 'cash') {
            if ($cashGiven === null || $cashGiven < $total) {
                throw new BusinessException('Tiền khách đưa chưa đủ để thanh toán.');
            }
            $change = $cashGiven - $total;
        }

        // 5. Khách hàng (tùy chọn)
        $customerId = null;
        $points = 0;
        if ($phone !== null && trim($phone) !== '') {
            $digits = Customer::normalizePhone($phone);
            if (strlen($digits) < 8 || strlen($digits) > 15) {
                throw new BusinessException('Số điện thoại khách hàng không hợp lệ.');
            }
            $stmt = $this->db->prepare('SELECT id FROM customers WHERE phone = ? FOR UPDATE');
            $stmt->execute([$digits]);
            $customerId = $stmt->fetchColumn();
            if ($customerId === false) {
                throw new BusinessException('Không tìm thấy khách hàng có số điện thoại này.');
            }
            $customerId = (int) $customerId;
            $points = self::pointsFor($total);
        }

        // 6. Trừ kho (điều kiện qty_remaining >= qty là chốt chặn cuối, không để tồn âm)
        $deduct = $this->db->prepare('UPDATE product_batches SET qty_remaining = qty_remaining - ? WHERE id = ? AND qty_remaining >= ?');
        foreach ($items as $it) {
            $deduct->execute([$it['qty'], $it['batch_id'], $it['qty']]);
            if ($deduct->rowCount() !== 1) {
                throw new \LogicException('Trừ kho thất bại cho lô #' . $it['batch_id']);
            }
        }

        // 7. Lưu hóa đơn, các dòng, thanh toán; cộng điểm
        $code = $this->nextCode();
        $orderId = $this->create([
            'code'        => $code,
            'store_id'    => $storeId,
            'channel'     => 'pos',
            'shift_id'    => (int) $shiftId,
            'user_id'     => $userId,
            'customer_id' => $customerId,
            'subtotal'    => $subtotal,
            'discount'    => $discount,
            'total'       => $total,
            'status'      => 'completed',
        ]);
        $ins = $this->db->prepare('INSERT INTO order_items (order_id, batch_id, qty, unit_price) VALUES (?, ?, ?, ?)');
        foreach ($items as $it) {
            $ins->execute([$orderId, $it['batch_id'], $it['qty'], $it['unit_price']]);
        }
        $this->db->prepare('INSERT INTO payments (order_id, method_id, amount) VALUES (?, ?, ?)')
            ->execute([$orderId, $method['id'], $total]);
        if ($customerId !== null && $points > 0) {
            $this->db->prepare('UPDATE customers SET points = points + ? WHERE id = ?')->execute([$points, $customerId]);
        }

        return ['id' => $orderId, 'code' => $code, 'subtotal' => $subtotal, 'discount' => $discount,
                'total' => $total, 'change' => $change, 'points' => $points, 'lines' => count($items)];
    }

    /**
     * Hủy hóa đơn (admin quyết định ở controller): hoàn tồn đúng lô đã bán, trừ điểm đã cộng, đổi trạng thái
     * `cancelled` (không xóa). Chỉ hủy được khi hóa đơn còn `completed` và ca của hóa đơn còn mở
     * (ca đã đóng thì tiền két đã đối chiếu). Khóa theo thứ tự: ca → hóa đơn → sản phẩm → lô (id tăng dần) → khách.
     * @throws BusinessException nếu không hủy được
     */
    public function cancel(int $orderId, int $storeId): void
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                Database::transaction(fn() => $this->doCancel($orderId, $storeId));
                return;
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) !== 1213 || $attempt >= 3) {   // deadlock: thử lại
                    throw $e;
                }
            }
        }
    }

    private function doCancel(int $orderId, int $storeId): void
    {
        // Đọc shift_id trước (chưa khóa) để khóa ca trước hóa đơn, cùng thứ tự với checkout/đóng ca
        $stmt = $this->db->prepare('SELECT shift_id FROM orders WHERE id = ? AND store_id = ?');
        $stmt->execute([$orderId, $storeId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new BusinessException('Không tìm thấy hóa đơn.');
        }
        $shiftStatus = null;
        if ($row['shift_id'] !== null) {
            $stmt = $this->db->prepare('SELECT status FROM shifts WHERE id = ? FOR UPDATE');
            $stmt->execute([$row['shift_id']]);
            $shiftStatus = $stmt->fetchColumn();
        }
        $stmt = $this->db->prepare('SELECT id, status, customer_id, total FROM orders WHERE id = ? AND store_id = ? FOR UPDATE');
        $stmt->execute([$orderId, $storeId]);
        $order = $stmt->fetch();
        if ($order['status'] === 'cancelled') {
            throw new BusinessException('Hóa đơn này đã được hủy trước đó.');
        }
        if ($shiftStatus !== 'open') {
            throw new BusinessException('Ca của hóa đơn đã đóng nên không thể hủy.');
        }

        $stmt = $this->db->prepare(
            'SELECT i.batch_id, SUM(i.qty) AS qty, b.product_id
               FROM order_items i JOIN product_batches b ON b.id = i.batch_id
              WHERE i.order_id = ? GROUP BY i.batch_id, b.product_id ORDER BY b.product_id, i.batch_id'
        );
        $stmt->execute([$orderId]);
        $rows = $stmt->fetchAll();
        // Khóa sản phẩm rồi lô theo thứ tự cố định để không deadlock với checkout
        $productIds = array_values(array_unique(array_column($rows, 'product_id')));
        if ($productIds) {
            $marks = implode(',', array_fill(0, count($productIds), '?'));
            $this->db->prepare("SELECT id FROM products WHERE id IN ($marks) ORDER BY id FOR UPDATE")->execute($productIds);
        }
        $restore = $this->db->prepare('UPDATE product_batches SET qty_remaining = qty_remaining + ? WHERE id = ?');
        foreach ($rows as $r) {
            $restore->execute([(int) $r['qty'], (int) $r['batch_id']]);
        }

        if ($order['customer_id'] !== null) {
            $this->db->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE')->execute([$order['customer_id']]);
            $this->db->prepare('UPDATE customers SET points = GREATEST(CAST(points AS SIGNED) - ?, 0) WHERE id = ?')
                ->execute([self::pointsFor((int) $order['total']), $order['customer_id']]);
        }
        $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
    }

    /** Lọc hóa đơn: $userId = 0 là mọi thu ngân; $q tìm theo mã; $method là code phương thức ('' = tất cả); $status completed|cancelled|''. */
    public function countFiltered(int $storeId, int $userId, string $q, string $method, string $status, string $from, string $to): int
    {
        [$where, $params] = $this->filter($storeId, $userId, $q, $method, $status, $from, $to);
        $stmt = $this->db->prepare('SELECT COUNT(*) ' . $this->listFrom() . " $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** $limit/$offset là int do Paginator tạo nên nối thẳng vào SQL là an toàn. */
    public function search(int $storeId, int $userId, string $q, string $method, string $status, string $from, string $to, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($storeId, $userId, $q, $method, $status, $from, $to);
        $stmt = $this->db->prepare(
            'SELECT o.id, o.code, o.subtotal, o.discount, o.total, o.status, o.created_at,
                    u.full_name AS user_name, c.full_name AS customer_name, c.phone AS customer_phone, m.name AS method_name '
            . $this->listFrom() . " $where ORDER BY o.created_at DESC, o.id DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Mỗi hóa đơn có đúng 1 thanh toán nên các JOIN không nhân bản dòng. */
    private function listFrom(): string
    {
        return 'FROM orders o
                LEFT JOIN users u ON u.id = o.user_id
                LEFT JOIN customers c ON c.id = o.customer_id
                LEFT JOIN payments p ON p.order_id = o.id
                LEFT JOIN payment_methods m ON m.id = p.method_id';
    }

    private function filter(int $storeId, int $userId, string $q, string $method, string $status, string $from, string $to): array
    {
        $conds = ['o.store_id = ?'];
        $params = [$storeId];
        if ($userId > 0) {
            $conds[] = 'o.user_id = ?';
            $params[] = $userId;
        }
        if ($q !== '') {
            $conds[] = "o.code LIKE ? ESCAPE '!'";
            $params[] = self::likePattern($q);
        }
        if ($method !== '') {
            $conds[] = 'm.code = ?';
            $params[] = $method;
        }
        if ($status === 'completed' || $status === 'cancelled') {
            $conds[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($from !== '') {
            $conds[] = 'o.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $conds[] = 'o.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        return ['WHERE ' . implode(' AND ', $conds), $params];
    }

}
