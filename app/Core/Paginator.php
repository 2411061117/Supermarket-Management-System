<?php
namespace App\Core;

/**
 * Phân trang.
 *   $p = new Paginator($total, 10);
 *   "SELECT ... LIMIT {$p->limit} OFFSET {$p->offset}"   // 2 giá trị là int, an toàn để nối vào SQL
 *   echo $p->links();                                      // giữ nguyên các tham số tìm kiếm trong URL
 */
class Paginator
{
    public readonly int $page;
    public readonly int $pages;
    public readonly int $offset;

    public function __construct(
        public readonly int $total,
        public readonly int $limit = 10,
        ?int $page = null,
    ) {
        $this->pages  = max(1, (int) ceil($total / max(1, $limit)));
        $page ??= (int) ($_GET['page'] ?? 1);
        $this->page   = min(max(1, $page), $this->pages);
        $this->offset = ($this->page - 1) * $limit;
    }

    /** "Hiển thị 11–20 / 53" */
    public function summary(): string
    {
        if ($this->total === 0) {
            return 'Không có dữ liệu';
        }
        $from = $this->offset + 1;
        $to = min($this->offset + $this->limit, $this->total);
        // Phải có {} vì "–" là ký tự hợp lệ trong tên biến PHP ("$from–" sẽ bị hiểu là biến khác)
        return "Hiển thị {$from}–{$to} / {$this->total}";
    }

    public function links(): string
    {
        if ($this->pages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Phân trang"><ul class="pagination pagination-sm mb-0 flex-wrap">';
        $html .= $this->item($this->page - 1, '&laquo;', $this->page <= 1);

        $from = max(1, $this->page - 2);
        $to = min($this->pages, $this->page + 2);
        if ($from > 1) {
            $html .= $this->item(1, '1');
            if ($from > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }
        for ($i = $from; $i <= $to; $i++) {
            $html .= $this->item($i, (string) $i, false, $i === $this->page);
        }
        if ($to < $this->pages) {
            if ($to < $this->pages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            $html .= $this->item($this->pages, (string) $this->pages);
        }

        $html .= $this->item($this->page + 1, '&raquo;', $this->page >= $this->pages);
        return $html . '</ul></nav>';
    }

    private function item(int $page, string $label, bool $disabled = false, bool $active = false): string
    {
        $class = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        if ($disabled || $active) {
            return "<li class=\"$class\"><span class=\"page-link\">$label</span></li>";
        }
        return "<li class=\"$class\"><a class=\"page-link\" href=\"" . e($this->href($page)) . "\">$label</a></li>";
    }

    private function href(int $page): string
    {
        $query = $_GET;
        $query['page'] = $page;
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        return $path . '?' . http_build_query($query);
    }
}
