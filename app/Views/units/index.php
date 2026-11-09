<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Đơn vị tính</h1>
    <a href="<?= url('/units/create') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Thêm đơn vị
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px">#</th>
                    <th>Tên đơn vị</th>
                    <th class="text-end">Số sản phẩm</th>
                    <th class="text-end" style="width: 190px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($units as $i => $unit): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= e($unit['name']) ?></td>
                    <td class="text-end"><?= e($unit['product_count']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("/units/{$unit['id']}/edit") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <?php if ((int) $unit['product_count'] > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled
                                    title="Đang được dùng bởi sản phẩm nên không thể xóa">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        <?php else: ?>
                            <?php partial('delete_button', [
                                'action'  => url("/units/{$unit['id']}/delete"),
                                'message' => 'Xóa đơn vị "' . $unit['name'] . '"?',
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$units): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đơn vị tính nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
