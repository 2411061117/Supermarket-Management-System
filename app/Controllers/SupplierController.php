<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\View;
use App\Models\Supplier;
use PDOException;

/**
 * CRUD nhà cung cấp. Mọi route chỉ dành cho admin (xem config/routes.php).
 * NCC đã có phiếu nhập thì không xóa được (mất lịch sử nhập hàng) -> dùng "Ngừng hợp tác".
 */
class SupplierController extends Controller
{
    private const PER_PAGE = 10;
    private const LABELS = [
        'name'    => 'Tên nhà cung cấp',
        'contact' => 'Người liên hệ',
        'phone'   => 'Số điện thoại',
        'email'   => 'Email',
        'address' => 'Địa chỉ',
    ];
    private const RULES = [
        'name'    => 'required|max:150',
        'contact' => 'max:100',
        'phone'   => 'phone|max:20',
        'email'   => 'email|max:100',
        'address' => 'max:255',
    ];

    private Supplier $suppliers;

    public function __construct()
    {
        $this->suppliers = new Supplier();
    }

    public function index(): void
    {
        // ?q[]=x hay ?status[]=x cho ra mảng -> coi như không lọc
        $q = $this->input('q', '');
        $q = is_string($q) ? $q : '';
        $status = $this->input('status', '');
        $status = in_array($status, ['active', 'inactive'], true) ? $status : '';

        $paginator = new Paginator($this->suppliers->countFiltered($q, $status), self::PER_PAGE);
        $this->view('suppliers/index', [
            'title'     => 'Nhà cung cấp',
            'suppliers' => $this->suppliers->search($q, $status, $paginator->limit, $paginator->offset),
            'paginator' => $paginator,
            'q'         => $q,
            'status'    => $status,
        ]);
    }

    public function create(): void
    {
        $this->view('suppliers/form', ['title' => 'Thêm nhà cung cấp', 'supplier' => null]);
    }

    public function store(): void
    {
        $data = $this->payload('/suppliers/create');
        $this->suppliers->create($data);
        Session::flash('success', 'Đã thêm nhà cung cấp "' . $data['name'] . '".');
        redirect('/suppliers');
    }

    public function edit(string $id): void
    {
        $supplier = $this->findOrFail($id);
        $this->view('suppliers/form', ['title' => 'Sửa nhà cung cấp', 'supplier' => $supplier]);
    }

    public function update(string $id): void
    {
        $supplier = $this->findOrFail($id);
        $data = $this->payload("/suppliers/{$supplier['id']}/edit");
        $this->suppliers->update((int) $supplier['id'], $data);
        Session::flash('success', 'Đã cập nhật nhà cung cấp "' . $data['name'] . '".');
        redirect('/suppliers');
    }

    /** Bật/tắt trạng thái hợp tác. */
    public function toggle(string $id): void
    {
        $supplier = $this->findOrFail($id);
        $active = (int) $supplier['is_active'] === 1 ? 0 : 1;
        $this->suppliers->update((int) $supplier['id'], ['is_active' => $active]);
        Session::flash('success', $active
            ? "Đã kích hoạt lại nhà cung cấp \"{$supplier['name']}\"."
            : "Đã ngừng hợp tác với \"{$supplier['name']}\".");
        redirect('/suppliers');
    }

    public function destroy(string $id): void
    {
        $supplier = $this->findOrFail($id);

        $receipts = $this->suppliers->receiptCount((int) $supplier['id']);
        if ($receipts > 0) {
            Session::flash('danger', "Không thể xóa: \"{$supplier['name']}\" đã có $receipts phiếu nhập. Hãy dùng \"Ngừng hợp tác\".");
            redirect('/suppliers');
        }

        try {
            $this->suppliers->delete((int) $supplier['id']);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {   // 23000 = vi phạm khóa ngoại (vừa có phiếu nhập mới)
                throw $e;
            }
            Session::flash('danger', "Không thể xóa: \"{$supplier['name']}\" đang được sử dụng. Hãy dùng \"Ngừng hợp tác\".");
            redirect('/suppliers');
        }
        Session::flash('success', 'Đã xóa nhà cung cấp "' . $supplier['name'] . '".');
        redirect('/suppliers');
    }

    /** Chuẩn hóa SĐT, validate, và đổi trường tùy chọn để trống thành NULL. */
    private function payload(string $backTo): array
    {
        // Cho phép gõ "0912 345 678", "0912.345.678", "0912-345-678"
        if (isset($_POST['phone']) && is_string($_POST['phone'])) {
            $_POST['phone'] = preg_replace('/[\s.\-]/', '', $_POST['phone']);
        }

        $data = $this->validate(self::RULES, self::LABELS, $backTo);
        foreach (['contact', 'phone', 'email', 'address'] as $field) {
            $data[$field] = $data[$field] === '' ? null : $data[$field];
        }
        return $data;
    }

    private function findOrFail(string $id): array
    {
        return ctype_digit($id) ? ($this->suppliers->find((int) $id) ?? View::abort(404)) : View::abort(404);
    }
}
