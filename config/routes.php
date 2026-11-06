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
use App\Controllers\ProductController;
use App\Controllers\SupplierController;
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

// Nhà cung cấp (xóa nếu chưa có phiếu nhập, nếu không thì "ngừng hợp tác" qua /toggle)
$router->get('/suppliers',                [SupplierController::class, 'index'],   ['admin']);
$router->get('/suppliers/create',         [SupplierController::class, 'create'],  ['admin']);
$router->post('/suppliers',               [SupplierController::class, 'store'],   ['admin']);
$router->get('/suppliers/{id}/edit',      [SupplierController::class, 'edit'],    ['admin']);
$router->post('/suppliers/{id}',          [SupplierController::class, 'update'],  ['admin']);
$router->post('/suppliers/{id}/toggle',   [SupplierController::class, 'toggle'],  ['admin']);
$router->post('/suppliers/{id}/delete',   [SupplierController::class, 'destroy'], ['admin']);

// Sản phẩm (xóa nếu chưa có lô hàng, nếu không thì "ngừng bán" qua /toggle)
// /products/search trả JSON cho POS (tuần 4) và form nhập kho (tuần 3) nên mở cho cả thu ngân và NV kho.
// Phải khai báo trước /products/{id}/... để không bị nhầm "search" là {id}.
$router->get('/products/search',          [ProductController::class, 'search'],  ['admin', 'cashier', 'warehouse']);
$router->get('/products',                 [ProductController::class, 'index'],   ['admin']);
$router->get('/products/create',          [ProductController::class, 'create'],  ['admin']);
$router->post('/products',                [ProductController::class, 'store'],   ['admin']);
$router->get('/products/{id}/edit',       [ProductController::class, 'edit'],    ['admin']);
$router->post('/products/{id}',           [ProductController::class, 'update'],  ['admin']);
$router->post('/products/{id}/toggle',    [ProductController::class, 'toggle'],  ['admin']);
$router->post('/products/{id}/delete',    [ProductController::class, 'destroy'], ['admin']);

// Chưa làm – trang tạm, thay dần bằng controller thật (thay PlaceholderController)
$router->get('/users',     [PlaceholderController::class, 'users'],     ['admin']);
$router->get('/inventory', [PlaceholderController::class, 'inventory'], ['admin', 'warehouse']);
$router->get('/pos',       [PlaceholderController::class, 'pos'],       ['admin', 'cashier']);
$router->get('/reports',   [PlaceholderController::class, 'reports'],   ['admin']);
