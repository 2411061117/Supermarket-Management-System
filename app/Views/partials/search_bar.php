<?php
/**
 * Thanh tìm kiếm (GET ?q=...) kèm các ô lọc dạng select (tùy chọn).
 *   partial('search_bar', [
 *       'action'      => url('/suppliers'),
 *       'q'           => $q,
 *       'placeholder' => 'Tìm theo tên hoặc SĐT',
 *       'selects'     => [   // tên tham số GET => [nhãn mục "tất cả", [giá trị => nhãn], giá trị đang chọn]
 *           'status' => ['Tất cả trạng thái', ['active' => 'Đang hợp tác', 'inactive' => 'Ngừng hợp tác'], $status],
 *       ],
 *   ]);
 */
$q = $q ?? '';
$selects = $selects ?? [];
$filtered = $q !== '' || array_filter($selects, fn($s) => ($s[2] ?? '') !== '') !== [];
?>
<form method="get" action="<?= e($action) ?>" class="d-flex flex-wrap gap-2 no-print">
    <input type="search" name="q" class="form-control w-auto flex-grow-1" value="<?= e($q) ?>"
           placeholder="<?= e($placeholder ?? 'Tìm kiếm...') ?>" aria-label="Tìm kiếm">
    <?php foreach ($selects as $name => [$allLabel, $options, $selected]): ?>
        <select name="<?= e($name) ?>" class="form-select w-auto" aria-label="<?= e($allLabel) ?>">
            <option value=""><?= e($allLabel) ?></option>
            <?php foreach ($options as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= (string) $value === (string) $selected ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endforeach; ?>
    <button class="btn btn-outline-success" aria-label="Tìm"><i class="bi bi-search"></i></button>
    <?php if ($filtered): ?>
        <a href="<?= e($action) ?>" class="btn btn-outline-secondary text-nowrap">Xóa lọc</a>
    <?php endif; ?>
</form>
