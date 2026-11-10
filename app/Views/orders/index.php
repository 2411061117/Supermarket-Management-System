<?php $filtered = $q !== '' || $method !== '' || $status !== '' || $from !== '' || $to !== '' || ($isAdmin && $userId > 0); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/pos') ?>" class="btn btn-success"><i class="bi bi-cart3"></i> Bán hàng</a>
</div>

<?php if ($notice !== ''): ?>
    <div class="alert alert-info py-2"><?= e($notice) ?></div>
<?php endif; ?>

<form method="get" action="<?= url('/orders') ?>" class="row g-2 mb-3 no-print">
    <div class="col-12 col-md-3">
        <input type="search" name="q" class="form-control" value="<?= e($q) ?>" maxlength="30" placeholder="Mã hóa đơn (HD-…)" aria-label="Mã hóa đơn">
    </div>
    <?php if ($isAdmin): ?>
        <div class="col-6 col-md-3">
            <select name="user_id" class="form-select" aria-label="Thu ngân">
                <option value="">Tất cả thu ngân</option>
                <?php foreach ($users as $uid => $uname): ?>
                    <option value="<?= e($uid) ?>" <?= (int) $uid === $userId ? 'selected' : '' ?>><?= e($uname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-6 col-md-3">
        <select name="method" class="form-select" aria-label="Phương thức thanh toán">
            <option value="">Mọi phương thức</option>
            <?php foreach ($methods as $code => $name): ?>
                <option value="<?= e($code) ?>" <?= $code === $method ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select name="status" class="form-select" aria-label="Trạng thái">
            <option value="">Mọi trạng thái</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <input type="date" name="from" class="form-control" value="<?= e($from) ?>" aria-label="Từ ngày" title="Từ ngày">
    </div>
    <div class="col-6 col-md-3">
        <input type="date" name="to" class="form-control" value="<?= e($to) ?>" aria-label="Đến ngày" title="Đến ngày">
    </div>
    <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-outline-success"><i class="bi bi-search"></i></button>
        <?php if ($filtered): ?>
            <a href="<?= url('/orders') ?>" class="btn btn-outline-secondary text-nowrap">Xóa lọc</a>
        <?php endif; ?>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã hóa đơn</th>
                    <th>Thời gian</th>
                    <th>Thu ngân</th>
                    <th>Khách hàng</th>
                    <th>Thanh toán</th>
                    <th class="text-end">Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr class="<?= $o['status'] === 'cancelled' ? 'text-muted' : '' ?>">
                    <td class="text-nowrap fw-medium"><?= e($o['code']) ?></td>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                    <td><?= e($o['user_name'] ?? '—') ?></td>
                    <td><?= $o['customer_phone'] !== null ? e(($o['customer_name'] ?: 'Khách') . ' · ' . $o['customer_phone']) : '—' ?></td>
                    <td><?= e($o['method_name'] ?? '—') ?></td>
                    <td class="text-end text-nowrap <?= $o['status'] === 'cancelled' ? 'text-decoration-line-through' : '' ?>"><?= money($o['total']) ?></td>
                    <td>
                        <?php if ($o['status'] === 'cancelled'): ?>
                            <span class="badge text-bg-secondary">Đã hủy</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Hoàn thành</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url("/orders/{$o['id']}") ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Xem</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <?= $filtered ? 'Không tìm thấy hóa đơn phù hợp.' : 'Chưa có hóa đơn nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
