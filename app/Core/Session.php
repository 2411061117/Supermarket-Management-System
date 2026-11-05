<?php
namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
            session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Thông báo hiển thị 1 lần (sau redirect). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /**
     * Lưu lỗi validate + dữ liệu form đã nhập để hiển thị lại ở request kế tiếp (sau redirect).
     * Chỉ bị xóa khi view thực sự đọc (errors()/old()), nên redirect trung gian hay trang 404 không làm mất.
     */
    public static function flashErrors(array $errors, array $old): void
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $old;
    }

    private static ?array $errors = null;
    private static ?array $old = null;

    public static function errors(): array
    {
        if (self::$errors === null) {
            self::$errors = $_SESSION['_errors'] ?? [];
            unset($_SESSION['_errors']);
        }
        return self::$errors;
    }

    public static function old(): array
    {
        if (self::$old === null) {
            self::$old = $_SESSION['_old'] ?? [];
            unset($_SESSION['_old']);
        }
        return self::$old;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
