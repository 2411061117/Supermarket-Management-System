<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Report;

class DashboardController extends Controller
{
    public function index(): void
    {
        $storeId = Auth::user()['store_id'];
        $report = new Report();

        $this->view('dashboard/index', [
            'title'        => 'Tổng quan',
            'revenue'      => $report->todayRevenue($storeId),
            'orderCount'   => $report->todayOrderCount($storeId),
            'lowStock'     => $report->lowStockProducts($storeId),
            'expiring'     => $report->expiringBatches($storeId),
        ]);
    }
}
