<?php
namespace App\Controllers;

use App\Core\Controller;

/** Báo cáo & thống kê (admin). Chỉ tính hóa đơn `completed`. */
class ReportController extends Controller
{
    public function index(): void
    {
        $this->view('reports/index', ['title' => 'Báo cáo thống kê']);
    }

    // TODO Bước 5: bộ lọc khoảng ngày, doanh thu theo ngày/phương thức/thu ngân, top 10 sản phẩm.
}
