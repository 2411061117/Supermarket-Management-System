<?php use App\Core\Session; ?>
<div class="auth-wrap">
    <section class="auth-art">
        <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
        <div class="brand-logo text-white">
            <span class="logo-mark" style="background: rgba(255,255,255,.22); box-shadow: none"><i class="bi bi-shop"></i></span>
            <?= e(App\Core\App::config('app.name')) ?>
        </div>
        <div>
            <h2>Bán hàng nhanh,<br>quản lý kho gọn.</h2>
            <p class="opacity-75 mt-3 mb-4" style="max-width: 420px">Theo dõi doanh thu, tồn kho và hạn sử dụng trong một nơi duy nhất — mượt mà trên mọi thiết bị.</p>
            <div class="d-flex flex-wrap gap-2">
                <span class="pill"><i class="bi bi-lightning-charge-fill"></i> Siêu nhanh</span>
                <span class="pill"><i class="bi bi-box-seam"></i> Quản lý kho</span>
                <span class="pill"><i class="bi bi-graph-up-arrow"></i> Báo cáo</span>
            </div>
        </div>
        <small class="opacity-75">© <?= date('Y') ?> <?= e(App\Core\App::config('app.name')) ?></small>
    </section>

    <section class="auth-form">
        <div class="login-card">
            <div class="mb-4">
                <h1 class="h3 mb-1">Chào mừng trở lại 👋</h1>
                <p class="text-muted mb-0">Đăng nhập dành cho nhân viên</p>
            </div>

            <?php foreach (Session::pullFlash() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> py-2"><?= e($f['message']) ?></div>
            <?php endforeach; ?>

            <form method="post" action="<?= url('/login') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input class="form-control" id="username" name="username" autocomplete="username" placeholder="Nhập tên đăng nhập" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2">
                    Đăng nhập <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </section>
</div>
