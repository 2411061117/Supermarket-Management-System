<?php
/**
 * Ô nhập liệu Bootstrap kèm hiển thị lỗi validate.
 *   partial('field', ['name' => 'name', 'label' => 'Tên', 'value' => $row['name'] ?? '', 'required' => true]);
 * Bắt buộc: $name, $label.
 * Tùy chọn: $type (text|number|email|password|textarea|select|... mặc định text), $value (giá trị hiện tại của bản ghi;
 *           dữ liệu vừa nhập sai - old() - được ưu tiên), $required, $options (cho select: [giá trị => nhãn]),
 *           $help (ghi chú nhỏ), $attrs (thuộc tính HTML thêm, do code truyền vào - KHÔNG truyền input người dùng).
 */
$type     = $type ?? 'text';
$required = $required ?? false;
$current  = $type === 'password' ? '' : old($name, $value ?? '');
$id       = 'f_' . $name;
?>
<div class="mb-3">
    <label class="form-label" for="<?= e($id) ?>">
        <?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
    </label>

    <?php if ($type === 'select'): ?>
        <select class="form-select<?= invalid($name) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?> <?= $attrs ?? '' ?>>
            <option value="">— Chọn —</option>
            <?php foreach (($options ?? []) as $optValue => $optLabel): ?>
                <option value="<?= e($optValue) ?>" <?= (string) $optValue === (string) $current ? 'selected' : '' ?>>
                    <?= e($optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($type === 'textarea'): ?>
        <textarea class="form-control<?= invalid($name) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="3"
                  <?= $required ? 'required' : '' ?> <?= $attrs ?? '' ?>><?= e($current) ?></textarea>
    <?php else: ?>
        <input class="form-control<?= invalid($name) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>"
               value="<?= e($current) ?>" <?= $required ? 'required' : '' ?> <?= $attrs ?? '' ?>>
    <?php endif; ?>

    <?php if (error($name) !== ''): ?>
        <div class="invalid-feedback"><?= e(error($name)) ?></div>
    <?php endif; ?>
    <?php if (!empty($help)): ?>
        <div class="form-text"><?= e($help) ?></div>
    <?php endif; ?>
</div>
