<?php
/**
 * Nút thao tác POST có hỏi xác nhận (xóa, khóa, mở khóa...). Đã kèm CSRF token.
 *   partial('delete_button', ['action' => url("/units/$id/delete"), 'message' => 'Xóa đơn vị này?']);
 * Tùy chọn: $label (mặc định "Xóa"), $icon (mặc định trash), $class (mặc định btn-outline-danger).
 */
?>
<form method="post" action="<?= e($action) ?>" class="d-inline"
      onsubmit="return confirm(<?= e(json_encode($message ?? 'Bạn chắc chắn muốn xóa?', JSON_UNESCAPED_UNICODE)) ?>)">
    <?= csrf_field() ?>
    <button class="btn btn-sm <?= e($class ?? 'btn-outline-danger') ?>">
        <i class="bi bi-<?= e($icon ?? 'trash') ?>"></i> <?= e($label ?? 'Xóa') ?>
    </button>
</form>
