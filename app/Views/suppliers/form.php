<?php
/** Form dùng chung thêm/sửa. $supplier = null khi thêm mới. */
$action = $supplier ? url("/suppliers/{$supplier['id']}") : url('/suppliers');
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" novalidate>
            <?= csrf_field() ?>
            <?php partial('field', [
                'name'     => 'name',
                'label'    => 'Tên nhà cung cấp',
                'value'    => $supplier['name'] ?? '',
                'required' => true,
                'attrs'    => 'maxlength="150" autofocus',
            ]) ?>
            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'  => 'contact',
                        'label' => 'Người liên hệ',
                        'value' => $supplier['contact'] ?? '',
                        'attrs' => 'maxlength="100"',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'  => 'phone',
                        'label' => 'Số điện thoại',
                        'type'  => 'tel',
                        'value' => $supplier['phone'] ?? '',
                        'attrs' => 'maxlength="20"',
                        'help'  => 'Dạng 0912345678 hoặc +84912345678.',
                    ]) ?>
                </div>
            </div>
            <?php partial('field', [
                'name'  => 'email',
                'label' => 'Email',
                'type'  => 'email',
                'value' => $supplier['email'] ?? '',
                'attrs' => 'maxlength="100"',
            ]) ?>
            <?php partial('field', [
                'name'  => 'address',
                'label' => 'Địa chỉ',
                'value' => $supplier['address'] ?? '',
                'attrs' => 'maxlength="255"',
            ]) ?>
            <div class="d-flex gap-2">
                <button class="btn btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="<?= url('/suppliers') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
