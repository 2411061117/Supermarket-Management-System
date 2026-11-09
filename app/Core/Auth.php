<?php
namespace App\Core;

use App\Models\User;

/** Xác thực nhân viên và kiểm tra vai trò (RBAC). */
class Auth
{
    /** Người dùng hiện tại đã đối chiếu với DB (chỉ truy vấn 1 lần mỗi request). */
    private static ?array $current = null;
    private static bool $loaded = false;
    private static bool $revoked = false;

    public static function attempt(string $username, string $password): bool
    {
        $user = (new User())->findActiveByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);   // chống session fixationn
        self::$loaded = false;
        Session::set('user', [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role_code'],
            'role_name' => $user['role_name'],
            'store_id'  => (int) $user['store_id'],
        ]);
        (new User())->touchLastLogin((int) $user['id']);
        return true;
    }

    /**
     * Người dùng đang đăng nhập, lấy lại từ DB ở mỗi request (không tin hoàn toàn vào session) để:
     *  - tài khoản bị khóa/xóa mất quyền truy cập ngay, không cần chờ họ đăng xuất;
     *  - đổi vai trò hoặc họ tên có hiệu lực ngay.
     */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$current;
        }
        self::$loaded = true;

        $session = Session::get('user');
        if ($session === null) {
            return self::$current = null;
        }

        $fresh = (new User())->findActiveForSession((int) $session['id']);
        if ($fresh === null) {
            Session::forget('user');
            self::$revoked = true;
            Session::flash('danger', 'Tài khoản của bạn đã bị khóa hoặc không còn tồn tại.');
            return self::$current = null;
        }

        Session::set('user', $fresh);
        return self::$current = $fresh;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::user()['role'] ?? null, $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (!self::$revoked) {   // đã có thông báo "tài khoản bị khóa" thì không thêm thông báo thứ hai
                Session::flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            }
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        self::$current = null;
        self::$loaded = false;
        Session::destroy();
    }
}
