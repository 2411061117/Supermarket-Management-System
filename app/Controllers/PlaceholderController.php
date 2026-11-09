<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Trang tạm cho các module sẽ làm ở các tuần sau.
 * Dùng để kiểm thử phân quyền ngay từ tuần 1; thay dần bằng controller thật.
 */
class PlaceholderController extends Controller
{
    private const MODULES = [
        'pos'       => ['Bán hàng tại quầy (POS)', 'Tuần 4'],
        'reports'   => ['Báo cáo thống kê', 'Tuần 4'],
    ];

    public function pos(): void       { $this->show('pos'); }
    public function reports(): void   { $this->show('reports'); }

    private function show(string $key): void
    {
        [$title, $week] = self::MODULES[$key];
        $this->view('placeholder', compact('title', 'week'));
    }
}
