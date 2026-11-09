<?php
/** Form dùng chung thêm/sửa (có upload ảnh). $product = null khi thêm mới. */
$action = $product ? url("/products/{$product['id']}") : url('/products');
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card border-0 shadow-sm mx-auto" style="max-width: 760px">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'sku',
                        'label'    => 'Mã SKU',
                        'value'    => $product['sku'] ?? '',
                        'required' => true,
                        'help'     => 'Chữ không dấu, số, gạch dưới (tự viết hoa). Ví dụ: DU001.',
                        'attrs'    => 'maxlength="30" autofocus',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'  => 'barcode',
                        'label' => 'Mã vạch',
                        'value' => $product['barcode'] ?? '',
                        'help'  => 'Chỉ gồm chữ số. Để trống nếu sản phẩm không có mã vạch.',
                        'attrs' => 'maxlength="30" inputmode="numeric"',
                    ]) ?>
                </div>
            </div>

            <?php partial('field', [
                'name'     => 'name',
                'label'    => 'Tên sản phẩm',
                'value'    => $product['name'] ?? '',
                'required' => true,
                'attrs'    => 'maxlength="150"',
            ]) ?>

            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'category_id',
                        'label'    => 'Loại hàng',
                        'type'     => 'select',
                        'value'    => $product['category_id'] ?? '',
                        'options'  => $categories,
                        'required' => true,
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'unit_id',
                        'label'    => 'Đơn vị tính',
                        'type'     => 'select',
                        'value'    => $product['unit_id'] ?? '',
                        'options'  => $units,
                        'required' => true,
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'sale_price',
                        'label'    => 'Giá bán (₫)',
                        'type'     => 'number',
                        'value'    => $product['sale_price'] ?? '',
                        'required' => true,
                        'help'     => 'Số nguyên, đơn vị đồng, không dùng dấu chấm phân cách.',
                        'attrs'    => 'min="0" max="999999999" step="1" inputmode="numeric"',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'min_stock',
                        'label'    => 'Tồn tối thiểu',
                        'type'     => 'number',
                        'value'    => $product['min_stock'] ?? 10,
                        'required' => true,
                        'help'     => 'Tồn kho dưới mức này sẽ bị cảnh báo.',
                        'attrs'    => 'min="0" max="1000000" step="1" inputmode="numeric"',
                    ]) ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="f_image">Ảnh sản phẩm</label>
                <?php if (!empty($product['image'])): ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?= e(asset($product['image'])) ?>" alt="" width="72" height="72" class="rounded object-fit-cover border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1"
                                   <?= old('remove_image') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remove_image">Gỡ ảnh hiện tại</label>
                        </div>
                    </div>
                <?php endif; ?>
                <input class="form-control<?= invalid('image') ?>" type="file" id="f_image" name="image"
                       accept="image/jpeg,image/png,image/webp">
                <?php if (error('image') !== ''): ?>
                    <div class="invalid-feedback"><?= e(error('image')) ?></div>
                <?php endif; ?>
                <div class="form-text">
                    JPG, PNG hoặc WEBP, tối đa 2 MB.
                    <?= !empty($product['image']) ? 'Chọn ảnh mới để thay thế ảnh hiện tại.' : '' ?>
                    Nếu form báo lỗi, bạn cần chọn lại ảnh.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="<?= url('/products') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
