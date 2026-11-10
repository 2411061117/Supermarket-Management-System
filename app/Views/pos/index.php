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
    <div class="alert alert-info" data-pos data-shift="<?= (int) $shift['id'] ?>">
        <i class="bi bi-cone-striped"></i> Giao diện bán hàng sẽ được xây dựng ở <strong>Bước 2</strong>.
        Đang trong ca #<?= (int) $shift['id'] ?>.
    </div>
<?php endif; ?>
