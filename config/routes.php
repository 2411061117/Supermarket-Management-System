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
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\PlaceholderController;
use App\Controllers\UnitController;

// Xác thực
$router->get('/login',  [AuthController::class, 'showLogin'], null);
$router->post('/login', [AuthController::class, 'login'], null);
$router->post('/logout', [AuthController::class, 'logout']);

// Tổng quan – mọi nhân viên đã đăng nhập
$router->get('/', [DashboardController::class, 'index']);

// ---- Tuần 2: danh mục (chỉ admin). Xem docs/PLAN-W2.md ----
// Loại hàng
$router->get('/categories',              [CategoryController::class, 'index'],   ['admin']);
$router->get('/categories/create',       [CategoryController::class, 'create'],  ['admin']);
$router->post('/categories',             [CategoryController::class, 'store'],   ['admin']);
$router->get('/categories/{id}/edit',    [CategoryController::class, 'edit'],    ['admin']);
$router->post('/categories/{id}',        [CategoryController::class, 'update'],  ['admin']);
$router->post('/categories/{id}/delete', [CategoryController::class, 'destroy'], ['admin']);

// Đơn vị tính
$router->get('/units',                [UnitController::class, 'index'],   ['admin']);
$router->get('/units/create',         [UnitController::class, 'create'],  ['admin']);
$router->post('/units',               [UnitController::class, 'store'],   ['admin']);
$router->get('/units/{id}/edit',      [UnitController::class, 'edit'],    ['admin']);
$router->post('/units/{id}',          [UnitController::class, 'update'],  ['admin']);
$router->post('/units/{id}/delete',   [UnitController::class, 'destroy'], ['admin']);

// Chưa làm – trang tạm, thay dần bằng controller thật (thay PlaceholderController)
$router->get('/products',  [PlaceholderController::class, 'products'],  ['admin']);
$router->get('/suppliers', [PlaceholderController::class, 'suppliers'], ['admin']);
$router->get('/users',     [PlaceholderController::class, 'users'],     ['admin']);
$router->get('/inventory', [PlaceholderController::class, 'inventory'], ['admin', 'warehouse']);
$router->get('/pos',       [PlaceholderController::class, 'pos'],       ['admin', 'cashier']);
$router->get('/reports',   [PlaceholderController::class, 'reports'],   ['admin']);
