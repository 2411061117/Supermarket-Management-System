<?php
namespace App\Core;

/**
 * Router đơn giản: hỗ trợ GET/POST, tham số dạng {id}, và giới hạn vai trò.
 *   $router->get('/products/{id}', [ProductController::class, 'show'], ['admin']);
 * Tham số thứ 3: null = công khai, [] = chỉ cần đăng nhập, ['admin',...] = các vai trò được phép.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $action, ?array $roles = []): void
    {
        $this->add('GET', $path, $action, $roles);
    }

    public function post(string $path, array $action, ?array $roles = []): void
    {
        $this->add('POST', $path, $action, $roles);
    }

    private function add(string $method, string $path, array $action, ?array $roles): void
    {
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'pattern', 'action', 'roles');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $base = App::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim(preg_replace('#^/index\.php#', '', $path), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['pattern'], $path, $m)) {
                continue;
            }

            if ($route['roles'] !== null) {
                Auth::requireLogin();
                if ($route['roles'] && !Auth::hasRole(...$route['roles'])) {
                    View::abort(403);
                }
            }
            if ($method === 'POST') {
                Csrf::verify();
            }

            [$class, $fn] = $route['action'];
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            (new $class())->$fn(...$params);
            return;
        }

        View::abort(404);
    }
}
