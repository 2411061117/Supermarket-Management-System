<?php
namespace App\Core;

use App\Models\User;

/** Xác thực nhân viên và kiểm tra vai trò (RBAC). */
class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $user = (new User())->findActiveByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);   // chống session fixation
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

    public static function user(): ?array
    {
        return Session::get('user');
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
            Session::flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
