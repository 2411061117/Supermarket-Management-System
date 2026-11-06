<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Loại hàng</h1>
    <a href="<?= url('/categories/create') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Thêm loại hàng
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px">#</th>
                    <th>Tên loại hàng</th>
                    <th>Mô tả</th>
                    <th class="text-end">Số sản phẩm</th>
                    <th class="text-end" style="width: 190px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $i => $category): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= e($category['name']) ?></td>
                    <td class="text-muted"><?= e($category['description']) ?></td>
                    <td class="text-end"><?= e($category['product_count']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("/categories/{$category['id']}/edit") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <?php if ((int) $category['product_count'] > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled
                                    title="Đang có sản phẩm thuộc loại này nên không thể xóa">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        <?php else: ?>
                            <?php partial('delete_button', [
                                'action'  => url("/categories/{$category['id']}/delete"),
                                'message' => 'Xóa loại hàng "' . $category['name'] . '"?',
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có loại hàng nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
