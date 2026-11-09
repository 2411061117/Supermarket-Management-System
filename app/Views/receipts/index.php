<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Phiếu nhập kho</h1>
    <a href="<?= url('/receipts/create') ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Lập phiếu nhập</a>
</div>

<?php if ($notice !== ''): ?>
    <div class="alert alert-info py-2"><?= e($notice) ?></div>
<?php endif; ?>

<form method="get" action="<?= url('/receipts') ?>" class="row g-2 mb-3 no-print">
    <div class="col-12 col-md-3">
        <input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Mã phiếu (PN-…)" aria-label="Mã phiếu">
    </div>
    <div class="col-12 col-md-3">
        <select name="supplier_id" class="form-select" aria-label="Nhà cung cấp">
            <option value="">Tất cả nhà cung cấp</option>
            <?php foreach ($suppliers as $sid => $sname): ?>
                <option value="<?= e($sid) ?>" <?= (int) $sid === $supplierId ? 'selected' : '' ?>><?= e($sname) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <input type="date" name="from" class="form-control" value="<?= e($from) ?>" aria-label="Từ ngày" title="Từ ngày">
    </div>
    <div class="col-6 col-md-2">
        <input type="date" name="to" class="form-control" value="<?= e($to) ?>" aria-label="Đến ngày" title="Đến ngày">
    </div>
    <div class="col-12 col-md-2 d-flex gap-2">
        <button class="btn btn-outline-success"><i class="bi bi-search"></i></button>
        <?php if ($q !== '' || $supplierId > 0 || $from !== '' || $to !== ''): ?>
            <a href="<?= url('/receipts') ?>" class="btn btn-outline-secondary text-nowrap">Xóa lọc</a>
        <?php endif; ?>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã phiếu</th>
                    <th>Ngày lập</th>
                    <th>Nhà cung cấp</th>
                    <th>Người lập</th>
                    <th class="text-end">Số dòng</th>
                    <th class="text-end">Tổng giá trị</th>
                    <th class="text-end">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($receipts as $r): ?>
                <tr>
                    <td class="text-nowrap fw-medium"><?= e($r['code']) ?></td>
                    <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                    <td><?= e($r['supplier_name']) ?></td>
                    <td><?= e($r['user_name']) ?></td>
                    <td class="text-end"><?= e($r['line_count']) ?></td>
                    <td class="text-end text-nowrap"><?= money($r['total_cost']) ?></td>
                    <td class="text-end">
                        <a href="<?= url("/receipts/{$r['id']}") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Xem
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$receipts): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <?= ($q !== '' || $supplierId > 0 || $from !== '' || $to !== '') ? 'Không tìm thấy phiếu nhập phù hợp.' : 'Chưa có phiếu nhập nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
