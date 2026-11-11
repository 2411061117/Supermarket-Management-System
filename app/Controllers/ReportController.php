<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Report;

/** Báo cáo & thống kê (admin). Chỉ tính hóa đơn `completed`. */
class ReportController extends Controller
{
    private const DEFAULT_DAYS = 7;
    private const MAX_DAYS = 366;

    public function index(): void
    {
        $today = date('Y-m-d');
        $from = $this->dateInput('from');
        $to = $this->dateInput('to');
        $notices = [];

        // Thiếu ngày nào thì lấy mặc định: 7 ngày gần nhất (hôm nay và 6 ngày trước)
        if ($to === '') {
            $to = $from !== '' ? min($today, date('Y-m-d', strtotime($from . ' +' . (self::DEFAULT_DAYS - 1) . ' day'))) : $today;
        }
        if ($from === '') {
            $from = date('Y-m-d', strtotime($to . ' -' . (self::DEFAULT_DAYS - 1) . ' day'));
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
            $notices[] = 'Đã đổi chỗ "Từ ngày" và "Đến ngày" vì khoảng ngày bị ngược.';
        }
        $days = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;
        if ($days > self::MAX_DAYS) {
            $from = date('Y-m-d', strtotime($to . ' -' . (self::MAX_DAYS - 1) . ' day'));
            $notices[] = 'Khoảng ngày tối đa ' . self::MAX_DAYS . ' ngày, đã rút gọn còn ' . self::MAX_DAYS . ' ngày tính đến "Đến ngày".';
        }

        $storeId = (int) Auth::user()['store_id'];
        $report = new Report();
        $byDay = $report->revenueByDay($storeId, $from, $to);
        $this->view('reports/index', [
            'title'       => 'Báo cáo thống kê',
            'from'        => $from,
            'to'          => $to,
            'notices'     => $notices,
            'summary'     => $report->summary($storeId, $from, $to),
            'byDay'       => $byDay,
            'maxDay'      => max(1, max(array_column($byDay, 'revenue'))),
            'byMethod'    => $report->byPaymentMethod($storeId, $from, $to),
            'byCashier'   => $report->byCashier($storeId, $from, $to),
            'topByQty'    => $report->topProducts($storeId, $from, $to, 'qty'),
            'topByRevenue' => $report->topProducts($storeId, $from, $to, 'revenue'),
        ]);
    }

    /** Ngày hợp lệ dạng Y-m-d, ngược lại '' (dùng mặc định). */
    private function dateInput(string $key): string
    {
        $v = $this->input($key, '');
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d', $v) : false;
        return $d && $d->format('Y-m-d') === $v ? $v : '';
    }
}
