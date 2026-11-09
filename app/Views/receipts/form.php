<?php
/** Form lập phiếu nhập nhiều dòng. Dòng mẫu (template) dùng chỉ số __i__, JS (initRepeater) đánh lại chỉ số 0,1,2… */
$oldItems = old('items', []);
$count = max(1, is_array($oldItems) ? count($oldItems) : 1);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/receipts') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Danh sách</a>
</div>

<form method="post" action="<?= url('/receipts') ?>" novalidate data-once>
    <?= csrf_field() ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <?php partial('field', [
                        'name'     => 'supplier_id',
                        'label'    => 'Nhà cung cấp',
                        'type'     => 'select',
                        'options'  => $suppliers,
                        'required' => true,
                        'help'     => $suppliers ? '' : 'Chưa có nhà cung cấp đang hợp tác. Hãy thêm ở mục Danh mục → Nhà cung cấp.',
                    ]) ?>
                </div>
                <div class="col-md-7">
                    <?php partial('field', [
                        'name'  => 'note',
                        'label' => 'Ghi chú',
                        'attrs' => 'maxlength="255"',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Hàng nhập (mỗi dòng tạo 1 lô, tồn kho tự cộng)</div>
        <div class="card-body bg-body-tertiary" data-repeater data-max="<?= (int) $maxLines ?>">
            <?php if (error('items') !== ''): ?>
                <div class="alert alert-danger py-2"><?= e(error('items')) ?></div>
            <?php endif; ?>
            <?php if (!$products): ?>
                <div class="alert alert-warning py-2">Chưa có sản phẩm đang bán. Hãy thêm ở mục Sản phẩm trước.</div>
            <?php endif; ?>

            <div data-repeater-rows>
                <?php for ($i = 0; $i < $count; $i++): ?>
                    <?php partial('receipt_row', ['i' => $i, 'products' => $products]) ?>
                <?php endfor; ?>
            </div>
            <template data-repeater-template>
                <?php partial('receipt_row', ['i' => '__i__', 'products' => $products]) ?>
            </template>

            <button type="button" class="btn btn-outline-success btn-sm" data-repeater-add>
                <i class="bi bi-plus-lg"></i> Thêm dòng
            </button>
            <span class="text-muted small ms-2">Tối đa <?= (int) $maxLines ?> dòng. HSD để trống nếu hàng không có hạn.</span>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span>Tổng giá trị nhập: <strong data-receipt-total>0 ₫</strong></span>
            <div class="d-flex gap-2">
                <button class="btn btn-success"><i class="bi bi-check-lg"></i> Lưu phiếu nhập</button>
                <a href="<?= url('/receipts') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </div>
    </div>
</form>
