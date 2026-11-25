<?php
use App\Core\Auth;
use App\Models\Shift;

$user = Auth::user();
$myShift = Auth::hasRole('admin', 'cashier') ? (new Shift())->findOpenByUser((int) $user['id']) : null;
$initials = '';
foreach (array_slice(preg_split('/\s+/u', trim($user['full_name'])), -2) as $w) {
    $initials .= mb_strtoupper(mb_substr($w, 0, 1));
}
?>
<div class="userbar d-flex align-items-center gap-2">
    <?php if (Auth::hasRole('admin', 'cashier')): ?>
        <a href="<?= url('/shifts') ?>" class="shift-pill <?= $myShift ? 'is-open' : 'is-closed' ?>">
            <span class="dot"></span>
            <?= $myShift ? 'Ca mở từ ' . e(date('H:i', strtotime($myShift['opened_at']))) : 'Chưa mở ca' ?>
        </a>
    <?php endif; ?>
    <div class="dropdown">
        <button class="user-pill" data-bs-toggle="dropdown">
            <span class="avatar"><?= e($initials) ?></span>
            <span class="d-none d-sm-inline"><?= e($user['full_name']) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted"><?= e($user['role_name']) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="post" action="<?= url('/logout') ?>">
                    <?= csrf_field() ?>
                    <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                </form>
            </li>
        </ul>
    </div>
</div>
