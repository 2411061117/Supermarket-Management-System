<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Models\Shift;
use App\Models\User;

/**
 * Ca làm việc (admin, thu ngân). Thu ngân chỉ thấy ca của mình; admin thấy mọi ca và lọc được.
 * Tuần 3: mở ca + xem danh sách. Đóng ca/đối chiếu tiền mặt làm ở Tuần 4 (cần doanh thu từ POS).
 */
class ShiftController extends Controller
{
    private const PER_PAGE = 10;
    private Shift $shifts;

    public function __construct()
    {
        $this->shifts = new Shift();
    }

    public function index(): void
    {
        $me = Auth::user();
        $isAdmin = $me['role'] === 'admin';

        $userId = $this->input('user_id', '');
        $userId = $isAdmin && is_string($userId) && ctype_digit($userId) ? (int) $userId : 0;
        if (!$isAdmin) {
            $userId = (int) $me['id'];   // thu ngân chỉ xem ca của chính mình, bất kể tham số gửi lên
        }
        $status = $this->input('status', '');
        $status = in_array($status, ['open', 'closed'], true) ? $status : '';
        $from = $this->dateInput('from');
        $to = $this->dateInput('to');
        $notice = '';
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
            $notice = 'Đã đổi chỗ "Từ ngày" và "Đến ngày" vì khoảng ngày bị ngược.';
        }

        $storeId = (int) $me['store_id'];
        $paginator = new Paginator($this->shifts->countFiltered($storeId, $userId, $status, $from, $to), self::PER_PAGE);
        $this->view('shifts/index', [
            'title'     => 'Ca làm việc',
            'current'   => $this->shifts->findOpenByUser((int) $me['id']),
            'shifts'    => $this->shifts->search($storeId, $userId, $status, $from, $to, $paginator->limit, $paginator->offset),
            'paginator' => $paginator,
            'isAdmin'   => $isAdmin,
            'users'     => $isAdmin ? array_column((new User())->all('full_name ASC'), 'full_name', 'id') : [],
            'userId'    => $userId,
            'status'    => $status,
            'from'      => $from,
            'to'        => $to,
            'notice'    => $notice,
        ]);
    }

    public function open(): void
    {
        $me = Auth::user();
        $_POST['note'] = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';
        $data = $this->validate([
            'opening_cash' => 'required|integer|min:0|max:999999999',
            'note'         => 'max:255',
        ], ['opening_cash' => 'Tiền đầu ca', 'note' => 'Ghi chú'], '/shifts');

        $id = $this->shifts->open((int) $me['store_id'], (int) $me['id'], (int) $data['opening_cash'], $data['note'] === '' ? null : $data['note']);
        if ($id === null) {
            Session::flash('danger', 'Bạn đang có một ca mở, không thể mở thêm ca mới.');
            redirect('/shifts');
        }
        Session::flash('success', 'Đã mở ca với tiền đầu ca ' . money($data['opening_cash']) . '.');
        redirect('/shifts');
    }

    /** Ngày hợp lệ dạng Y-m-d, ngược lại '' (không lọc). */
    private function dateInput(string $key): string
    {
        $v = $this->input($key, '');
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d', $v) : false;
        return $d && $d->format('Y-m-d') === $v ? $v : '';
    }
}
