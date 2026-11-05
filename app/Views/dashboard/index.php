<h1 class="h4 mb-4">Tổng quan hôm nay</h1>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Doanh thu hôm nay</div>
                <div class="fs-4 fw-semibold"><?= money($revenue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Số hóa đơn</div>
                <div class="fs-4 fw-semibold"><?= e($orderCount) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Sản phẩm sắp hết hàng</div>
                <div class="fs-4 fw-semibold text-warning"><?= count($lowStock) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Lô sắp hết hạn (≤ 7 ngày)</div>
                <div class="fs-4 fw-semibold text-danger"><?= count($expiring) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Tồn kho thấp</div>
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
            <div class="card-header bg-white fw-semibold">Hàng sắp hết hạn</div>
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
