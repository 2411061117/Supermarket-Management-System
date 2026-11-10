<?php $cancelled = $order['status'] === 'cancelled'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        Hóa đơn <?= e($order['code']) ?>
        <?= $cancelled ? '<span class="badge text-bg-secondary align-middle">Đã hủy</span>' : '<span class="badge text-bg-success align-middle">Hoàn thành</span>' ?>
    </h1>
    <div class="d-flex flex-wrap gap-2 no-print">
        <a href="<?= url("/orders/{$order['id']}/print") ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> In hóa đơn</a>
        <?php if ($canCancel): ?>
            <?php partial('delete_button', [
                'action'  => url("/orders/{$order['id']}/cancel"),
                'message' => 'Hủy hóa đơn ' . $order['code'] . '? Hàng sẽ được hoàn lại đúng lô, điểm tích lũy bị trừ. Không thể hoàn tác.',
                'label'   => 'Hủy hóa đơn', 'icon' => 'x-circle',
            ]) ?>
        <?php endif; ?>
        <a href="<?= url('/orders') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Danh sách</a>
    </div>
</div>

<?php if (!$cancelled && !$canCancel && $order['status'] === 'completed' && $order['shift_status'] !== 'open' && \App\Core\Auth::hasRole('admin')): ?>
    <div class="alert alert-light border py-2 no-print">Ca của hóa đơn đã đóng nên không thể hủy.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Thời gian</dt>
            <dd class="col-sm-9"><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></dd>
            <dt class="col-sm-3">Thu ngân</dt>
            <dd class="col-sm-9"><?= e($order['user_name'] ?? '—') ?></dd>
            <dt class="col-sm-3">Khách hàng</dt>
            <dd class="col-sm-9"><?= $order['customer_phone'] !== null ? e(($order['customer_name'] ?: 'Khách') . ' · ' . $order['customer_phone']) : '—' ?></dd>
            <dt class="col-sm-3">Thanh toán</dt>
            <dd class="col-sm-9 mb-0"><?= e($order['method_name'] ?? '—') ?><?= $order['paid_amount'] !== null ? ' · ' . money($order['paid_amount']) : '' ?></dd>
        </dl>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-end">SL</th><th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lines as $n => $l): ?>
                <tr>
                    <td><?= $n + 1 ?></td>
                    <td>
                        <div><?= e($l['name']) ?> <small class="text-muted"><?= e($l['sku']) ?></small></div>
                        <small class="text-muted">Lô:
                            <?= e(implode('; ', array_map(
                                fn($b) => $b['code'] . ' (' . $b['qty'] . ($b['expiry'] ? ', HSD ' . date('d/m/Y', strtotime($b['expiry'])) : '') . ')',
                                $l['batches']
                            ))) ?></small>
                    </td>
                    <td class="text-end text-nowrap"><?= money($l['unit_price']) ?></td>
                    <td class="text-end text-nowrap"><?= e($l['qty']) ?> <?= e($l['unit']) ?></td>
                    <td class="text-end text-nowrap"><?= money($l['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="4" class="text-end fw-normal">Tạm tính</th><td class="text-end text-nowrap"><?= money($order['subtotal']) ?></td></tr>
                <tr><th colspan="4" class="text-end fw-normal">Giảm giá</th><td class="text-end text-nowrap"><?= (int) $order['discount'] > 0 ? '− ' . money($order['discount']) : money(0) ?></td></tr>
                <tr class="table-light"><th colspan="4" class="text-end">Tổng thanh toán</th><th class="text-end text-nowrap"><?= money($order['total']) ?></th></tr>
            </tfoot>
        </table>
    </div>
</div>
