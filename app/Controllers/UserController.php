<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\User;
use PDOException;

/**
 * Quản lý tài khoản nhân viên. Mọi route chỉ dành cho admin (xem config/routes.php).
 * Không xóa tài khoản (đã gắn với ca, hóa đơn, phiếu nhập) mà khóa/mở khóa.
 * Luôn phải còn ít nhất 1 quản trị viên đang hoạt động, và không ai tự khóa/hạ quyền chính mình.
 */
class UserController extends Controller
{
    private const PER_PAGE = 10;
    private const LABELS = [
        'username'              => 'Tên đăng nhập',
        'full_name'             => 'Họ tên',
        'role_id'               => 'Vai trò',
        'phone'                 => 'Số điện thoại',
        'email'                 => 'Email',
        'password'              => 'Mật khẩu',
        'password_confirmation' => 'Xác nhận mật khẩu',
    ];

    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $q = $this->input('q', '');
        $q = is_string($q) ? $q : '';
        $roleId = $this->input('role_id', '');
        $roleId = is_string($roleId) && ctype_digit($roleId) ? (int) $roleId : 0;
        $status = $this->input('status', '');
        $status = in_array($status, ['active', 'inactive'], true) ? $status : '';

        $paginator = new Paginator($this->users->countFiltered($q, $roleId, $status), self::PER_PAGE);
        $this->view('users/index', [
            'title'     => 'Nhân viên',
            'users'     => $this->users->search($q, $roleId, $status, $paginator->limit, $paginator->offset),
            'paginator' => $paginator,
            'roles'     => $this->roleOptions(),
            'meId'      => (int) Auth::user()['id'],
            'q'         => $q,
            'roleId'    => $roleId,
            'status'    => $status,
        ]);
    }

    public function create(): void
    {
        $this->view('users/form', [
            'title' => 'Thêm nhân viên',
            'user'  => null,
            'roles' => $this->roleOptions(),
            'isSelf' => false,
        ]);
    }

    public function store(): void
    {
        $back = '/users/create';
        $this->normalize();

        $data = $this->validate([
            'username'              => 'required|min:3|max:50|alpha_dash|unique:users,username',
            'full_name'             => 'required|max:100',
            'role_id'               => 'required|integer|exists:roles,id',
            'phone'                 => 'phone|max:20',
            'email'                 => 'email|max:100',
            'password'              => 'required|min:6|max:72',
            'password_confirmation' => 'required|same:password',
        ], self::LABELS, $back);

        try {
            $this->users->create([
                'store_id'      => (int) App::config('app.store_id'),
                'role_id'       => (int) $data['role_id'],
                'username'      => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name'     => $data['full_name'],
                'phone'         => $data['phone'] === '' ? null : $data['phone'],
                'email'         => $data['email'] === '' ? null : $data['email'],
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // trùng username do 2 người lưu cùng lúc
                throw $e;
            }
            $this->backWithErrors(['username' => 'Tên đăng nhập đã tồn tại.'], $back);
        }
        Session::flash('success', 'Đã thêm nhân viên "' . $data['full_name'] . '".');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = $this->findOrFail($id);
        $this->view('users/form', [
            'title'  => 'Sửa nhân viên',
            'user'   => $this->forView($user),
            'roles'  => $this->roleOptions(),
            'isSelf' => (int) $user['id'] === (int) Auth::user()['id'],
        ]);
    }

    public function update(string $id): void
    {
        $user = $this->findOrFail($id);
        $userId = (int) $user['id'];
        $isSelf = $userId === (int) Auth::user()['id'];
        $back = "/users/$userId/edit";
        $this->normalize();

        // Không tự đổi vai trò của chính mình (tránh tự hạ quyền rồi mất quyền quản trị)
        if ($isSelf) {
            $_POST['role_id'] = (string) $user['role_id'];
        }
        $newPassword = is_string($_POST['password'] ?? null) ? trim($_POST['password']) : '';

        $data = $this->validate([
            'full_name'             => 'required|max:100',
            'role_id'               => 'required|integer|exists:roles,id',
            'phone'                 => 'phone|max:20',
            'email'                 => 'email|max:100',
            'password'              => 'min:6|max:72',   // để trống = giữ mật khẩu cũ
            'password_confirmation' => $newPassword !== '' ? 'required|same:password' : 'same:password',
        ], self::LABELS, $back);

        // Phải còn ít nhất 1 quản trị viên đang hoạt động
        if ($user['role_code'] === 'admin' && (int) $user['is_active'] === 1
            && (int) $data['role_id'] !== (int) $user['role_id']
            && $this->users->activeAdminCount($userId) === 0) {
            $this->backWithErrors(['role_id' => 'Phải còn ít nhất một quản trị viên đang hoạt động.'], $back);
        }

        $row = [
            'role_id'   => (int) $data['role_id'],
            'full_name' => $data['full_name'],
            'phone'     => $data['phone'] === '' ? null : $data['phone'],
            'email'     => $data['email'] === '' ? null : $data['email'],
        ];
        $resetPassword = is_string($data['password']) && $data['password'] !== '';
        if ($resetPassword) {
            $row['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $this->users->update($userId, $row);

        Session::flash('success', 'Đã cập nhật nhân viên "' . $data['full_name'] . '"'
            . ($resetPassword ? ' và đặt lại mật khẩu.' : '.'));
        redirect('/users');
    }

    /** Khóa/mở khóa tài khoản. Tài khoản bị khóa mất quyền truy cập ngay (xem Auth::user()). */
    public function toggle(string $id): void
    {
        $user = $this->findOrFail($id);
        $userId = (int) $user['id'];

        if ($userId === (int) Auth::user()['id']) {
            Session::flash('danger', 'Bạn không thể tự khóa tài khoản của chính mình.');
            redirect('/users');
        }

        $deactivate = (int) $user['is_active'] === 1;
        if ($deactivate && $user['role_code'] === 'admin' && $this->users->activeAdminCount($userId) === 0) {
            Session::flash('danger', 'Không thể khóa: phải còn ít nhất một quản trị viên đang hoạt động.');
            redirect('/users');
        }

        $this->users->update($userId, ['is_active' => $deactivate ? 0 : 1]);
        Session::flash('success', $deactivate
            ? "Đã khóa tài khoản \"{$user['username']}\"."
            : "Đã mở khóa tài khoản \"{$user['username']}\".");
        redirect('/users');
    }

    /** Tên đăng nhập viết thường; SĐT bỏ dấu cách/chấm/gạch. */
    private function normalize(): void
    {
        if (isset($_POST['username']) && is_string($_POST['username'])) {
            $_POST['username'] = strtolower(trim($_POST['username']));
        }
        if (isset($_POST['phone']) && is_string($_POST['phone'])) {
            $_POST['phone'] = preg_replace('/[\s.\-]/', '', $_POST['phone']);
        }
    }

    /** [id => tên vai trò] cho ô chọn/lọc. */
    private function roleOptions(): array
    {
        return array_column((new Role())->all('id ASC'), 'name', 'id');
    }

    /** Bỏ password_hash trước khi đưa dữ liệu ra view. */
    private function forView(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }

    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? ($this->users->findWithRole((int) $id) ?? View::abort(404)) : View::abort(404);
    }
}
