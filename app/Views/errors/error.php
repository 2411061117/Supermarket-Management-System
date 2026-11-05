<div class="min-vh-100 d-flex align-items-center justify-content-center p-3 text-center">
    <div>
        <div class="display-1 fw-bold text-success"><?= e($code) ?></div>
        <h1 class="h4 mb-3"><?= e($title) ?></h1>
        <?php if ($code === 419): ?>
            <p class="text-muted">Vui lòng tải lại trang và thử lại.</p>
        <?php endif; ?>
        <a class="btn btn-success" href="<?= url('/') ?>"><i class="bi bi-house"></i> Về trang chủ</a>
    </div>
</div>
