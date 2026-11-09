<div class="page-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1>Tổng quan hôm nay</h1>
        <p>Chúc bạn một ngày bán hàng thật hiệu quả ✨</p>
    </div>
    <span class="hero-date"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-muted small fw-medium mb-1">Doanh thu hôm nay</div>
                    <div class="stat-value"><?= money($revenue) ?></div>
                </div>
                <span class="stat-icon s-violet"><i class="bi bi-cash-coin"></i></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-muted small fw-medium mb-1">Số hóa đơn</div>
                    <div class="stat-value"><?= e($orderCount) ?></div>
                </div>
                <span class="stat-icon s-pink"><i class="bi bi-receipt"></i></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-muted small fw-medium mb-1">Sản phẩm sắp hết hàng</div>
                    <div class="stat-value text-warning"><?= count($lowStock) ?></div>
                </div>
                <span class="stat-icon s-amber"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-muted small fw-medium mb-1">Lô sắp hết hạn (≤ 7 ngày)</div>
                    <div class="stat-value text-danger"><?= count($expiring) ?></div>
                </div>
                <span class="stat-icon s-rose"><i class="bi bi-hourglass-split"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header"><i class="bi bi-box-seam text-primary me-1"></i> Tồn kho thấp</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead><tr><th>SKU</th><th>Sản phẩm</th><th class="text-end">Tồn</th><th class="text-end">Tối thiểu</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                        <tr>
                            <td><?= e($p['sku']) ?></td>
                            <td><?= e($p['name']) ?></td>
                            <td class="text-end fw-semibold <?= $p['stock'] == 0 ? 'text-danger' : 'text-warning' ?>"><?= e($p['stock']) ?></td>
                            <td class="text-end"><?= e($p['min_stock']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Không có sản phẩm nào tồn thấp</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header"><i class="bi bi-clock-history text-danger me-1"></i> Hàng sắp hết hạn</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead><tr><th>Lô</th><th>Sản phẩm</th><th>HSD</th><th class="text-end">Còn</th></tr></thead>
                    <tbody>
                    <?php foreach ($expiring as $b): ?>
                        <tr>
                            <td><?= e($b['batch_code']) ?></td>
                            <td><?= e($b['name']) ?></td>
                            <td class="<?= $b['expiry_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>">
                                <?= e(date('d/m/Y', strtotime($b['expiry_date']))) ?>
                            </td>
                            <td class="text-end"><?= e($b['qty_remaining']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$expiring): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Không có lô nào sắp hết hạn</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
