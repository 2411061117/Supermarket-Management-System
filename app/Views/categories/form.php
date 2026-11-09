<?php
/** Form dùng chung thêm/sửa. $category = null khi thêm mới. */
$action = $category ? url("/categories/{$category['id']}") : url('/categories');
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card border-0 shadow-sm mx-auto" style="max-width: 520px">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" novalidate>
            <?= csrf_field() ?>
            <?php partial('field', [
                'name'     => 'name',
                'label'    => 'Tên loại hàng',
                'value'    => $category['name'] ?? '',
                'required' => true,
                'attrs'    => 'maxlength="100" autofocus',
            ]) ?>
            <?php partial('field', [
                'name'  => 'description',
                'label' => 'Mô tả',
                'type'  => 'textarea',
                'value' => $category['description'] ?? '',
                'attrs' => 'maxlength="255"',
                'help'  => 'Không bắt buộc, tối đa 255 ký tự.',
            ]) ?>
            <div class="d-flex gap-2">
                <button class="btn btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="<?= url('/categories') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
