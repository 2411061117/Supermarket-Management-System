<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Unit;
use PDOException;

/** CRUD đơn vị tính. Mọi route chỉ dành cho admin (xem config/routes.php). */
class UnitController extends Controller
{
    private const LABELS = ['name' => 'Tên đơn vị'];

    private Unit $units;

    public function __construct()
    {
        $this->units = new Unit();
    }

    public function index(): void
    {
        $this->view('units/index', ['title' => 'Đơn vị tính', 'units' => $this->units->withProductCount()]);
    }

    public function create(): void
    {
        $this->view('units/form', ['title' => 'Thêm đơn vị tính', 'unit' => null]);
    }

    public function store(): void
    {
        $data = $this->validate($this->rules(), self::LABELS, '/units/create');
        $this->units->create($data);
        Session::flash('success', 'Đã thêm đơn vị "' . $data['name'] . '".');
        redirect('/units');
    }

    public function edit(string $id): void
    {
        $unit = $this->findOrFail($id);
        $this->view('units/form', ['title' => 'Sửa đơn vị tính', 'unit' => $unit]);
    }

    public function update(string $id): void
    {
        $unit = $this->findOrFail($id);
        $data = $this->validate($this->rules((int) $unit['id']), self::LABELS, "/units/{$unit['id']}/edit");
        $this->units->update((int) $unit['id'], $data);
        Session::flash('success', 'Đã cập nhật đơn vị "' . $data['name'] . '".');
        redirect('/units');
    }

    public function destroy(string $id): void
    {
        $unit = $this->findOrFail($id);

        $inUse = $this->units->productCount((int) $unit['id']);
        if ($inUse > 0) {
            Session::flash('danger', "Không thể xóa: đơn vị \"{$unit['name']}\" đang được dùng bởi $inUse sản phẩm.");
            redirect('/units');
        }

        try {
            $this->units->delete((int) $unit['id']);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // 23000 = vi phạm khóa ngoại (vừa có sản phẩm mới dùng)
                throw $e;
            }
            Session::flash('danger', "Không thể xóa: đơn vị \"{$unit['name']}\" đang được sử dụng.");
            redirect('/units');
        }
        Session::flash('success', 'Đã xóa đơn vị "' . $unit['name'] . '".');
        redirect('/units');
    }

    private function rules(?int $ignoreId = null): array
    {
        return ['name' => 'required|max:50|unique:units,name' . ($ignoreId !== null ? ",$ignoreId" : '')];
    }

    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? ($this->units->find((int) $id) ?? View::abort(404)) : View::abort(404);
    }
}
