<?php
/** Báo cáo: bộ lọc ngày, thẻ tổng, doanh thu theo ngày (biểu đồ cột CSS), theo phương thức, theo thu ngân, top 10 sản phẩm. */
$fmtDay = fn(string $d): string => date('d/m/Y', strtotime($d));
$presets = [
    'Hôm nay'     => [date('Y-m-d'), date('Y-m-d')],
    '7 ngày'      => [date('Y-m-d', strtotime('-6 day')), date('Y-m-d')],
    '30 ngày'     => [date('Y-m-d', strtotime('-29 day')), date('Y-m-d')],
    'Tháng này'   => [date('Y-m-01'), date('Y-m-d')],
];
$topTable = function (array $rows, string $emptyText): void { ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Sản phẩm</th><th class="text-end">Số lượng</th><th class="text-end">Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($r['name']) ?> <span class="text-muted small"><?= e($r['sku']) ?></span></td>
                    <td class="text-end"><?= number_format((int) $r['qty'], 0, ',', '.') ?></td>
                    <td class="text-end text-nowrap"><?= money($r['revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-3"><?= e($emptyText) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php };
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<?php foreach ($notices as $n): ?>
    <div class="alert alert-info py-2"><?= e($n) ?></div>
<?php endforeach; ?>

<form method="get" action="<?= url('/reports') ?>" class="row g-2 mb-2 no-print align-items-end">
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rp-from">Từ ngày</label>
        <input type="date" id="rp-from" name="from" class="form-control" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="rp-to">Đến ngày</label>
        <input type="date" id="rp-to" name="to" class="form-control" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>">
    </div>
    <div class="col-12 col-md-auto"><button class="btn btn-success"><i class="bi bi-bar-chart"></i> Xem báo cáo</button></div>
</form>
<div class="mb-3 no-print small">
    <?php foreach ($presets as $label => [$f, $t]): ?>
        <a href="<?= url('/reports?from=' . $f . '&to=' . $t) ?>" class="btn btn-sm btn-outline-secondary"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<p class="text-muted">Số liệu từ <strong><?= e($fmtDay($from)) ?></strong> đến <strong><?= e($fmtDay($to)) ?></strong> (chỉ hóa đơn hoàn thành, không tính hóa đơn đã hủy).</p>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Doanh thu', money($summary['revenue']), ''],
        ['Số hóa đơn', number_format($summary['orders_count'], 0, ',', '.'), ''],
        ['Trung bình / hóa đơn', money($summary['average']), ''],
        ['Lợi nhuận gộp', money($summary['profit']), $summary['profit'] < 0 ? 'text-danger' : 'text-success'],
    ];
    foreach ($cards as [$label, $value, $cls]): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small"><?= e($label) ?></div>
                <div class="fs-4 fw-semibold <?= $cls ?>"><?= e($value) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
<p class="small text-muted mt-n2 mb-4">Lợi nhuận gộp = doanh thu − giá vốn theo lô đã bán (giá vốn: <?= money($summary['cost']) ?>).</p>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Doanh thu theo ngày</div>
    <div class="card-body">
        <div class="d-flex align-items-end gap-1" style="height:160px" role="img" aria-label="Biểu đồ cột doanh thu theo ngày">
            <?php foreach ($byDay as $d => $row): $h = $row['revenue'] > 0 ? max(2, (int) round($row['revenue'] / $maxDay * 100)) : 0; ?>
                <div class="flex-fill d-flex align-items-end" style="min-width:2px;height:100%" title="<?= e($fmtDay($d) . ': ' . money($row['revenue']) . ' · ' . $row['orders_count'] . ' hóa đơn') ?>">
                    <div class="bg-success w-100 rounded-top" style="height:<?= $h ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between small text-muted mt-1"><span><?= e($fmtDay($from)) ?></span><span><?= e($fmtDay($to)) ?></span></div>
    </div>
    <div class="table-responsive" style="max-height:320px">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Ngày</th><th class="text-end">Số hóa đơn</th><th class="text-end">Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach (array_reverse($byDay, true) as $d => $row): ?>
                <tr class="<?= $row['orders_count'] === 0 ? 'text-muted' : '' ?>">
                    <td><?= e($fmtDay($d)) ?></td>
                    <td class="text-end"><?= $row['orders_count'] ?></td>
                    <td class="text-end text-nowrap"><?= money($row['revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Theo phương thức thanh toán</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Phương thức</th><th class="text-end">Số hóa đơn</th><th class="text-end">Số tiền</th></tr></thead>
                    <tbody>
                    <?php foreach ($byMethod as $m): ?>
                        <tr><td><?= e($m['name']) ?></td><td class="text-end"><?= (int) $m['orders_count'] ?></td><td class="text-end text-nowrap"><?= money($m['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$byMethod): ?><tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Theo thu ngân</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Thu ngân</th><th class="text-end">Số hóa đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                    <tbody>
                    <?php foreach ($byCashier as $c): ?>
                        <tr><td><?= e($c['name']) ?></td><td class="text-end"><?= (int) $c['orders_count'] ?></td><td class="text-end text-nowrap"><?= money($c['revenue']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$byCashier): ?><tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top 10 bán chạy theo số lượng</div>
            <?php $topTable($topByQty, 'Chưa có dữ liệu'); ?>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top 10 theo doanh thu</div>
            <?php $topTable($topByRevenue, 'Chưa có dữ liệu'); ?>
        </div>
    </div>
</div>
<p class="small text-muted mt-2">Doanh thu sản phẩm = số lượng × đơn giá, chưa trừ giảm giá cấp hóa đơn.</p>
