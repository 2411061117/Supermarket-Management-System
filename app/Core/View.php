<?php
namespace App\Core;

class View
{
    /**
     * Render app/Views/{$view}.php bên trong layout.
     * Trong view, biến $content của layout chứa HTML của trang con.
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = self::capture($view, $data);
        echo $layout ? self::capture($layout, $data + ['content' => $content]) : $content;
    }

    /** In app/Views/partials/{$name}.php (không bọc layout). */
    public static function partial(string $name, array $data = []): void
    {
        echo self::capture('partials/' . $name, $data);
    }

    private static function capture(string $view, array $data): string
    {
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View không tồn tại: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }

    public static function abort(int $code): never
    {
        http_response_code($code);
        $titles = [403 => 'Không có quyền truy cập', 404 => 'Không tìm thấy trang', 419 => 'Phiên làm việc hết hạn'];
        self::render('errors/error', ['code' => $code, 'title' => $titles[$code] ?? 'Lỗi'], 'layouts/blank');
        exit;
    }
}
