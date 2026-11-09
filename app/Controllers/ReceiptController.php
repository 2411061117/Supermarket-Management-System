<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Batch;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Supplier;
use PDOException;

/** Phiếu nhập kho (admin, NV kho): danh sách + chi tiết. Phiếu đã lập là chứng từ nên không sửa/xóa. */
class ReceiptController extends Controller
{
    private const PER_PAGE = 10;
    private const MAX_LINES = 50;
    private const LINE_FIELDS = ['product_id', 'batch_code', 'expiry_date', 'qty', 'cost_price'];

    private GoodsReceipt $receipts;

    public function __construct()
    {
        $this->receipts = new GoodsReceipt();
    }

    public function index(): void
    {
        $storeId = (int) Auth::user()['store_id'];
        $q = $this->input('q', '');
        $q = is_string($q) ? $q : '';
        $supplierId = $this->input('supplier_id', '');
        $supplierId = is_string($supplierId) && ctype_digit($supplierId) ? (int) $supplierId : 0;
        $from = $this->dateInput('from');
        $to = $this->dateInput('to');
        $rangeError = '';
        if ($from !== '' && $to !== '' && $from > $to) {   // khoảng ngày ngược: hoán đổi cho người dùng đỡ thấy trống
            [$from, $to] = [$to, $from];
            $rangeError = 'Đã đổi chỗ "Từ ngày" và "Đến ngày" vì khoảng ngày bị ngược.';
        }

        $paginator = new Paginator($this->receipts->countFiltered($storeId, $q, $supplierId, $from, $to), self::PER_PAGE);
        $this->view('receipts/index', [
            'title'      => 'Phiếu nhập kho',
            'receipts'   => $this->receipts->search($storeId, $q, $supplierId, $from, $to, $paginator->limit, $paginator->offset),
            'paginator'  => $paginator,
            'suppliers'  => array_column((new Supplier())->all('name ASC'), 'name', 'id'),
            'q'          => $q,
            'supplierId' => $supplierId,
            'from'       => $from,
            'to'         => $to,
            'notice'     => $rangeError,
        ]);
    }

    public function create(): void
    {
        $this->view('receipts/form', [
            'title'     => 'Lập phiếu nhập kho',
            'suppliers' => (new Supplier())->activeOptions(),
            'products'  => (new Product())->activeOptions(),
            'maxLines'  => self::MAX_LINES,
        ]);
    }

    /**
     * Lưu phiếu trong 1 transaction: phiếu -> mỗi dòng 1 lô (qty_received = qty_remaining) -> liên kết dòng.
     * Lỗi bất kỳ thì rollback, không để lại phiếu hoặc lô dở dang.
     */
    public function store(): void
    {
        $back = '/receipts/create';
        $items = $this->normalizeItems($tooMany);
        $_POST['note'] = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';

        $rules = ['supplier_id' => 'required|integer|exists:suppliers,id', 'note' => 'max:255'];
        $labels = ['supplier_id' => 'Nhà cung cấp', 'note' => 'Ghi chú'];
        foreach ($items as $i => $_) {
            $n = $i + 1;
            $rules += [
                "items.$i.product_id"  => 'required|integer|exists:products,id',
                "items.$i.batch_code"  => 'required|max:50',
                "items.$i.expiry_date" => 'date|after_or_equal:today',
                "items.$i.qty"         => 'required|integer|min:1|max:1000000',
                "items.$i.cost_price"  => 'required|integer|min:0|max:999999999',
            ];
            $labels += [
                "items.$i.product_id"  => "Sản phẩm (dòng $n)",
                "items.$i.batch_code"  => "Mã lô (dòng $n)",
                "items.$i.expiry_date" => "HSD (dòng $n)",
                "items.$i.qty"         => "Số lượng (dòng $n)",
                "items.$i.cost_price"  => "Giá nhập (dòng $n)",
            ];
        }
        $errors = (new Validator($_POST, $rules, $labels))->errors();
        if (!$items) {
            $errors['items'] = 'Phiếu phải có ít nhất 1 dòng hàng.';
        } elseif ($tooMany) {
            $errors['items'] = 'Mỗi phiếu tối đa ' . self::MAX_LINES . ' dòng.';
        }
        $errors += $this->businessErrors($items, $errors);
        if ($errors) {
            $this->backWithErrors($errors, $back);
        }

        $user = Auth::user();
        $result = $this->save((int) $_POST['supplier_id'], (int) $user['id'], (int) $user['store_id'], $_POST['note'], $items);
        if (isset($result['errors'])) {
            $this->backWithErrors($result['errors'], $back);
        }
        Session::flash('success', "Đã lập phiếu nhập {$result['code']} ({$result['lines']} dòng, tổng " . money($result['total']) . ').');
        redirect('/receipts/' . $result['id']);
    }

    public function show(string $id): void
    {
        $receipt = ctype_digit($id) ? $this->receipts->findForStore((int) $id, (int) Auth::user()['store_id']) : null;
        if ($receipt === null) {
            View::abort(404);
        }
        $items = $this->receipts->items((int) $receipt['id']);
        $this->view('receipts/show', [
            'title'   => 'Phiếu nhập ' . $receipt['code'],
            'receipt' => $receipt,
            'items'   => $items,
            'total'   => array_sum(array_map(fn($i) => $i['cost_price'] * $i['qty_received'], $items)),
        ]);
    }

    /** Ngày hợp lệ dạng Y-m-d, ngược lại '' (không lọc). */
    private function dateInput(string $key): string
    {
        $v = $this->input($key, '');
        if (!is_string($v)) {
            return '';
        }
        $d = \DateTime::createFromFormat('!Y-m-d', $v);
        return $d && $d->format('Y-m-d') === $v ? $v : '';
    }

    /** Chuẩn hóa $_POST['items'] thành danh sách các dòng (đủ 5 trường chuỗi đã trim), tối đa MAX_LINES dòng. */
    private function normalizeItems(?bool &$tooMany): array
    {
        $raw = $_POST['items'] ?? [];
        $raw = is_array($raw) ? array_values($raw) : [];
        $tooMany = count($raw) > self::MAX_LINES;
        $items = [];
        foreach (array_slice($raw, 0, self::MAX_LINES) as $row) {
            $row = is_array($row) ? $row : [];
            $line = [];
            foreach (self::LINE_FIELDS as $f) {
                $line[$f] = is_string($row[$f] ?? null) ? trim($row[$f]) : '';
            }
            $items[] = $line;
        }
        $_POST['items'] = $items;   // để chỉ số dòng của lỗi và dữ liệu cũ (old) khớp với nhau
        return $items;
    }

    /** Các lỗi cần tra CSDL: NCC/sản phẩm phải đang hoạt động; mã lô không trùng trong phiếu và với lô đã có. */
    private function businessErrors(array $items, array $errors): array
    {
        $extra = [];
        if (!isset($errors['supplier_id'])) {
            $active = (new Supplier())->find((int) $_POST['supplier_id']);
            if ($active === null || (int) $active['is_active'] !== 1) {
                $extra['supplier_id'] = 'Nhà cung cấp đã ngừng hợp tác, không thể nhập hàng.';
            }
        }

        $flags = (new Product())->activeFlags(array_column(
            array_filter($items, fn($r, $i) => !isset($errors["items.$i.product_id"]), ARRAY_FILTER_USE_BOTH),
            'product_id'
        ));
        $batches = new Batch();
        $seen = [];
        foreach ($items as $i => $r) {
            $n = $i + 1;
            if (!isset($errors["items.$i.product_id"]) && ($flags[(int) $r['product_id']] ?? 0) !== 1) {
                $extra["items.$i.product_id"] = "Sản phẩm (dòng $n) đã ngừng bán, không thể nhập hàng.";
                continue;
            }
            if (isset($errors["items.$i.product_id"]) || isset($errors["items.$i.batch_code"])) {
                continue;
            }
            $key = (int) $r['product_id'] . '|' . mb_strtolower($r['batch_code']);
            if (isset($seen[$key])) {
                $extra["items.$i.batch_code"] = "Mã lô (dòng $n) trùng với dòng " . ($seen[$key] + 1) . ' của phiếu này.';
            } elseif ($batches->codeExists((int) $r['product_id'], $r['batch_code'])) {
                $extra["items.$i.batch_code"] = "Mã lô (dòng $n) đã tồn tại cho sản phẩm này.";
            }
            $seen[$key] ??= $i;
        }
        return $extra;
    }

    /** @return array{id:int,code:string,lines:int,total:int}|array{errors:array} */
    private function save(int $supplierId, int $userId, int $storeId, string $note, array $items): array
    {
        $receipts = new GoodsReceipt();
        $batches = new Batch();

        for ($attempt = 1; ; $attempt++) {
            try {
                return Database::transaction(function () use ($receipts, $batches, $supplierId, $userId, $storeId, $note, $items) {
                    // Khóa các sản phẩm (theo thứ tự id để không deadlock) rồi kiểm tra lại mã lô:
                    // 2 phiếu nhập cùng lúc cho 1 sản phẩm sẽ được xử lý lần lượt, không thể trùng mã lô.
                    $ids = array_values(array_unique(array_map(fn($r) => (int) $r['product_id'], $items)));
                    sort($ids);
                    $lock = Database::connection()->prepare(
                        'SELECT id FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id FOR UPDATE'
                    );
                    $lock->execute($ids);
                    $errors = [];
                    foreach ($items as $i => $r) {
                        if ($batches->codeExists((int) $r['product_id'], $r['batch_code'])) {
                            $errors["items.$i.batch_code"] = 'Mã lô (dòng ' . ($i + 1) . ') đã tồn tại cho sản phẩm này.';
                        }
                    }
                    if ($errors) {
                        return ['errors' => $errors];
                    }

                    $code = $receipts->nextCode();
                    $receiptId = $receipts->create([
                        'code'        => $code,
                        'store_id'    => $storeId,
                        'supplier_id' => $supplierId,
                        'user_id'     => $userId,
                        'note'        => $note === '' ? null : $note,
                    ]);
                    $total = 0;
                    foreach ($items as $r) {
                        $batchId = $batches->create([
                            'store_id'      => $storeId,
                            'product_id'    => (int) $r['product_id'],
                            'batch_code'    => $r['batch_code'],
                            'expiry_date'   => $r['expiry_date'] === '' ? null : $r['expiry_date'],
                            'cost_price'    => (int) $r['cost_price'],
                            'qty_received'  => (int) $r['qty'],
                            'qty_remaining' => (int) $r['qty'],
                        ]);
                        $receipts->addItem($receiptId, $batchId);
                        $total += (int) $r['cost_price'] * (int) $r['qty'];
                    }
                    return ['id' => $receiptId, 'code' => $code, 'lines' => count($items), 'total' => $total];
                });
            } catch (PDOException $e) {
                // Trùng mã phiếu do 2 người lập cùng lúc (UNIQUE): thử lại với mã kế tiếp
                if ($e->getCode() !== '23000' || $attempt >= 5) {
                    throw $e;
                }
            }
        }
    }
}
