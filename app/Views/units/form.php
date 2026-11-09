<?php
/** Form dùng chung thêm/sửa. $unit = null khi thêm mới. */
$action = $unit ? url("/units/{$unit['id']}") : url('/units');
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card border-0 shadow-sm" style="max-width: 520px">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" novalidate>
            <?= csrf_field() ?>
            <?php partial('field', [
                'name'     => 'name',
                'label'    => 'Tên đơn vị',
                'value'    => $unit['name'] ?? '',
                'required' => true,
                'help'     => 'Ví dụ: chai, lon, gói, hộp.',
                'attrs'    => 'maxlength="50" autofocus',
            ]) ?>
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="<?= url('/units') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
