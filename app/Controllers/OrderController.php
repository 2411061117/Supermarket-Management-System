<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessException;
use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\View;
use App\Models\Order;
use App\Models\User;

/** Hóa đơn: danh sách, xem, in, hủy. Thu ngân chỉ thấy hóa đơn của mình; hủy chỉ admin. */
class OrderController extends Controller
{
    private const PER_PAGE = 10;
    private Order $orders;

    public function __construct()
    {
        $this->orders = new Order();
    }

    public function index(): void
    {
        $me = Auth::user();
        $isAdmin = $me['role'] === 'admin';
        $storeId = (int) $me['store_id'];

        $q = $this->input('q', '');
        $q = is_string($q) ? mb_substr($q, 0, 30) : '';
        $userId = $this->input('user_id', '');
        $userId = $isAdmin && is_string($userId) && ctype_digit($userId) ? (int) $userId : 0;
        if (!$isAdmin) {
            $userId = (int) $me['id'];   // thu ngân chỉ thấy hóa đơn của mình, bất kể tham số gửi lên
        }
        $methods = array_column($this->orders->paymentMethods(), 'name', 'code');
        $method = $this->input('method', '');
        $method = is_string($method) && isset($methods[$method]) ? $method : '';
        $status = $this->input('status', '');
        $status = in_array($status, ['completed', 'cancelled'], true) ? $status : '';
        $from = $this->dateInput('from');
        $to = $this->dateInput('to');
        $notice = '';
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
            $notice = 'Đã đổi chỗ "Từ ngày" và "Đến ngày" vì khoảng ngày bị ngược.';
        }

        $paginator = new Paginator($this->orders->countFiltered($storeId, $userId, $q, $method, $status, $from, $to), self::PER_PAGE);
        $this->view('orders/index', [
            'title'     => 'Hóa đơn',
            'orders'    => $this->orders->search($storeId, $userId, $q, $method, $status, $from, $to, $paginator->limit, $paginator->offset),
            'paginator' => $paginator,
            'isAdmin'   => $isAdmin,
            'users'     => $isAdmin ? array_column((new User())->all('full_name ASC'), 'full_name', 'id') : [],
            'methods'   => $methods,
            'q' => $q, 'userId' => $userId, 'method' => $method, 'status' => $status, 'from' => $from, 'to' => $to,
            'notice'    => $notice,
        ]);
    }

    public function show(string $id): void
    {
        $order = $this->findOr404($id);
        $this->view('orders/show', $this->detail($order) + [
            'title'     => 'Hóa đơn ' . $order['code'],
            'canCancel' => Auth::user()['role'] === 'admin' && $order['status'] === 'completed' && $order['shift_status'] === 'open',
        ]);
    }

    /** Bản in khổ nhỏ (không menu); nút "In hóa đơn" gọi window.print(). */
    public function print(string $id): void
    {
        $order = $this->findOr404($id);
        $this->view('orders/print', $this->detail($order) + ['title' => 'Hóa đơn ' . $order['code']], 'layouts/blank');
    }

    /** POST /orders/{id}/cancel (chỉ admin, kiểm tra ở route). */
    public function cancel(string $id): void
    {
        $order = $this->findOr404($id);
        try {
            $this->orders->cancel((int) $order['id'], (int) $order['store_id']);
            Session::flash('success', 'Đã hủy hóa đơn ' . $order['code'] . ': tồn kho đã được hoàn lại.');
        } catch (BusinessException $e) {
            Session::flash('danger', $e->getMessage());
        }
        redirect('/orders/' . (int) $order['id']);
    }

    /** Hóa đơn của chi nhánh mình; thu ngân chỉ xem được hóa đơn do mình lập (ngược lại 404). */
    private function findOr404(string $id): array
    {
        $me = Auth::user();
        $order = ctype_digit($id) ? $this->orders->findForStore((int) $id, (int) $me['store_id']) : null;
        if (!$order || ($me['role'] !== 'admin' && (int) $order['user_id'] !== (int) $me['id'])) {
            View::abort(404);
        }
        return $order;
    }

    /** Dữ liệu chung cho trang xem/in: các dòng gộp theo sản phẩm, kèm chi tiết từng lô. */
    private function detail(array $order): array
    {
        $lines = [];
        foreach ($this->orders->items((int) $order['id']) as $i) {
            $pid = $i['product_id'];
            $lines[$pid] ??= ['sku' => $i['sku'], 'name' => $i['product_name'], 'unit' => $i['unit_name'],
                              'unit_price' => (int) $i['unit_price'], 'qty' => 0, 'amount' => 0, 'batches' => []];
            $lines[$pid]['qty'] += (int) $i['qty'];
            $lines[$pid]['amount'] += (int) $i['qty'] * (int) $i['unit_price'];
            $lines[$pid]['batches'][] = ['code' => $i['batch_code'], 'expiry' => $i['expiry_date'], 'qty' => (int) $i['qty']];
        }
        return ['order' => $order, 'lines' => array_values($lines)];
    }

    private function dateInput(string $key): string
    {
        $v = $this->input($key, '');
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d', $v) : false;
        return $d && $d->format('Y-m-d') === $v ? $v : '';
    }
}
