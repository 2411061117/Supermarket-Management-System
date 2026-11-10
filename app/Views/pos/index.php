<h1 class="h4 mb-3"><i class="bi bi-cart3"></i> <?= e($title) ?></h1>
<?php if (!$shift): ?>
    <div class="card border-warning">
        <div class="card-body">
            <h2 class="h5"><i class="bi bi-exclamation-triangle text-warning"></i> Bạn chưa mở ca</h2>
            <p class="mb-3">Cần mở ca làm việc trước khi bán hàng để hóa đơn được gắn vào ca và đối chiếu tiền mặt khi đóng ca.</p>
            <a href="<?= url('/shifts') ?>" class="btn btn-success"><i class="bi bi-clock-history"></i> Đi tới Ca làm việc</a>
        </div>
    </div>
<?php else: ?>
<div data-pos data-admin="<?= $isAdmin ? '1' : '0' ?>"
     data-search-url="<?= e(url('/products/search')) ?>"
     data-customer-url="<?= e(url('/pos/customer')) ?>"
     data-checkout-url="<?= e(url('/pos/checkout')) ?>"
     data-orders-url="<?= e(url('/orders')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <!-- Tìm sản phẩm -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label for="pos-search" class="form-label fw-semibold">Quét mã vạch hoặc tìm sản phẩm <kbd>F2</kbd></label>
                    <input type="search" id="pos-search" class="form-control form-control-lg" autocomplete="off" autofocus
                           maxlength="100" placeholder="Mã vạch, SKU hoặc tên sản phẩm" data-pos-search>
                    <div class="form-text">Enter: thêm ngay nếu khớp chính xác mã vạch/SKU.</div>
                    <div class="list-group mt-3" data-pos-results aria-live="polite"></div>
                </div>
            </div>
        </div>

        <!-- Giỏ hàng + thanh toán -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm" data-pos-cart-card>
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-basket"></i> Giỏ hàng</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-pos-clear>Xóa hết</button>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger py-2 d-none" role="alert" data-pos-alert></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead><tr><th>Sản phẩm</th><th class="text-center" style="width:8.5rem">SL</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                            <tbody data-pos-lines></tbody>
                        </table>
                    </div>
                    <p class="text-muted text-center my-3" data-pos-empty>Chưa có sản phẩm nào.</p>

                    <div class="mb-3">
                        <label for="pos-phone" class="form-label mb-1">Khách hàng (SĐT, tùy chọn)</label>
                        <div class="input-group input-group-sm">
                            <input type="tel" id="pos-phone" class="form-control" maxlength="20" inputmode="numeric" placeholder="Nhập SĐT rồi Enter" data-pos-phone>
                            <button type="button" class="btn btn-outline-secondary" data-pos-phone-btn>Tìm</button>
                        </div>
                        <div class="form-text" data-pos-customer></div>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div class="mb-3">
                            <label for="pos-discount" class="form-label mb-1">Giảm giá (₫)</label>
                            <input type="number" id="pos-discount" class="form-control form-control-sm" min="0" step="1" value="0" inputmode="numeric" data-pos-discount>
                            <div class="invalid-feedback">Giảm giá phải từ 0 đến tạm tính.</div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <div class="form-label mb-1">Phương thức thanh toán</div>
                        <?php foreach ($methods as $i => $m): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pos-method" id="pos-method-<?= e($m['code']) ?>"
                                       value="<?= e($m['code']) ?>" <?= $i === 0 ? 'checked' : '' ?> data-pos-method>
                                <label class="form-check-label" for="pos-method-<?= e($m['code']) ?>"><?= e($m['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3 d-none" data-pos-cash-box>
                        <label for="pos-cash" class="form-label mb-1">Tiền khách đưa (₫)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="pos-cash" class="form-control" min="0" step="1" inputmode="numeric" data-pos-cash>
                            <button type="button" class="btn btn-outline-secondary" data-pos-cash-exact>Đủ tiền</button>
                        </div>
                        <div class="form-text fw-semibold" data-pos-change></div>
                    </div>

                    <dl class="row mb-1">
                        <dt class="col-6 fw-normal">Tạm tính</dt><dd class="col-6 text-end mb-1" data-pos-subtotal>0 ₫</dd>
                        <dt class="col-6 fw-normal">Giảm giá</dt><dd class="col-6 text-end mb-1" data-pos-discount-text>0 ₫</dd>
                        <dt class="col-6 fs-5">Khách phải trả</dt><dd class="col-6 text-end fs-4 fw-bold text-success mb-1" data-pos-total>0 ₫</dd>
                    </dl>
                    <button type="button" class="btn btn-success btn-lg w-100" disabled data-pos-pay>
                        <i class="bi bi-cash-coin"></i> Thanh toán <kbd class="ms-1">F9</kbd>
                    </button>
                </div>
            </div>

            <!-- Hiện sau khi bán xong -->
            <div class="card border-success shadow-sm d-none" data-pos-done>
                <div class="card-body text-center">
                    <div class="display-5 text-success"><i class="bi bi-check-circle-fill"></i></div>
                    <h2 class="h5">Thanh toán thành công</h2>
                    <dl class="row text-start mb-3">
                        <dt class="col-6">Mã hóa đơn</dt><dd class="col-6 text-end" data-pos-done-code></dd>
                        <dt class="col-6">Tổng thanh toán</dt><dd class="col-6 text-end" data-pos-done-total></dd>
                        <dt class="col-6" data-pos-done-change-label>Tiền thối</dt><dd class="col-6 text-end fw-bold" data-pos-done-change></dd>
                        <dt class="col-6 d-none" data-pos-done-points-label>Điểm cộng</dt><dd class="col-6 text-end d-none" data-pos-done-points></dd>
                    </dl>
                    <div class="d-flex gap-2 mb-2">
                        <a href="#" class="btn btn-outline-secondary flex-fill" data-pos-done-view target="_blank" rel="noopener"><i class="bi bi-eye"></i> Xem</a>
                        <a href="#" class="btn btn-outline-secondary flex-fill" data-pos-done-print target="_blank" rel="noopener"><i class="bi bi-printer"></i> In hóa đơn</a>
                    </div>
                    <button type="button" class="btn btn-success w-100" data-pos-new><i class="bi bi-plus-lg"></i> Bán đơn mới</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
