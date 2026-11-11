<?php
/** Chi tiết một ca: thông tin, doanh thu, tiền mặt dự kiến/thực tế, danh sách hóa đơn. */
$isOpen = $shift['status'] === 'open';
$min = (int) $shift['minutes'];
$duration = $min >= 60 ? intdiv($min, 60) . ' giờ ' . ($min % 60) . ' phút' : $min . ' phút';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        Ca #<?= (int) $shift['id'] ?>
        <?= $isOpen ? '<span class="badge text-bg-success align-middle">Đang mở</span>' : '<span class="badge text-bg-secondary align-middle">Đã đóng</span>' ?>
    </h1>
    <a href="<?= url('/shifts') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Danh sách ca</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-5">Nhân viên</dt>
                <dd class="col-sm-7"><?= e($shift['user_name']) ?> <span class="text-muted small">(<?= e($shift['username']) ?>)</span></dd>
                <dt class="col-sm-5">Mở lúc</dt>
                <dd class="col-sm-7"><?= e(date('d/m/Y H:i', strtotime($shift['opened_at']))) ?></dd>
                <dt class="col-sm-5">Đóng lúc</dt>
                <dd class="col-sm-7"><?= $shift['closed_at'] === null ? '—' : e(date('d/m/Y H:i', strtotime($shift['closed_at']))) ?></dd>
                <dt class="col-sm-5">Thời lượng</dt>
                <dd class="col-sm-7"><?= e($duration) ?></dd>
                <dt class="col-sm-5">Ghi chú</dt>
                <dd class="col-sm-7 mb-0"><?= ($shift['note'] ?? '') !== '' ? e($shift['note']) : '—' ?></dd>
            </dl>
        </div></div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-6">Số hóa đơn</dt>
                <dd class="col-sm-6"><?= (int) $summary['orders_count'] ?><?php if ($summary['cancelled_count'] > 0): ?> <span class="text-muted small">(+<?= (int) $summary['cancelled_count'] ?> đã hủy)</span><?php endif; ?></dd>
                <dt class="col-sm-6">Doanh thu</dt>
                <dd class="col-sm-6 fw-semibold"><?= money($summary['revenue']) ?></dd>
                <?php foreach ($summary['methods'] as $m): ?>
                    <dt class="col-sm-6 fw-normal text-muted">— <?= e($m['name']) ?></dt>
                    <dd class="col-sm-6"><?= money($m['amount']) ?></dd>
                <?php endforeach; ?>
                <dt class="col-sm-6">Tiền đầu ca</dt>
                <dd class="col-sm-6"><?= money($shift['opening_cash']) ?></dd>
                <dt class="col-sm-6">Tiền mặt <?= $isOpen ? 'dự kiến hiện tại' : 'dự kiến lúc đóng' ?></dt>
                <dd class="col-sm-6"><?= money($expected) ?></dd>
                <dt class="col-sm-6">Tiền mặt đếm thực tế</dt>
                <dd class="col-sm-6"><?= $shift['closing_cash'] === null ? '—' : money($shift['closing_cash']) ?></dd>
                <dt class="col-sm-6">Chênh lệch</dt>
                <dd class="col-sm-6 mb-0">
                    <?php if ($difference === null): ?>—
                    <?php else: ?>
                        <span class="fw-semibold <?= $difference === 0 ? 'text-success' : 'text-danger' ?>"><?= $difference > 0 ? '+' : ($difference < 0 ? '−' : '') ?><?= money(abs($difference)) ?></span>
                        <span class="text-muted small"><?= $difference === 0 ? '(khớp)' : ($difference > 0 ? '(thừa tiền)' : '(thiếu tiền)') ?></span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div></div>
    </div>
</div>

<h2 class="h5 mb-3">Hóa đơn của ca</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Mã hóa đơn</th><th>Thời gian</th><th>Thanh toán</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th class="text-end">Chi tiết</th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): $cancelled = $o['status'] === 'cancelled'; ?>
                <tr class="<?= $cancelled ? 'text-muted' : '' ?>">
                    <td class="text-nowrap fw-medium"><?= e($o['code']) ?></td>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                    <td><?= e($o['method_name'] ?? '—') ?></td>
                    <td class="text-end text-nowrap <?= $cancelled ? 'text-decoration-line-through' : '' ?>"><?= money($o['total']) ?></td>
                    <td><?= $cancelled ? '<span class="badge text-bg-secondary">Đã hủy</span>' : '<span class="badge text-bg-success">Hoàn thành</span>' ?></td>
                    <td class="text-end"><a href="<?= url('/orders/' . (int) $o['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Xem</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Ca này chưa có hóa đơn.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
