<?php $today = date('Y-m-d'); $warn = date('Y-m-d', strtotime("+$warnDays days")); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0"><?= e($product['name']) ?></h1>
        <div class="text-muted small">
            SKU <?= e($product['sku']) ?> · <?= e($product['category_name']) ?> · ĐVT: <?= e($product['unit_name']) ?>
            · Tồn tối thiểu: <?= e($product['min_stock']) ?>
            <?php if ((int) $product['is_active'] !== 1): ?><span class="badge text-bg-secondary">Ngừng bán</span><?php endif; ?>
        </div>
    </div>
    <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Về tồn kho</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Các lô (bán theo thứ tự từ trên xuống — HSD gần nhất trước)</span>
        <?php if ($includeEmpty): ?>
            <a href="<?= url("/inventory/products/{$product['id']}") ?>" class="small">Ẩn lô đã hết hàng</a>
        <?php else: ?>
            <a href="<?= url("/inventory/products/{$product['id']}?all=1") ?>" class="small">Hiện cả lô đã hết hàng</a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã lô</th>
                    <th>HSD</th>
                    <th class="text-end">SL nhập</th>
                    <th class="text-end">Còn lại</th>
                    <th class="text-end">Giá nhập</th>
                    <th>Ngày nhập</th>
                    <th>Tình trạng</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
                <?php
                $exp = $b['expiry_date'];
                $expired = $exp !== null && $exp < $today;
                $soon = $exp !== null && !$expired && $exp <= $warn;
                $empty = (int) $b['qty_remaining'] === 0;
                ?>
                <tr class="<?= $empty ? 'text-muted' : '' ?>">
                    <td><?= e($b['batch_code']) ?></td>
                    <td class="text-nowrap <?= $expired ? 'text-danger fw-semibold' : ($soon ? 'text-warning fw-semibold' : '') ?>">
                        <?= $exp === null ? '—' : e(date('d/m/Y', strtotime($exp))) ?>
                    </td>
                    <td class="text-end"><?= e($b['qty_received']) ?></td>
                    <td class="text-end fw-semibold"><?= e($b['qty_remaining']) ?></td>
                    <td class="text-end text-nowrap"><?= money($b['cost_price']) ?></td>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($b['received_at']))) ?></td>
                    <td>
                        <?php if ($empty): ?>
                            <span class="badge text-bg-secondary">Đã hết hàng</span>
                        <?php elseif ($expired): ?>
                            <span class="badge text-bg-danger">Đã hết hạn</span>
                        <?php elseif ($soon): ?>
                            <span class="badge text-bg-warning">Sắp hết hạn</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Còn hạn</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$batches): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Sản phẩm này chưa có lô nào còn hàng.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
