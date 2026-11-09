<?php
use App\Core\App;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Shift;

$user = Auth::user();
// [đường dẫn, icon, nhãn, các vai trò thấy menu, (tùy chọn) mục con [[đường dẫn, nhãn], ...]]
// Mục có mục con là nhóm xổ xuống; đường dẫn của nhóm chỉ để đặt tên, không điều hướng.
$menu = [
    ['/',          'speedometer2', 'Tổng quan',     ['admin', 'cashier', 'warehouse']],
    ['/pos',       'cart3',        'Bán hàng (POS)', ['admin', 'cashier']],
    ['/shifts',    'clock-history', 'Ca làm việc',   ['admin', 'cashier']],
    ['#warehouse', 'box-seam',     'Kho hàng',       ['admin', 'warehouse'], [
        ['/inventory', 'Tồn kho'],
        ['/receipts',  'Phiếu nhập'],
    ]],
    ['/products',  'tags',         'Sản phẩm',       ['admin']],
    ['#catalog',   'folder2-open', 'Danh mục',       ['admin'], [
        ['/categories', 'Loại hàng'],
        ['/units',      'Đơn vị tính'],
        ['/suppliers',  'Nhà cung cấp'],
    ]],
    ['/users',     'people',       'Nhân viên',      ['admin']],
    ['/reports',   'bar-chart',    'Báo cáo',        ['admin']],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? '') ?> · <?= e(App::config('app.name')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-dark bg-success sticky-top px-3">
    <button class="btn btn-outline-light d-lg-none me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Mở menu">
        <i class="bi bi-list"></i>
    </button>
    <a class="navbar-brand fw-semibold me-auto" href="<?= url('/') ?>">
        <i class="bi bi-shop"></i> <?= e(App::config('app.name')) ?>
    </a>
    <?php if (Auth::hasRole('admin', 'cashier')): ?>
        <?php $myShift = (new Shift())->findOpenByUser((int) $user['id']); ?>
        <a href="<?= url('/shifts') ?>" class="btn btn-sm me-2 <?= $myShift ? 'btn-light text-success' : 'btn-warning' ?>">
            <i class="bi bi-clock-history"></i>
            <?= $myShift ? 'Ca mở từ ' . e(date('H:i', strtotime($myShift['opened_at']))) : 'Chưa mở ca' ?>
        </a>
    <?php endif; ?>
    <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i>
            <span class="d-none d-sm-inline"><?= e($user['full_name']) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted"><?= e($user['role_name']) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="post" action="<?= url('/logout') ?>">
                    <?= csrf_field() ?>
                    <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start sidebar bg-white border-end" id="sidebar" tabindex="-1">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <nav class="nav nav-pills flex-column p-2">
            <?php foreach ($menu as $i => $item): ?>
                <?php
                [$href, $icon, $label, $roles] = $item;
                $children = $item[4] ?? [];
                if (!Auth::hasRole(...$roles)) {
                    continue;
                }
                ?>
                <?php if ($children): ?>
                    <?php $open = array_filter($children, fn($c) => active($c[0]) !== ''); ?>
                    <a class="nav-link d-flex justify-content-between align-items-center <?= $open ? 'fw-semibold text-success' : '' ?>"
                       data-bs-toggle="collapse" href="#menu-group-<?= $i ?>" role="button"
                       aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="menu-group-<?= $i ?>">
                        <span><i class="bi bi-<?= $icon ?> me-2"></i><?= e($label) ?></span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse <?= $open ? 'show' : '' ?>" id="menu-group-<?= $i ?>">
                        <div class="nav flex-column ms-3">
                            <?php foreach ($children as [$childHref, $childLabel]): ?>
                                <a class="nav-link py-1 <?= active($childHref) ?>" href="<?= url($childHref) ?>">
                                    <?= e($childLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="nav-link <?= active($href) ?>" href="<?= url($href) ?>">
                        <i class="bi bi-<?= $icon ?> me-2"></i><?= e($label) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="flex-grow-1 p-3 p-md-4 min-vw-0">
        <?php foreach (Session::pullFlash() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
