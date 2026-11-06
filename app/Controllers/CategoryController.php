<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use PDOException;

/** CRUD loại hàng. Mọi route chỉ dành cho admin (xem config/routes.php). */
class CategoryController extends Controller
{
    private const LABELS = ['name' => 'Tên loại hàng', 'description' => 'Mô tả'];

    private Category $categories;

    public function __construct()
    {
        $this->categories = new Category();
    }

    public function index(): void
    {
        $this->view('categories/index', [
            'title'      => 'Loại hàng',
            'categories' => $this->categories->withProductCount(),
        ]);
    }

    public function create(): void
    {
        $this->view('categories/form', ['title' => 'Thêm loại hàng', 'category' => null]);
    }

    public function store(): void
    {
        $data = $this->payload('/categories/create');
        $this->categories->create($data);
        Session::flash('success', 'Đã thêm loại hàng "' . $data['name'] . '".');
        redirect('/categories');
    }

    public function edit(string $id): void
    {
        $category = $this->findOrFail($id);
        $this->view('categories/form', ['title' => 'Sửa loại hàng', 'category' => $category]);
    }

    public function update(string $id): void
    {
        $category = $this->findOrFail($id);
        $data = $this->payload("/categories/{$category['id']}/edit", (int) $category['id']);
        $this->categories->update((int) $category['id'], $data);
        Session::flash('success', 'Đã cập nhật loại hàng "' . $data['name'] . '".');
        redirect('/categories');
    }

    public function destroy(string $id): void
    {
        $category = $this->findOrFail($id);

        $inUse = $this->categories->productCount((int) $category['id']);
        if ($inUse > 0) {
            Session::flash('danger', "Không thể xóa: loại hàng \"{$category['name']}\" đang có $inUse sản phẩm.");
            redirect('/categories');
        }

        try {
            $this->categories->delete((int) $category['id']);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // 23000 = vi phạm khóa ngoại (vừa có sản phẩm mới thuộc loại này)
                throw $e;
            }
            Session::flash('danger', "Không thể xóa: loại hàng \"{$category['name']}\" đang được sử dụng.");
            redirect('/categories');
        }
        Session::flash('success', 'Đã xóa loại hàng "' . $category['name'] . '".');
        redirect('/categories');
    }

    /** Validate form; mô tả để trống thì lưu NULL. */
    private function payload(string $backTo, ?int $ignoreId = null): array
    {
        $data = $this->validate([
            'name'        => 'required|max:100|unique:categories,name' . ($ignoreId !== null ? ",$ignoreId" : ''),
            'description' => 'max:255',
        ], self::LABELS, $backTo);

        $data['description'] = $data['description'] === '' ? null : $data['description'];
        return $data;
    }

    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? ($this->categories->find((int) $id) ?? View::abort(404)) : View::abort(404);
    }
}
