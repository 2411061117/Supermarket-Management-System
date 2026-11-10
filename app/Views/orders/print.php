<?php $cancelled = $order['status'] === 'cancelled'; ?>
<div class="receipt">
    <div class="text-center mb-2">
        <div class="fw-bold fs-6"><?= e(App\Core\App::config('app.name')) ?></div>
        <div class="fw-semibold">HÓA ĐƠN BÁN HÀNG</div>
        <?php if ($cancelled): ?><div class="fw-bold border border-dark d-inline-block px-2 mt-1">ĐÃ HỦY</div><?php endif; ?>
    </div>
    <div>Mã: <strong><?= e($order['code']) ?></strong></div>
    <div>Ngày: <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></div>
    <div>Thu ngân: <?= e($order['user_name'] ?? '—') ?></div>
    <?php if ($order['customer_phone'] !== null): ?>
        <div>Khách: <?= e(($order['customer_name'] ?: 'Khách') . ' · ' . $order['customer_phone']) ?></div>
    <?php endif; ?>
    <hr class="my-2">
    <?php foreach ($lines as $l): ?>
        <div class="fw-semibold"><?= e($l['name']) ?></div>
        <div class="d-flex justify-content-between">
            <span><?= e($l['qty']) ?> × <?= money($l['unit_price']) ?></span>
            <span><?= money($l['amount']) ?></span>
        </div>
    <?php endforeach; ?>
    <hr class="my-2">
    <div class="d-flex justify-content-between"><span>Tạm tính</span><span><?= money($order['subtotal']) ?></span></div>
    <?php if ((int) $order['discount'] > 0): ?>
        <div class="d-flex justify-content-between"><span>Giảm giá</span><span>− <?= money($order['discount']) ?></span></div>
    <?php endif; ?>
    <div class="d-flex justify-content-between fw-bold fs-6"><span>TỔNG</span><span><?= money($order['total']) ?></span></div>
    <div class="d-flex justify-content-between"><span>Thanh toán</span><span><?= e($order['method_name'] ?? '—') ?></span></div>
    <hr class="my-2">
    <div class="text-center">Cảm ơn quý khách!</div>
</div>

<div class="text-center my-3 no-print">
    <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> In hóa đơn</button>
    <a href="<?= url("/orders/{$order['id']}") ?>" class="btn btn-outline-secondary">Quay lại</a>
</div>
