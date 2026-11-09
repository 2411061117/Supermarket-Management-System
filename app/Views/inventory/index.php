<?php
/** Tồn kho theo sản phẩm. Thẻ cảnh báo ở trên bấm vào để lọc. */
$cards = [
    ['out',      'Hết hàng',            'text-danger',  'x-circle'],
    ['low',      'Tồn thấp',            'text-warning', 'exclamation-triangle'],
    ['expiring', 'Sắp hết hạn (≤ ' . App\Models\Batch::WARN_DAYS . ' ngày)', 'text-warning', 'hourglass-split'],
    ['expired',  'Đã hết hạn còn trong kho', 'text-danger', 'calendar-x'],
];
?>
<h1 class="h4 mb-3">Tồn kho</h1>

<div class="row g-3 mb-3">
    <?php foreach ($cards as [$key, $label, $color, $icon]): ?>
        <div class="col-6 col-xl-3">
            <a href="<?= url('/inventory?status=' . $key) ?>" class="text-decoration-none">
                <div class="card stat-card border-0 shadow-sm h-100 <?= $status === $key ? 'border-success' : '' ?>">
                    <div class="card-body py-2">
                        <div class="text-muted small"><i class="bi bi-<?= $icon ?>"></i> <?= e($label) ?></div>
                        <div class="fs-4 fw-semibold <?= $alerts[$key] > 0 ? $color : 'text-body' ?>"><?= e($alerts[$key]) ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="mb-3">
    <?php partial('search_bar', [
        'action'      => url('/inventory'),
        'q'           => $q,
        'placeholder' => 'Tìm theo tên, SKU hoặc mã vạch',
        'selects'     => [
            'category_id' => ['Tất cả loại hàng', $categories, $categoryId > 0 ? $categoryId : ''],
            'status'      => ['Tất cả tình trạng', $statuses, $status],
        ],
    ]) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Loại hàng</th>
                    <th>ĐVT</th>
                    <th class="text-end">Tồn bán được</th>
                    <th class="text-end">Tối thiểu</th>
                    <th>Tình trạng</th>
                    <th class="text-end">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $stock = (int) $r['stock'];
                $out = $stock === 0;
                $low = $stock <= (int) $r['min_stock'];
                ?>
                <tr class="<?= (int) $r['is_active'] === 1 ? '' : 'text-muted' ?>">
                    <td class="text-nowrap"><?= e($r['sku']) ?></td>
                    <td>
                        <?= e($r['name']) ?>
                        <?php if ((int) $r['is_active'] !== 1): ?><span class="badge text-bg-secondary">Ngừng bán</span><?php endif; ?>
                    </td>
                    <td><?= e($r['category_name']) ?></td>
                    <td><?= e($r['unit_name']) ?></td>
                    <td class="text-end fw-semibold <?= $out ? 'text-danger' : ($low ? 'text-warning' : '') ?>"><?= e($stock) ?></td>
                    <td class="text-end"><?= e($r['min_stock']) ?></td>
                    <td>
                        <?php if ($out): ?>
                            <span class="badge text-bg-danger">Hết hàng</span>
                        <?php elseif ($low): ?>
                            <span class="badge text-bg-warning">Tồn thấp</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Đủ hàng</span>
                        <?php endif; ?>
                        <?php if ((int) $r['expiring_qty'] > 0): ?>
                            <span class="badge text-bg-warning">Sắp hết hạn: <?= e($r['expiring_qty']) ?></span>
                        <?php endif; ?>
                        <?php if ((int) $r['expired_qty'] > 0): ?>
                            <span class="badge text-bg-danger">Hết hạn: <?= e($r['expired_qty']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url("/inventory/products/{$r['id']}") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-boxes"></i> Xem lô
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <?= ($q !== '' || $categoryId > 0 || $status !== '') ? 'Không tìm thấy sản phẩm phù hợp.' : 'Chưa có dữ liệu tồn kho.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <small class="text-muted">Tồn bán được chỉ tính lô còn hạn.</small>
</div>
<?php partial('pagination', ['paginator' => $paginator]) ?>
