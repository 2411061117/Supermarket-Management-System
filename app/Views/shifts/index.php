<?php
/** Ca làm việc: thẻ trạng thái ca hiện tại (mở ca nếu chưa có) + danh sách ca. */
$fmtDuration = function (int $min): string {
    return $min >= 60 ? intdiv($min, 60) . ' giờ ' . ($min % 60) . ' phút' : $min . ' phút';
};
?>
<h1 class="h4 mb-3">Ca làm việc</h1>

<?php if ($notice !== ''): ?>
    <div class="alert alert-info py-2"><?= e($notice) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4" style="max-width: 560px">
    <div class="card-body">
        <?php if ($current): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge text-bg-success">Đang mở</span>
                <span class="fw-semibold">Ca của bạn</span>
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-5">Mở lúc</dt>
                <dd class="col-sm-7"><?= e(date('d/m/Y H:i', strtotime($current['opened_at']))) ?> (đã <?= e($fmtDuration((int) $current['minutes_open'])) ?>)</dd>
                <dt class="col-sm-5">Tiền đầu ca</dt>
                <dd class="col-sm-7"><?= money($current['opening_cash']) ?></dd>
                <dt class="col-sm-5">Ghi chú</dt>
                <dd class="col-sm-7 mb-0"><?= ($current['note'] ?? '') !== '' ? e($current['note']) : '—' ?></dd>
            </dl>
            <hr>
            <form method="post" action="<?= url('/shifts/' . (int) $current['id'] . '/close') ?>" novalidate data-once
                  onsubmit="return confirm('Đóng ca hiện tại? Sau khi đóng không thể mở lại.')">
                <?= csrf_field() ?>
                <div class="mb-2">Tiền mặt dự kiến trong két: <strong><?= money($expected) ?></strong></div>
                <?php partial('field', [
                    'name'     => 'closing_cash',
                    'label'    => 'Tiền mặt đếm thực tế cuối ca (₫)',
                    'type'     => 'number',
                    'value'    => (string) $expected,
                    'required' => true,
                    'help'     => 'Đếm tiền trong két và nhập số thực tế. Hệ thống ghi nhận chênh lệch nếu có.',
                    'attrs'    => 'min="0" max="999999999" step="1" inputmode="numeric"',
                ]) ?>
                <button class="btn btn-danger"><i class="bi bi-stop-circle"></i> Đóng ca</button>
            </form>
        <?php else: ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge text-bg-secondary">Chưa mở ca</span>
                <span class="fw-semibold">Mở ca mới</span>
            </div>
            <form method="post" action="<?= url('/shifts/open') ?>" novalidate data-once>
                <?= csrf_field() ?>
                <?php partial('field', [
                    'name'     => 'opening_cash',
                    'label'    => 'Tiền đầu ca (₫)',
                    'type'     => 'number',
                    'value'    => '0',
                    'required' => true,
                    'help'     => 'Số tiền mặt có trong két lúc bắt đầu ca. Số nguyên, không dùng dấu chấm phân cách.',
                    'attrs'    => 'min="0" max="999999999" step="1" inputmode="numeric"',
                ]) ?>
                <?php partial('field', [
                    'name'  => 'note',
                    'label' => 'Ghi chú',
                    'attrs' => 'maxlength="255"',
                ]) ?>
                <button class="btn btn-success"><i class="bi bi-play-circle"></i> Mở ca</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<h2 class="h5 mb-3"><?= $isAdmin ? 'Tất cả các ca' : 'Lịch sử ca của tôi' ?></h2>

<form method="get" action="<?= url('/shifts') ?>" class="row g-2 mb-3 no-print">
    <?php if ($isAdmin): ?>
        <div class="col-12 col-md-3">
            <select name="user_id" class="form-select" aria-label="Nhân viên">
                <option value="">Tất cả nhân viên</option>
                <?php foreach ($users as $uid => $uname): ?>
                    <option value="<?= e($uid) ?>" <?= (int) $uid === $userId ? 'selected' : '' ?>><?= e($uname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-12 col-md-2">
        <select name="status" class="form-select" aria-label="Trạng thái">
            <option value="">Tất cả trạng thái</option>
            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Đang mở</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
        </select>
    </div>
    <div class="col-6 col-md-2"><input type="date" name="from" class="form-control" value="<?= e($from) ?>" aria-label="Từ ngày" title="Từ ngày"></div>
    <div class="col-6 col-md-2"><input type="date" name="to" class="form-control" value="<?= e($to) ?>" aria-label="Đến ngày" title="Đến ngày"></div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-outline-success"><i class="bi bi-search"></i></button>
        <?php if (($isAdmin && $userId > 0) || $status !== '' || $from !== '' || $to !== ''): ?>
            <a href="<?= url('/shifts') ?>" class="btn btn-outline-secondary text-nowrap">Xóa lọc</a>
        <?php endif; ?>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <?php if ($isAdmin): ?><th>Nhân viên</th><?php endif; ?>
                    <th>Mở lúc</th>
                    <th>Đóng lúc</th>
                    <th>Thời lượng</th>
                    <th class="text-end">Tiền đầu ca</th>
                    <th class="text-end">Tiền cuối ca</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($shifts as $s): ?>
                <tr data-shift-row>
                    <td><?= e($s['id']) ?></td>
                    <?php if ($isAdmin): ?><td><?= e($s['user_name']) ?> <span class="text-muted small">(<?= e($s['username']) ?>)</span></td><?php endif; ?>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($s['opened_at']))) ?></td>
                    <td class="text-nowrap"><?= $s['closed_at'] === null ? '—' : e(date('d/m/Y H:i', strtotime($s['closed_at']))) ?></td>
                    <td class="text-nowrap"><?= e($fmtDuration((int) $s['minutes'])) ?></td>
                    <td class="text-end text-nowrap"><?= money($s['opening_cash']) ?></td>
                    <td class="text-end text-nowrap"><?= $s['closing_cash'] === null ? '—' : money($s['closing_cash']) ?></td>
                    <td>
                        <?php if ($s['status'] === 'open'): ?>
                            <span class="badge text-bg-success">Đang mở</span>
                            <?php if ($isAdmin && (!$current || (int) $current['id'] !== (int) $s['id'])): ?>
                                <form method="post" action="<?= url('/shifts/' . (int) $s['id'] . '/close') ?>" class="d-inline-flex gap-1 mt-1" data-once
                                      onsubmit="return confirm('Đóng hộ ca #<?= (int) $s['id'] ?> với số tiền đã nhập?')">
                                    <?= csrf_field() ?>
                                    <input type="number" name="closing_cash" class="form-control form-control-sm" style="width:9rem" min="0" max="999999999" step="1" required placeholder="Tiền cuối ca" aria-label="Tiền cuối ca">
                                    <button class="btn btn-sm btn-outline-danger text-nowrap">Đóng ca</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Đã đóng</span>
                        <?php endif; ?>
                    </td>
                    <td><?= ($s['note'] ?? '') !== '' ? e($s['note']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$shifts): ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 9 : 8 ?>" class="text-center text-muted py-4">
                        <?= (($isAdmin && $userId > 0) || $status !== '' || $from !== '' || $to !== '') ? 'Không tìm thấy ca phù hợp.' : 'Chưa có ca nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
