<?php
/** Thanh phân trang + dòng tóm tắt. partial('pagination', ['paginator' => $paginator]); */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 no-print">
    <small class="text-muted"><?= e($paginator->summary()) ?></small>
    <?= $paginator->links() ?>
</div>
