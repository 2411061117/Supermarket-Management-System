<?php use App\Core\Session; ?>
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="card shadow-sm login-card w-100">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-shop text-success display-5"></i>
                <h1 class="h4 mt-2 mb-0"><?= e(App\Core\App::config('app.name')) ?></h1>
                <p class="text-muted small">Đăng nhập dành cho nhân viên</p>
            </div>

            <?php foreach (Session::pullFlash() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> py-2"><?= e($f['message']) ?></div>
            <?php endforeach; ?>

            <form method="post" action="<?= url('/login') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <input class="form-control" id="username" name="username" autocomplete="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-success w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                </button>
            </form>
        </div>
    </div>
</div>
