<?php
/**
 * Thanh tìm kiếm (GET ?q=...).
 *   partial('search_bar', ['action' => url('/suppliers'), 'q' => $q, 'placeholder' => 'Tìm theo tên hoặc SĐT']);
 */
$q = $q ?? '';
?>
<form method="get" action="<?= e($action) ?>" class="d-flex gap-2 no-print">
    <input type="search" name="q" class="form-control" value="<?= e($q) ?>"
           placeholder="<?= e($placeholder ?? 'Tìm kiếm...') ?>" aria-label="Tìm kiếm">
    <button class="btn btn-outline-success" aria-label="Tìm"><i class="bi bi-search"></i></button>
    <?php if ($q !== ''): ?>
        <a href="<?= e($action) ?>" class="btn btn-outline-secondary text-nowrap">Xóa lọc</a>
    <?php endif; ?>
</form>
