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
    ['/orders',    'receipt',      'Hóa đơn',        ['admin', 'cashier']],
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
<body class="app-body">
<div class="app-shell">
    <aside class="offcanvas-lg offcanvas-start sidebar" id="sidebar" tabindex="-1">
        <div class="sidebar-brand">
            <a href="<?= url('/') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-mark"><?= e(mb_strtoupper(mb_substr(App::config('app.name'), 0, 1))) ?></span>
                <span class="brand-name">Quản lý<br>cửa hàng</span>
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Đóng"></button>
        </div>
        <nav class="nav flex-column sidebar-nav">
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
                    <a class="nav-link <?= $open ? 'is-current' : '' ?>"
                       data-bs-toggle="collapse" href="#menu-group-<?= $i ?>" role="button"
                       aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="menu-group-<?= $i ?>">
                        <span class="nav-dot"></span><span class="flex-grow-1"><?= e($label) ?></span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse <?= $open ? 'show' : '' ?>" id="menu-group-<?= $i ?>">
                        <div class="nav flex-column sub-nav">
                            <?php foreach ($children as [$childHref, $childLabel]): ?>
                                <a class="nav-link <?= active($childHref) ?>" href="<?= url($childHref) ?>"><?= e($childLabel) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="nav-link <?= active($href) ?>" href="<?= url($href) ?>">
                        <span class="nav-dot"></span><?= e($label) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="app-main">
        <?php if (empty($userbarInline)): ?>
            <div class="d-flex align-items-center mb-3">
                <button class="btn btn-outline-success d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Mở menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-auto"><?php App\Core\View::partial('userbar'); ?></div>
            </div>
        <?php endif; ?>

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
