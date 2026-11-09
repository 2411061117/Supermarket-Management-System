<?php
/** Form dùng chung thêm/sửa. $user = null khi thêm mới; $isSelf = đang sửa chính tài khoản của mình. */
$editing = $user !== null;
$action = $editing ? url("/users/{$user['id']}") : url('/users');
?>
<h1 class="h4 mb-3"><?= e($title) ?></h1>

<div class="card border-0 shadow-sm" style="max-width: 640px">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" novalidate autocomplete="off">
            <?= csrf_field() ?>

            <?php if ($editing): ?>
                <div class="mb-3">
                    <label class="form-label" for="f_username_view">Tên đăng nhập</label>
                    <input class="form-control" id="f_username_view" value="<?= e($user['username']) ?>" disabled>
                    <div class="form-text">Không thể đổi tên đăng nhập.</div>
                </div>
            <?php else: ?>
                <?php partial('field', [
                    'name'     => 'username',
                    'label'    => 'Tên đăng nhập',
                    'required' => true,
                    'help'     => '3–50 ký tự: chữ không dấu, số, gạch dưới (tự viết thường).',
                    'attrs'    => 'maxlength="50" autocomplete="off" autofocus',
                ]) ?>
            <?php endif; ?>

            <?php partial('field', [
                'name'     => 'full_name',
                'label'    => 'Họ tên',
                'value'    => $user['full_name'] ?? '',
                'required' => true,
                'attrs'    => 'maxlength="100"',
            ]) ?>

            <?php partial('field', [
                'name'     => 'role_id',
                'label'    => 'Vai trò',
                'type'     => 'select',
                'value'    => $user['role_id'] ?? '',
                'options'  => $roles,
                'required' => true,
                'help'     => $isSelf ? 'Bạn không thể tự đổi vai trò của chính mình.' : '',
                'attrs'    => $isSelf ? 'disabled' : '',
            ]) ?>

            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'  => 'phone',
                        'label' => 'Số điện thoại',
                        'type'  => 'tel',
                        'value' => $user['phone'] ?? '',
                        'attrs' => 'maxlength="20"',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'  => 'email',
                        'label' => 'Email',
                        'type'  => 'email',
                        'value' => $user['email'] ?? '',
                        'attrs' => 'maxlength="100"',
                    ]) ?>
                </div>
            </div>

            <hr>
            <h2 class="h6"><?= $editing ? 'Đặt lại mật khẩu' : 'Mật khẩu' ?></h2>
            <?php if ($editing): ?>
                <p class="small text-muted">Để trống nếu không muốn đổi mật khẩu.</p>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'password',
                        'label'    => $editing ? 'Mật khẩu mới' : 'Mật khẩu',
                        'type'     => 'password',
                        'required' => !$editing,
                        'help'     => 'Từ 6 đến 72 ký tự.',
                        'attrs'    => 'maxlength="72" autocomplete="new-password"',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?php partial('field', [
                        'name'     => 'password_confirmation',
                        'label'    => 'Xác nhận mật khẩu',
                        'type'     => 'password',
                        'required' => !$editing,
                        'attrs'    => 'maxlength="72" autocomplete="new-password"',
                    ]) ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="<?= url('/users') ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
