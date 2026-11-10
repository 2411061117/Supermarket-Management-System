<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessException;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shift;

/** Bán hàng tại quầy (admin, thu ngân). Bắt buộc có ca đang mở. */
class PosController extends Controller
{
    /** Số kết quả thanh toán gần nhất nhớ trong session để chống gửi trùng (bấm 2 lần, mạng chập chờn). */
    private const REMEMBER_REQUESTS = 20;

    public function index(): void
    {
        $me = Auth::user();
        $this->view('pos/index', [
            'title'   => 'Bán hàng (POS)',
            'shift'   => (new Shift())->findOpenByUser((int) $me['id']),
            'methods' => (new Order())->paymentMethods(),
            'isAdmin' => $me['role'] === 'admin',
        ]);
    }

    /** GET /pos/customer?phone=... -> {"found": bool, "name": ..., "points": ...} (tra khách theo SĐT). */
    public function customer(): void
    {
        $phone = $this->input('phone', '');
        $digits = is_string($phone) ? Customer::normalizePhone($phone) : '';
        $customer = strlen($digits) >= 8 && strlen($digits) <= 15 ? (new Customer())->findByPhone($digits) : null;
        $this->json($customer
            ? ['found' => true, 'phone' => $customer['phone'], 'name' => $customer['full_name'], 'points' => (int) $customer['points']]
            : ['found' => false]);
    }

    /**
     * POST /pos/checkout (JSON): {request_id, items: [{product_id, qty}], method, discount, phone, cash_given}.
     * Chỉ tin product_id + qty; giá, tổng, lô do server tính. Trả {"ok": true, "order": {...}} hoặc
     * {"ok": false, "message": ..., "problems": [...]} kèm mã 422 (lỗi nghiệp vụ) / 403 (giảm giá).
     * Cùng request_id gửi lại thì trả lại kết quả cũ, không bán lần 2.
     */
    public function checkout(): void
    {
        $me = Auth::user();
        $body = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($body)) {
            $this->fail('Dữ liệu gửi lên không hợp lệ.', 400);
        }

        $requestId = $body['request_id'] ?? '';
        if (!is_string($requestId) || !preg_match('/^[A-Za-z0-9-]{8,64}$/', $requestId)) {
            $this->fail('Thiếu mã yêu cầu, hãy tải lại trang.', 400);
        }
        $done = Session::get('pos_done', []);
        if (isset($done[$requestId])) {
            $this->json(['ok' => true, 'order' => $done[$requestId], 'duplicate' => true]);
        }

        $items = $body['items'] ?? null;
        if (!is_array($items) || !array_is_list($items)) {
            $this->fail('Giỏ hàng không hợp lệ.', 422);
        }
        $lines = [];
        foreach ($items as $it) {
            $lines[] = is_array($it) ? ['product_id' => $it['product_id'] ?? null, 'qty' => $it['qty'] ?? null] : [];
        }

        $discount = $this->intOrNull($body['discount'] ?? 0);
        if ($discount === null) {
            $this->fail('Giảm giá không hợp lệ.', 422);
        }
        if ($discount !== 0 && $me['role'] !== 'admin') {
            $this->fail('Chỉ quản lý được nhập giảm giá.', 403);
        }
        $cash = $body['cash_given'] ?? null;
        $cash = $cash === null || $cash === '' ? null : $this->intOrNull($cash);
        $method = $body['method'] ?? '';
        $phone = $body['phone'] ?? '';
        if (!is_string($method) || !is_string($phone) || mb_strlen($phone) > 30) {
            $this->fail('Dữ liệu gửi lên không hợp lệ.', 400);
        }

        try {
            $r = (new Order())->checkout((int) $me['store_id'], (int) $me['id'], $lines, $method, $discount, $phone, $cash);
        } catch (BusinessException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage(), 'problems' => $e->details()['problems'] ?? []], 422);
        }

        $order = ['id' => $r['id'], 'code' => $r['code'], 'subtotal' => $r['subtotal'], 'discount' => $r['discount'],
                  'total' => $r['total'], 'change' => $r['change'], 'points' => $r['points']];
        $done[$requestId] = $order;
        Session::set('pos_done', array_slice($done, -self::REMEMBER_REQUESTS, null, true));
        $this->json(['ok' => true, 'order' => $order]);
    }

    /** Số nguyên không âm từ JSON (int hoặc chuỗi toàn chữ số), ngược lại null. */
    private function intOrNull(mixed $v): ?int
    {
        if (is_int($v)) {
            return $v >= 0 ? $v : null;
        }
        return is_string($v) && ctype_digit($v) && strlen($v) <= 12 ? (int) $v : null;
    }

    private function fail(string $message, int $status): never
    {
        $this->json(['ok' => false, 'message' => $message, 'problems' => []], $status);
    }
}
