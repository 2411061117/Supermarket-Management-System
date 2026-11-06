<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use PDOException;
use RuntimeException;

/**
 * CRUD sản phẩm (admin) + endpoint tìm kiếm JSON cho POS/nhập kho (admin, thu ngân, NV kho).
 * Sản phẩm đã có lô hàng thì không xóa được (mất lịch sử kho/bán hàng) -> khóa bằng "Ngừng bán".
 */
class ProductController extends Controller
{
    private const PER_PAGE = 10;
    private const LABELS = [
        'sku'         => 'Mã SKU',
        'barcode'     => 'Mã vạch',
        'name'        => 'Tên sản phẩm',
        'category_id' => 'Loại hàng',
        'unit_id'     => 'Đơn vị tính',
        'sale_price'  => 'Giá bán',
        'min_stock'   => 'Tồn tối thiểu',
        'image'       => 'Ảnh',
    ];

    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(): void
    {
        $q = $this->input('q', '');
        $q = is_string($q) ? $q : '';
        $categoryId = $this->input('category_id', '');
        $categoryId = is_string($categoryId) && ctype_digit($categoryId) ? (int) $categoryId : 0;
        $status = $this->input('status', '');
        $status = in_array($status, ['active', 'inactive'], true) ? $status : '';

        $paginator = new Paginator($this->products->countFiltered($q, $categoryId, $status), self::PER_PAGE);
        $this->view('products/index', [
            'title'      => 'Sản phẩm',
            'products'   => $this->products->search($q, $categoryId, $status, $paginator->limit, $paginator->offset),
            'paginator'  => $paginator,
            'categories' => array_column((new Category())->all('name ASC'), 'name', 'id'),
            'q'          => $q,
            'categoryId' => $categoryId,
            'status'     => $status,
        ]);
    }

    /** GET /products/search?q=... -> {"items": [...]} (tối đa 20 sản phẩm đang bán). */
    public function search(): void
    {
        $q = $this->input('q', '');
        $q = is_string($q) ? mb_substr($q, 0, 100) : '';
        if ($q === '') {
            $this->json(['items' => []]);
        }

        $items = array_map(fn(array $p) => [
            'id'         => (int) $p['id'],
            'sku'        => $p['sku'],
            'barcode'    => $p['barcode'],
            'name'       => $p['name'],
            'unit'       => $p['unit_name'],
            'sale_price' => (int) $p['sale_price'],
            'stock'      => (int) $p['stock'],
            'image'      => $p['image'] ? asset($p['image']) : null,
        ], $this->products->searchActive($q));

        $this->json(['items' => $items]);
    }

    public function create(): void
    {
        $this->view('products/form', $this->formData('Thêm sản phẩm', null));
    }

    public function store(): void
    {
        $back = '/products/create';
        $data = $this->payload($back);
        $data['image'] = $this->uploadImage($back);

        try {
            $this->products->create($data);
        } catch (PDOException $e) {
            Uploader::delete($data['image']);
            $this->handleDuplicate($e, $back);
        }
        Session::flash('success', 'Đã thêm sản phẩm "' . $data['name'] . '".');
        redirect('/products');
    }

    public function edit(string $id): void
    {
        $product = $this->findOrFail($id);
        $this->view('products/form', $this->formData('Sửa sản phẩm', $product));
    }

    public function update(string $id): void
    {
        $product = $this->findOrFail($id);
        $back = "/products/{$product['id']}/edit";
        $data = $this->payload($back, (int) $product['id']);

        $newImage = $this->uploadImage($back);
        $removeImage = ($_POST['remove_image'] ?? '') === '1';
        if ($newImage !== null) {
            $data['image'] = $newImage;
        } elseif ($removeImage) {
            $data['image'] = null;
        }

        try {
            $this->products->update((int) $product['id'], $data);
        } catch (PDOException $e) {
            Uploader::delete($newImage);
            $this->handleDuplicate($e, $back);
        }

        if (array_key_exists('image', $data)) {
            Uploader::delete($product['image']);   // ảnh cũ đã bị thay hoặc gỡ
        }
        Session::flash('success', 'Đã cập nhật sản phẩm "' . $data['name'] . '".');
        redirect('/products');
    }

    /** Bật/tắt trạng thái kinh doanh (ngừng bán không ảnh hưởng lịch sử). */
    public function toggle(string $id): void
    {
        $product = $this->findOrFail($id);
        $active = (int) $product['is_active'] === 1 ? 0 : 1;
        $this->products->update((int) $product['id'], ['is_active' => $active]);
        Session::flash('success', $active
            ? "Đã mở bán lại \"{$product['name']}\"."
            : "Đã ngừng bán \"{$product['name']}\".");
        redirect('/products');
    }

    public function destroy(string $id): void
    {
        $product = $this->findOrFail($id);

        $batches = $this->products->batchCount((int) $product['id']);
        if ($batches > 0) {
            Session::flash('danger', "Không thể xóa: \"{$product['name']}\" đã có $batches lô hàng. Hãy dùng \"Ngừng bán\".");
            redirect('/products');
        }

        try {
            $this->products->delete((int) $product['id']);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // 23000 = vi phạm khóa ngoại (vừa có lô hàng mới)
                throw $e;
            }
            Session::flash('danger', "Không thể xóa: \"{$product['name']}\" đang được sử dụng. Hãy dùng \"Ngừng bán\".");
            redirect('/products');
        }
        Uploader::delete($product['image']);
        Session::flash('success', 'Đã xóa sản phẩm "' . $product['name'] . '".');
        redirect('/products');
    }

    private function formData(string $title, ?array $product): array
    {
        return [
            'title'      => $title,
            'product'    => $product,
            'categories' => array_column((new Category())->all('name ASC'), 'name', 'id'),
            'units'      => array_column((new Unit())->all('name ASC'), 'name', 'id'),
        ];
    }

    /** Chuẩn hóa (SKU viết hoa), validate, barcode trống -> NULL, ép kiểu số. Chưa xử lý ảnh. */
    private function payload(string $backTo, ?int $ignoreId = null): array
    {
        if (isset($_POST['sku']) && is_string($_POST['sku'])) {
            $_POST['sku'] = strtoupper(trim($_POST['sku']));
        }
        $ignore = $ignoreId !== null ? ",$ignoreId" : '';

        $data = $this->validate([
            'sku'         => 'required|max:30|alpha_dash|unique:products,sku' . $ignore,
            'barcode'     => 'max:30|digits|unique:products,barcode' . $ignore,
            'name'        => 'required|max:150',
            'category_id' => 'required|integer|exists:categories,id',
            'unit_id'     => 'required|integer|exists:units,id',
            'sale_price'  => 'required|integer|min:0|max:999999999',
            'min_stock'   => 'required|integer|min:0|max:1000000',
        ], self::LABELS, $backTo);

        $data['barcode']     = $data['barcode'] === '' ? null : $data['barcode'];
        $data['category_id'] = (int) $data['category_id'];
        $data['unit_id']     = (int) $data['unit_id'];
        $data['sale_price']  = (int) $data['sale_price'];
        $data['min_stock']   = (int) $data['min_stock'];
        return $data;
    }

    /** Lưu ảnh upload (nếu có). Ảnh sai -> quay lại form kèm lỗi. */
    private function uploadImage(string $backTo): ?string
    {
        try {
            return Uploader::image('image', 'products');
        } catch (RuntimeException $e) {
            $this->backWithErrors(['image' => $e->getMessage()], $backTo);
        }
    }

    /** Trùng SKU/barcode do 2 người lưu cùng lúc (qua được validate nhưng vướng UNIQUE). */
    private function handleDuplicate(PDOException $e, string $backTo): never
    {
        if ($e->getCode() !== '23000') {
            throw $e;
        }
        $this->backWithErrors(['sku' => 'SKU hoặc mã vạch đã tồn tại, vui lòng kiểm tra lại.'], $backTo);
    }

    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? ($this->products->find((int) $id) ?? View::abort(404)) : View::abort(404);
    }
}
