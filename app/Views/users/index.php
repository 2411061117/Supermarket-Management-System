<?php
$roleBadge = ['admin' => 'text-bg-primary', 'cashier' => 'text-bg-info', 'warehouse' => 'text-bg-warning'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Nhân viên</h1>
    <a href="<?= url('/users/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Thêm nhân viên
    </a>
</div>

<div class="mb-3">
    <?php partial('search_bar', [
        'action'      => url('/users'),
        'q'           => $q,
        'placeholder' => 'Tìm theo tên đăng nhập, họ tên, SĐT, email',
        'selects'     => [
            'role_id' => ['Tất cả vai trò', $roles, $roleId > 0 ? $roleId : ''],
            'status'  => ['Tất cả trạng thái', ['active' => 'Đang hoạt động', 'inactive' => 'Đã khóa'], $status],
        ],
    ]) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>SĐT</th>
                    <th>Email</th>
                    <th>Đăng nhập gần nhất</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 210px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <?php $active = (int) $u['is_active'] === 1; $isMe = (int) $u['id'] === $meId; ?>
                <tr data-user-row class="<?= $active ? '' : 'text-muted' ?>">
                    <td>
                        <div class="fw-medium">
                            <?= e($u['full_name']) ?>
                            <?php if ($isMe): ?><span class="badge text-bg-light border">Bạn</span><?php endif; ?>
                        </div>
                        <div class="small text-muted"><i class="bi bi-person"></i> <?= e($u['username']) ?></div>
                    </td>
                    <td><span class="badge <?= e($roleBadge[$u['role_code']] ?? 'text-bg-secondary') ?>"><?= e($u['role_name']) ?></span></td>
                    <td class="text-nowrap"><?= e($u['phone']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td class="text-nowrap">
                        <?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : '<span class="text-muted">Chưa đăng nhập</span>' ?>
                    </td>
                    <td>
                        <?php if ($active): ?>
                            <span class="badge text-bg-success">Đang hoạt động</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Đã khóa</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("/users/{$u['id']}/edit") ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <?php if ($isMe): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Bạn không thể tự khóa tài khoản của mình">
                                <i class="bi bi-lock"></i> Khóa
                            </button>
                        <?php else: ?>
                            <?php partial('delete_button', [
                                'action'  => url("/users/{$u['id']}/toggle"),
                                'message' => ($active ? 'Khóa tài khoản "' : 'Mở khóa tài khoản "') . $u['username'] . '"?',
                                'label'   => $active ? 'Khóa' : 'Mở khóa',
                                'icon'    => $active ? 'lock' : 'unlock',
                                'class'   => $active ? 'btn-outline-warning' : 'btn-outline-success',
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <?= ($q !== '' || $roleId > 0 || $status !== '') ? 'Không tìm thấy nhân viên phù hợp.' : 'Chưa có nhân viên nào.' ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php partial('pagination', ['paginator' => $paginator]) ?>
