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
use App\Controllers\InventoryController;
use App\Controllers\OrderController;
use App\Controllers\PosController;
use App\Controllers\ProductController;
use App\Controllers\ReceiptController;
use App\Controllers\ReportController;
use App\Controllers\ShiftController;
use App\Controllers\SupplierController;
use App\Controllers\UnitController;
use App\Controllers\UserController;

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

// Nhân viên (khóa/mở khóa qua /toggle, không xóa)
$router->get('/users',                [UserController::class, 'index'],  ['admin']);
$router->get('/users/create',         [UserController::class, 'create'], ['admin']);
$router->post('/users',               [UserController::class, 'store'],  ['admin']);
$router->get('/users/{id}/edit',      [UserController::class, 'edit'],   ['admin']);
$router->post('/users/{id}',          [UserController::class, 'update'], ['admin']);
$router->post('/users/{id}/toggle',   [UserController::class, 'toggle'], ['admin']);

// Tồn kho (chỉ đọc)
$router->get('/inventory',                [InventoryController::class, 'index'],   ['admin', 'warehouse']);
$router->get('/inventory/products/{id}',  [InventoryController::class, 'product'], ['admin', 'warehouse']);

// Phiếu nhập kho (chứng từ: không sửa/xóa). /receipts/create phải khai báo TRƯỚC /receipts/{id}.
$router->get('/receipts',        [ReceiptController::class, 'index'],  ['admin', 'warehouse']);
$router->get('/receipts/create', [ReceiptController::class, 'create'], ['admin', 'warehouse']);
$router->post('/receipts',       [ReceiptController::class, 'store'],  ['admin', 'warehouse']);
$router->get('/receipts/{id}',   [ReceiptController::class, 'show'],  ['admin', 'warehouse']);

// Ca làm việc (mở ca + xem; đóng ca làm ở Tuần 4)
$router->get('/shifts',       [ShiftController::class, 'index'], ['admin', 'cashier']);
$router->post('/shifts/open', [ShiftController::class, 'open'],  ['admin', 'cashier']);

// ---- Tuần 4: bán hàng, hóa đơn, báo cáo. Xem docs/PLAN-W4.md ----
$router->get('/pos', [PosController::class, 'index'], ['admin', 'cashier']);

$router->get('/orders', [OrderController::class, 'index'], ['admin', 'cashier']);

$router->get('/reports', [ReportController::class, 'index'], ['admin']);
