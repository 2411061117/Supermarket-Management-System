<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Sản phẩm</h1>
    <a href="<?= url('/products/create') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Thêm sản phẩm
    </a>
</div>

<div class="mb-3">
    <?php partial('search_bar', [
        'action'      => url('/products'),
        'q'           => $q,
        'placeholder' => 'Tìm theo tên, SKU hoặc mã vạch',
        'selects'     => [
            'category_id' => ['Tất cả loại hàng', $categories, $categoryId > 0 ? $categoryId : ''],
            'status'      => ['Tất cả trạng thái', ['active' => 'Đang bán', 'inactive' => 'Ngừng bán'], $status],
        ],
    ]) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 64px">Ảnh</th>
                    <th>SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Loại hàng</th>
                    <th>ĐVT</th>
                    <th class="text-end">Giá bán</th>
                    <th class="text-end">Tồn tối thiểu</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 270px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <?php $active = (int) $p['is_active'] === 1; ?>
                <tr class="<?= $active ? '' : 'text-muted' ?>">
                    <td>
                        <?php if ($p['image']): ?>
                            <img src="<?= e(asset($p['image'])) ?>" alt="" width="40" height="40" class="rounded object-fit-cover">
                        <?php else: ?>
                            <span class="d-inline-flex align-items-center justify-content-center bg-light rounded" style="width: 40px; height: 40px">
                                <i class="bi bi-image text-muted"></i>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= e($p['sku']) ?></td>
                    <td>
                        <div class="fw-medium"><?= e($p['name']) ?></div>
                        <?php if ($p['barcode'] !== null): ?>
                            <div class="small text-muted"><i class="bi bi-upc"></i> <?= e($p['barcode']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['category_name']) ?></td>
                    <td><?= e($p['unit_name']) ?></td>
                    <td class="text-end text-nowrap"><?= money($p['sale_price']) ?></td>
                    <td class="text-end"><?= e($p['min_stock']) ?></td>
                    <td>
                        <?php if ($active): ?>
                            <span class="badge text-bg-success">Đang bán</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Ngừng bán</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("/products/{$p['id']}/edit") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <?php partial('delete_button', [
                            'action'  => url("/products/{$p['id']}/toggle"),
                            'message' => ($active ? 'Ngừng bán "' : 'Mở bán lại "') . $p['name'] . '"?',
                            'label'   => $active ? 'Ngừng bán' : 'Mở bán',
                            'icon'    => $active ? 'pause-circle' : 'play-circle',
                            'class'   => $active ? 'btn-outline-warning' : 'btn-outline-success',
                        ]) ?>
                        <?php partial('delete_button', [
                            'action'  => url("/products/{$p['id']}/delete"),
                            'message' => 'Xóa sản phẩm "' . $p['name'] . '"? (Chỉ xóa được khi chưa có lô hàng nào.)',
                        ]) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <?= ($q !== '' || $categoryId > 0 || $status !== '') ? 'Không tìm thấy sản phẩm phù hợp.' : 'Chưa có sản phẩm nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
