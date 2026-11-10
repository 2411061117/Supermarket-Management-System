<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Order;

/** Hóa đơn: danh sách, xem, in, hủy. Thu ngân chỉ thấy hóa đơn của mình; hủy chỉ admin. */
class OrderController extends Controller
{
    private Order $orders;

    public function __construct()
    {
        $this->orders = new Order();
    }

    public function index(): void
    {
        $this->view('orders/index', ['title' => 'Hóa đơn']);
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

    // TODO Bước 3: show($id), print($id), cancel($id) – dùng findOr404().
}
