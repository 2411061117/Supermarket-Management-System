<div class="page-header">
    <div>
        <h1 class="page-title">Tổng quan hôm nay</h1>
        <p class="page-sub">Theo dõi doanh thu, tồn kho và hạn sử dụng trong một màn hình.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-success d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Mở menu"><i class="bi bi-list"></i></button>
        <?php \App\Core\View::partial('userbar'); ?>
    </div>
</div>

<?php if (!$hasOpenShift): ?>
    <div class="hero-banner">
        <div>
            <h2>Bạn chưa mở ca làm việc</h2>
            <p>Mở ca để bắt đầu bán hàng và ghi nhận doanh thu.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('/shifts') ?>" class="btn btn-light fw-semibold">Mở ca</a>
            <?php if (App\Core\Auth::hasRole('admin')): ?>
                <a href="<?= url('/reports') ?>" class="btn btn-outline-light">Xem báo cáo</a>
            <?php endif; ?>
        </div>
    </div>
<?php elseif (App\Core\Auth::hasRole('admin')): ?>
    <div class="hero-banner">
        <div>
            <h2>Ca làm việc đang mở</h2>
            <p>Bạn có thể bán hàng và theo dõi doanh thu theo thời gian thực.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('/pos') ?>" class="btn btn-light fw-semibold">Bán hàng</a>
            <a href="<?= url('/reports') ?>" class="btn btn-outline-light">Xem báo cáo</a>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-tile">
            <div class="label">Doanh thu hôm nay</div>
            <div class="value"><?= money($revenue) ?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-tile">
            <div class="label">Số hóa đơn</div>
            <div class="value"><?= e($orderCount) ?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-tile <?= count($lowStock) ? 'warn' : '' ?>">
            <div class="label">Sản phẩm sắp hết hàng</div>
            <div class="value"><?= count($lowStock) ?></div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-tile <?= count($expiring) ? 'danger' : '' ?>">
            <div class="label">Lô sắp hết hạn (≤ 7 ngày)</div>
            <div class="value"><?= count($expiring) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="panel h-100">
            <div class="panel-head">
                <span>Tồn kho thấp</span>
                <?php if (App\Core\Auth::hasRole('admin', 'warehouse')): ?>
                    <a href="<?= url('/inventory?status=low') ?>">Xem tồn kho →</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table panel-table mb-0 align-middle">
                    <thead><tr><th>SKU</th><th>Sản phẩm</th><th class="text-end">Tồn / Tối thiểu</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                        <tr>
                            <td class="text-muted"><?= e($p['sku']) ?></td>
                            <td><?= e($p['name']) ?></td>
                            <td class="text-end"><span class="fw-bold <?= $p['stock'] == 0 ? 'text-danger' : 'text-warning' ?>"><?= e($p['stock']) ?></span> / <?= e($p['min_stock']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Không có sản phẩm nào tồn thấp</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><span>Hàng sắp hết hạn</span></div>
            <?php if (!$expiring): ?>
                <div class="empty-ok">
                    <div class="check">✓</div>
                    <div class="fw-semibold">Mọi thứ đều ổn</div>
                    <div class="text-muted small">Không có lô nào sắp hết hạn.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table panel-table mb-0 align-middle">
                        <thead><tr><th>Lô</th><th>Sản phẩm</th><th>HSD</th><th class="text-end">Còn</th></tr></thead>
                        <tbody>
                        <?php foreach ($expiring as $b): ?>
                            <tr>
                                <td class="text-muted"><?= e($b['batch_code']) ?></td>
                                <td><?= e($b['name']) ?></td>
                                <td class="<?= $b['expiry_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= e(date('d/m/Y', strtotime($b['expiry_date']))) ?></td>
                                <td class="text-end"><?= e($b['qty_remaining']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
