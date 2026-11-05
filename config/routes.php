<?php
/**
 * Khai báo route. Tham số thứ 3 (vai trò):
 *   null                    -> công khai
 *   [] (mặc định)           -> chỉ cần đăng nhập
 *   ['admin', 'cashier']    -> chỉ các vai trò này
 *
 * @var App\Core\Router $router
 */
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PlaceholderController;

// Xác thực
$router->get('/login',  [AuthController::class, 'showLogin'], null);
$router->post('/login', [AuthController::class, 'login'], null);
$router->post('/logout', [AuthController::class, 'logout']);

// Tổng quan – mọi nhân viên đã đăng nhập
$router->get('/', [DashboardController::class, 'index']);

// Các module (thay PlaceholderController bằng controller thật ở các tuần sau)
// Tuần 2 – trang tạm, lần lượt thay bằng controller thật (xem docs/PLAN-W2.md)
$router->get('/products',   [PlaceholderController::class, 'products'],   ['admin']);
$router->get('/categories', [PlaceholderController::class, 'categories'], ['admin']);
$router->get('/units',      [PlaceholderController::class, 'units'],      ['admin']);
$router->get('/suppliers',  [PlaceholderController::class, 'suppliers'],  ['admin']);
$router->get('/users',     [PlaceholderController::class, 'users'],     ['admin']);
$router->get('/inventory', [PlaceholderController::class, 'inventory'], ['admin', 'warehouse']);
$router->get('/pos',       [PlaceholderController::class, 'pos'],       ['admin', 'cashier']);
$router->get('/reports',   [PlaceholderController::class, 'reports'],   ['admin']);
