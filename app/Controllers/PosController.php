<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Order;
use App\Models\Shift;

/** Bán hàng tại quầy (admin, thu ngân). Bắt buộc có ca đang mở. */
class PosController extends Controller
{
    public function index(): void
    {
        $me = Auth::user();
        $this->view('pos/index', [
            'title'    => 'Bán hàng (POS)',
            'shift'    => (new Shift())->findOpenByUser((int) $me['id']),
            'methods'  => (new Order())->paymentMethods(),
            'isAdmin'  => $me['role'] === 'admin',
        ]);
    }

    // TODO Bước 1–2: checkout() – POST /pos/checkout
}
