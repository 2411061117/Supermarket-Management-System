<?php
/**
 * 1 dòng hàng của form lập phiếu nhập. $i = chỉ số dòng ('__i__' khi là mẫu cho JS), $products = [id => nhãn].
 * Tên input items[$i][field]; dữ liệu cũ/lỗi tra theo đường dẫn "items.$i.field".
 */
$f = fn(string $field) => "items.$i.$field";
$val = fn(string $field) => old($f($field), '');
$input = function (string $field, string $label, string $type, string $attrs = '') use ($i, $f, $val) {
    ?>
    <label class="form-label small mb-1"><?= e($label) ?></label>
    <input type="<?= e($type) ?>" name="items[<?= e($i) ?>][<?= e($field) ?>]" value="<?= e(is_string($val($field)) ? $val($field) : '') ?>"
           class="form-control form-control-sm<?= invalid($f($field)) ?>" <?= $attrs ?>>
    <?php if (error($f($field)) !== ''): ?><div class="invalid-feedback"><?= e(error($f($field))) ?></div><?php endif; ?>
    <?php
};
?>
<div class="border rounded p-2 mb-2 bg-white" data-repeater-row>
    <div class="row g-2 align-items-start">
        <div class="col-12 col-md-4">
            <label class="form-label small mb-1">Sản phẩm <span class="text-danger">*</span> <span class="text-muted">(dòng <span data-repeater-index></span>)</span></label>
            <select name="items[<?= e($i) ?>][product_id]" class="form-select form-select-sm<?= invalid($f('product_id')) ?>">
                <option value="">— Chọn sản phẩm —</option>
                <?php foreach ($products as $pid => $plabel): ?>
                    <option value="<?= e($pid) ?>" <?= (string) $pid === (string) $val('product_id') ? 'selected' : '' ?>><?= e($plabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (error($f('product_id')) !== ''): ?><div class="invalid-feedback"><?= e(error($f('product_id'))) ?></div><?php endif; ?>
        </div>
        <div class="col-6 col-md-2"><?php $input('batch_code', 'Mã lô *', 'text', 'maxlength="50"'); ?></div>
        <div class="col-6 col-md-2"><?php $input('expiry_date', 'HSD (nếu có)', 'date', 'min="' . date('Y-m-d') . '"'); ?></div>
        <div class="col-4 col-md-1"><?php $input('qty', 'SL *', 'number', 'min="1" max="1000000" step="1" inputmode="numeric" data-line-qty'); ?></div>
        <div class="col-6 col-md-2"><?php $input('cost_price', 'Giá nhập (₫) *', 'number', 'min="0" max="999999999" step="1" inputmode="numeric" data-line-cost'); ?></div>
        <div class="col-2 col-md-1 text-end pt-md-4">
            <button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove aria-label="Xóa dòng"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</div>
