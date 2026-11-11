<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\View;
use App\Models\Shift;
use App\Models\User;

/**
 * Ca làm việc (admin, thu ngân). Thu ngân chỉ thấy ca của mình; admin thấy mọi ca và lọc được.
 * Mở ca, đóng ca (nhập tiền mặt đếm thực tế, đối chiếu với tiền dự kiến) và xem danh sách.
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
        $current = $this->shifts->findOpenByUser((int) $me['id']);
        $paginator = new Paginator($this->shifts->countFiltered($storeId, $userId, $status, $from, $to), self::PER_PAGE);
        $this->view('shifts/index', [
            'title'     => 'Ca làm việc',
            'current'   => $current,
            'expected'  => $current ? $this->shifts->expectedCash($current) : 0,
            'summary'   => $current ? $this->shifts->summary((int) $current['id']) : null,
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

    /** Đóng ca: chủ ca hoặc admin (đóng hộ khi nhân viên quên). */
    public function close(string $id): void
    {
        $me = Auth::user();
        $shift = ctype_digit($id) ? $this->shifts->find((int) $id) : null;
        if (!$shift || (int) $shift['store_id'] !== (int) $me['store_id']) {
            View::abort(404);
        }
        if ($me['role'] !== 'admin' && (int) $shift['user_id'] !== (int) $me['id']) {
            View::abort(403);
        }
        $data = $this->validate([
            'closing_cash' => 'required|integer|min:0|max:999999999',
        ], ['closing_cash' => 'Tiền mặt cuối ca'], '/shifts');

        $expected = $this->shifts->close((int) $shift['id'], (int) $data['closing_cash']);
        if ($expected === null) {
            Session::flash('danger', 'Ca này đã được đóng trước đó.');
            redirect('/shifts');
        }
        $diff = (int) $data['closing_cash'] - $expected;
        $msg = 'Đã đóng ca #' . $shift['id'] . '. Tiền cuối ca ' . money($data['closing_cash']) . '.';
        if ($diff !== 0) {
            $msg .= ' Chênh lệch so với dự kiến: ' . ($diff > 0 ? '+' : '−') . money(abs($diff)) . '.';
        }
        Session::flash($diff === 0 ? 'success' : 'warning', $msg);
        redirect('/shifts/' . (int) $shift['id']);
    }

    /** Chi tiết ca: doanh thu, tổng theo phương thức, chênh lệch tiền mặt, danh sách hóa đơn. Thu ngân chỉ xem ca của mình. */
    public function show(string $id): void
    {
        $me = Auth::user();
        $shift = ctype_digit($id) ? $this->shifts->findWithUser((int) $id) : null;
        if (!$shift || (int) $shift['store_id'] !== (int) $me['store_id']) {
            View::abort(404);
        }
        if ($me['role'] !== 'admin' && (int) $shift['user_id'] !== (int) $me['id']) {
            View::abort(403);
        }
        $isOpen = $shift['status'] === 'open';
        $expected = $isOpen ? $this->shifts->expectedCash($shift) : (int) ($shift['expected_cash'] ?? $this->shifts->expectedCash($shift));
        $this->view('shifts/show', [
            'title'      => 'Ca #' . $shift['id'],
            'shift'      => $shift,
            'summary'    => $this->shifts->summary((int) $shift['id']),
            'orders'     => $this->shifts->orders((int) $shift['id']),
            'expected'   => $expected,
            'difference' => $isOpen || $shift['closing_cash'] === null ? null : (int) $shift['closing_cash'] - $expected,
        ]);
    }

    /** Ngày hợp lệ dạng Y-m-d, ngược lại '' (không lọc). */
    private function dateInput(string $key): string
    {
        $v = $this->input($key, '');
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d', $v) : false;
        return $d && $d->format('Y-m-d') === $v ? $v : '';
    }
}
