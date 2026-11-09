<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Phiếu nhập <?= e($receipt['code']) ?></h1>
    <div class="d-flex gap-2 no-print">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> In</button>
        <a href="<?= url('/receipts') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Danh sách</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ngày lập</dt>
            <dd class="col-sm-9"><?= e(date('d/m/Y H:i', strtotime($receipt['created_at']))) ?></dd>
            <dt class="col-sm-3">Nhà cung cấp</dt>
            <dd class="col-sm-9">
                <?= e($receipt['supplier_name']) ?>
                <?php if ($receipt['supplier_phone'] !== null): ?><span class="text-muted">· <?= e($receipt['supplier_phone']) ?></span><?php endif; ?>
            </dd>
            <dt class="col-sm-3">Người lập</dt>
            <dd class="col-sm-9"><?= e($receipt['user_name']) ?></dd>
            <dt class="col-sm-3">Ghi chú</dt>
            <dd class="col-sm-9 mb-0"><?= ($receipt['note'] ?? '') !== '' ? e($receipt['note']) : '—' ?></dd>
        </dl>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Sản phẩm</th>
                    <th>Mã lô</th>
                    <th>HSD</th>
                    <th class="text-end">Số lượng</th>
                    <th class="text-end">Giá nhập</th>
                    <th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $n => $i): ?>
                <tr>
                    <td><?= $n + 1 ?></td>
                    <td class="text-nowrap"><?= e($i['sku']) ?></td>
                    <td><?= e($i['product_name']) ?></td>
                    <td><?= e($i['batch_code']) ?></td>
                    <td class="text-nowrap"><?= $i['expiry_date'] === null ? '—' : e(date('d/m/Y', strtotime($i['expiry_date']))) ?></td>
                    <td class="text-end"><?= e($i['qty_received']) ?> <?= e($i['unit_name']) ?></td>
                    <td class="text-end text-nowrap"><?= money($i['cost_price']) ?></td>
                    <td class="text-end text-nowrap"><?= money($i['cost_price'] * $i['qty_received']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Phiếu không có dòng nào.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="7" class="text-end">Tổng giá trị nhập</th>
                    <th class="text-end text-nowrap"><?= money($total) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
