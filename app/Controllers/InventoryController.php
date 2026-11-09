<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Core\View;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;

/** Tồn kho (admin, NV kho): xem tồn theo sản phẩm, chi tiết lô theo FEFO, cảnh báo tồn thấp/hết hạn. Chỉ đọc. */
class InventoryController extends Controller
{
    private const PER_PAGE = 10;
    public const STATUSES = [
        'low'      => 'Tồn thấp (gồm hết hàng)',
        'out'      => 'Hết hàng',
        'ok'       => 'Đủ hàng',
        'expiring' => 'Có lô sắp hết hạn',
        'expired'  => 'Có lô đã hết hạn',
    ];

    private Batch $batches;

    public function __construct()
    {
        $this->batches = new Batch();
    }

    public function index(): void
    {
        $storeId = (int) Auth::user()['store_id'];
        $q = $this->input('q', '');
        $q = is_string($q) ? $q : '';
        $categoryId = $this->input('category_id', '');
        $categoryId = is_string($categoryId) && ctype_digit($categoryId) ? (int) $categoryId : 0;
        $status = $this->input('status', '');
        $status = is_string($status) && isset(self::STATUSES[$status]) ? $status : '';

        $paginator = new Paginator($this->batches->countStock($storeId, $q, $categoryId, $status), self::PER_PAGE);
        $this->view('inventory/index', [
            'title'      => 'Tồn kho',
            'rows'       => $this->batches->stockSummary($storeId, $q, $categoryId, $status, $paginator->limit, $paginator->offset),
            'paginator'  => $paginator,
            'alerts'     => $this->batches->alertCounts($storeId),
            'categories' => array_column((new Category())->all('name ASC'), 'name', 'id'),
            'statuses'   => self::STATUSES,
            'q'          => $q,
            'categoryId' => $categoryId,
            'status'     => $status,
        ]);
    }

    /** Chi tiết các lô của 1 sản phẩm. ?all=1 để hiện cả lô đã hết hàng. */
    public function product(string $id): void
    {
        $product = ctype_digit($id) ? (new Product())->findWithNames((int) $id) : null;
        if ($product === null) {
            View::abort(404);
        }
        $includeEmpty = $this->input('all') === '1';
        $this->view('inventory/product', [
            'title'        => 'Tồn kho: ' . $product['name'],
            'product'      => $product,
            'batches'      => $this->batches->forProduct((int) $product['id'], (int) Auth::user()['store_id'], $includeEmpty),
            'includeEmpty' => $includeEmpty,
            'warnDays'     => Batch::WARN_DAYS,
        ]);
    }
}
