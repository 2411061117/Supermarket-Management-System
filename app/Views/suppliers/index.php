<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Nhà cung cấp</h1>
    <a href="<?= url('/suppliers/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Thêm nhà cung cấp
    </a>
</div>

<div class="mb-3">
    <?php partial('search_bar', [
        'action'      => url('/suppliers'),
        'q'           => $q,
        'placeholder' => 'Tìm theo tên, người liên hệ, SĐT, email',
        'selects'     => [
            'status' => ['Tất cả trạng thái', ['active' => 'Đang hợp tác', 'inactive' => 'Ngừng hợp tác'], $status],
        ],
    ]) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên nhà cung cấp</th>
                    <th>Liên hệ</th>
                    <th>SĐT</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 300px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <?php $active = (int) $s['is_active'] === 1; ?>
                <tr class="<?= $active ? '' : 'text-muted' ?>">
                    <td class="fw-medium"><?= e($s['name']) ?></td>
                    <td><?= e($s['contact']) ?></td>
                    <td class="text-nowrap"><?= e($s['phone']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['address']) ?></td>
                    <td>
                        <?php if ($active): ?>
                            <span class="badge text-bg-success">Đang hợp tác</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Ngừng hợp tác</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("/suppliers/{$s['id']}/edit") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <?php partial('delete_button', [
                            'action'  => url("/suppliers/{$s['id']}/toggle"),
                            'message' => ($active ? 'Ngừng hợp tác với "' : 'Kích hoạt lại "') . $s['name'] . '"?',
                            'label'   => $active ? 'Ngừng' : 'Kích hoạt',
                            'icon'    => $active ? 'pause-circle' : 'play-circle',
                            'class'   => $active ? 'btn-outline-warning' : 'btn-outline-success',
                        ]) ?>
                        <?php if ((int) $s['receipt_count'] > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled
                                    title="Đã có <?= e($s['receipt_count']) ?> phiếu nhập nên không thể xóa">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        <?php else: ?>
                            <?php partial('delete_button', [
                                'action'  => url("/suppliers/{$s['id']}/delete"),
                                'message' => 'Xóa nhà cung cấp "' . $s['name'] . '"?',
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$suppliers): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <?= ($q !== '' || $status !== '') ? 'Không tìm thấy nhà cung cấp phù hợp.' : 'Chưa có nhà cung cấp nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
