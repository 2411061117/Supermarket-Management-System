<?php
/**
 * Kiểm thử lõi bán hàng (Bước 1, chưa có giao diện): FEFO, hết hạn, thiếu tồn, giá/giảm giá, ca, tiền mặt,
 * rollback, khách hàng, đồng thời. CẢNH BÁO: ghi dữ liệu thật vào DB trong config/config.php -> chỉ chạy trên DB thử.
 *
 *   php tests/checkout_test.php          # chạy toàn bộ
 * (tiến trình con nội bộ: php tests/checkout_test.php worker <userId> <productId> <startMicrotime>)
 */
declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $f = BASE_DIR . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($f)) require $f;
    }
});
require BASE_DIR . '/app/helpers.php';

use App\Core\App;
use App\Core\BusinessException;
use App\Core\Database;
use App\Models\Order;

$_SERVER['SCRIPT_NAME'] = '/index.php';
$config = require BASE_DIR . '/config/config.php';
if (getenv('DB_HOST')) $config['db']['host'] = getenv('DB_HOST');   // chạy trong Docker: host = tên container MySQL
App::boot($config);
$db = Database::connection();
$orders = new Order();

// ---- tiến trình con cho test đồng thời ----
if (($argv[1] ?? '') === 'worker') {
    [, , $uid, $pid, $start] = $argv;
    while (microtime(true) < (float) $start) usleep(200);
    try {
        $orders->checkout(1, (int) $uid, [['product_id' => (int) $pid, 'qty' => 1]], 'bank');
        echo 'OK';
    } catch (BusinessException $e) {
        echo 'FAIL';
    }
    exit;
}

$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $extra = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  [OK]   ' : '  [FAIL] ') . $name . ($ok || $extra === '' ? '' : "  -> $extra") . "\n";
}
function expectBusiness(callable $fn): ?BusinessException {
    try { $fn(); } catch (BusinessException $e) { return $e; }
    return null;
}
function scalar(string $sql, array $p = []): mixed {
    global $db; $s = $db->prepare($sql); $s->execute($p); return $s->fetchColumn();
}

// ---- dữ liệu thử ----
$run = bin2hex(random_bytes(3));
$mkUser = function (string $name) use ($db, $run): int {
    $db->prepare("INSERT INTO users (store_id, role_id, username, password_hash, full_name) VALUES (1, 2, ?, 'x', ?)")
       ->execute(["t_{$name}_$run", $name]);
    return (int) $db->lastInsertId();
};
$openShift = function (int $uid) use ($db): int {
    $db->prepare("INSERT INTO shifts (store_id, user_id, opening_cash, status) VALUES (1, ?, 100000, 'open')")->execute([$uid]);
    return (int) $db->lastInsertId();
};
$mkProduct = function (string $tag, int $price, bool $active = true) use ($db, $run): int {
    $db->prepare("INSERT INTO products (sku, name, category_id, unit_id, sale_price, is_active) VALUES (?, ?, 1, 1, ?, ?)")
       ->execute(["T{$tag}_$run", "SP $tag", $price, (int) $active]);
    return (int) $db->lastInsertId();
};
$mkBatch = function (int $pid, ?string $expiry, int $qty, int $cost = 1000) use ($db, $run): int {
    static $n = 0;
    $db->prepare("INSERT INTO product_batches (store_id, product_id, batch_code, expiry_date, cost_price, qty_received, qty_remaining) VALUES (1, ?, ?, ?, ?, ?, ?)")
       ->execute([$pid, 'L' . (++$n) . "_$run", $expiry, $cost, $qty, $qty]);
    return (int) $db->lastInsertId();
};
$remain = fn(int $bid): int => (int) scalar('SELECT qty_remaining FROM product_batches WHERE id = ?', [$bid]);
$orderCount = fn(): int => (int) scalar('SELECT COUNT(*) FROM orders');
$line = fn(int $pid, int $qty): array => ['product_id' => $pid, 'qty' => $qty];
$day = fn(int $d): string => date('Y-m-d', strtotime("$d days"));

$cashier = $mkUser('cashier'); $shift = $openShift($cashier);
$other = $mkUser('other');     $otherShift = $openShift($other);
$noShift = $mkUser('noshift');

echo "1. FEFO: tách 2 lô, trừ lô sớm hạn trước\n";
$p = $mkProduct('fefo', 10000);
$late = $mkBatch($p, $day(60), 10); $soon = $mkBatch($p, $day(10), 4); $none = $mkBatch($p, null, 5);
$r = $orders->checkout(1, $cashier, [$line($p, 6)], 'cash', 0, null, 100000);
$rows = $orders->items($r['id']);
check('hóa đơn 2 dòng', count($rows) === 2, (string) count($rows));
check('lô sớm hạn hết (4), lô muộn trừ 2', $remain($soon) === 0 && $remain($late) === 8 && $remain($none) === 5);
check('tổng 60.000, thối 40.000', $r['total'] === 60000 && $r['change'] === 40000);
check('mã hóa đơn đúng dạng', (bool) preg_match('/^HD-\d{8}-\d{4}$/', $r['code']), $r['code']);
$r2 = $orders->checkout(1, $cashier, [$line($p, 13)], 'bank');   // 8 + 5 = 13: hết lô muộn rồi tới lô không HSD
check('lô không HSD xếp cuối', $remain($late) === 0 && $remain($none) === 0);

echo "2. Lô hết hạn không bị bán\n";
$p = $mkProduct('exp', 5000);
$expired = $mkBatch($p, $day(-1), 50); $ok = $mkBatch($p, $day(30), 3);
$e = expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 4)], 'bank'));
check('bán 4 khi chỉ còn 3 bán được -> từ chối', $e !== null && ($e->details()['problems'][0]['available'] ?? -1) === 3);
check('lô hết hạn nguyên vẹn', $remain($expired) === 50);

echo "3. Thiếu tồn / hết hàng: không tạo hóa đơn, không trừ kho\n";
$a = $mkProduct('a', 1000); $ba = $mkBatch($a, null, 5);
$b = $mkProduct('b', 1000);   // không có lô
$before = $orderCount();
$e = expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($a, 2), $line($b, 1)], 'bank'));
check('bị từ chối, báo đúng SP hết hàng', $e !== null && count($e->details()['problems'] ?? []) === 1);
check('không trừ kho, không có hóa đơn', $remain($ba) === 5 && $orderCount() === $before);

echo "4. Đồng thời: 2 thu ngân cùng mua phần tồn cuối\n";
$p = $mkProduct('race', 1000); $br = $mkBatch($p, null, 1);
$start = microtime(true) + 1.5;
$procs = [];
foreach ([$cashier, $other] as $uid) {
    $procs[] = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $uid, (string) $p, (string) $start], [1 => ['pipe', 'w']], $pipes);
    $procs[count($procs) - 1] = [$procs[count($procs) - 1], $pipes[1]];
}
$outs = array_map(function ($x) { $o = stream_get_contents($x[1]); proc_close($x[0]); return $o; }, $procs);
sort($outs);
check('đúng 1 OK + 1 FAIL', $outs === ['FAIL', 'OK'], implode(',', $outs));
check('tồn = 0, không âm', $remain($br) === 0);

echo "5. Server bỏ qua giá/tổng client; kiểm tra giảm giá\n";
$p = $mkProduct('price', 20000); $bp = $mkBatch($p, null, 20);
$r = $orders->checkout(1, $cashier, [['product_id' => $p, 'qty' => 2, 'price' => 1, 'total' => 1, 'batch_id' => 999999]], 'bank');
check('giá lấy từ DB: tổng 40.000', $r['total'] === 40000);
check('unit_price lưu 20.000', (int) scalar('SELECT unit_price FROM order_items WHERE order_id = ?', [$r['id']]) === 20000);
check('giảm giá > tạm tính bị từ chối', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 20001)) !== null);
check('giảm giá âm bị từ chối', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', -1)) !== null);
$r = $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 5000);
check('giảm 5.000 -> tổng 15.000', $r['total'] === 15000 && (int) scalar('SELECT amount FROM payments WHERE order_id = ?', [$r['id']]) === 15000);
check('giảm 100% (tổng 0) bị từ chối', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 20000)) !== null);
check('qty âm / 0 / rỗng / chuỗi lạ bị từ chối',
    expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, -1)], 'bank')) !== null
    && expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 0)], 'bank')) !== null
    && expectBusiness(fn() => $orders->checkout(1, $cashier, [], 'bank')) !== null
    && expectBusiness(fn() => $orders->checkout(1, $cashier, [['product_id' => $p, 'qty' => '1; DROP']], 'bank')) !== null);
$r = $orders->checkout(1, $cashier, [$line($p, 1), $line($p, 2)], 'bank');
check('dòng trùng sản phẩm được gộp (3 x 20.000)', $r['total'] === 60000);

echo "6. Ca làm việc\n";
check('chưa mở ca -> từ chối', expectBusiness(fn() => $orders->checkout(1, $noShift, [$line($p, 1)], 'bank')) !== null);
$db->prepare("UPDATE shifts SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$otherShift]);
check('ca đã đóng -> từ chối', expectBusiness(fn() => $orders->checkout(1, $other, [$line($p, 1)], 'bank')) !== null);
check('hóa đơn gắn đúng shift_id', (int) scalar('SELECT shift_id FROM orders WHERE code = ?', [$r['code']]) === $shift);

echo "7. Tiền mặt\n";
check('thiếu tiền -> chặn', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'cash', 0, null, 19999)) !== null);
check('không nhập tiền khách đưa -> chặn', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'cash')) !== null);
check('đủ -> thối 0', $orders->checkout(1, $cashier, [$line($p, 1)], 'cash', 0, null, 20000)['change'] === 0);
check('dư -> thối đúng', $orders->checkout(1, $cashier, [$line($p, 1)], 'cash', 0, null, 50000)['change'] === 30000);
check('phương thức lạ -> chặn', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bitcoin')) !== null);

echo "8. Rollback khi lỗi giữa chừng (trigger làm INSERT payments thất bại)\n";
$p = $mkProduct('rb', 1000); $brb = $mkBatch($p, null, 10);
$p2 = $mkProduct('rb2', 1000); $brb2 = $mkBatch($p2, null, 10);
$db->prepare("INSERT INTO customers (phone, full_name, points) VALUES ('0900000$run', 'KH rollback', 5)")->execute();
$db->exec("DROP TRIGGER IF EXISTS t_fail_payment");
$db->exec("CREATE TRIGGER t_fail_payment BEFORE INSERT ON payments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'giả lập lỗi'");
$before = $orderCount();
try {
    $orders->checkout(1, $cashier, [$line($p, 3), $line($p2, 4)], 'bank', 0, '0900000' . $run);
    $threw = false;
} catch (\Throwable $t) { $threw = true; }
$db->exec("DROP TRIGGER t_fail_payment");
check('ném lỗi', $threw);
check('không hóa đơn, không trừ kho, không cộng điểm',
    $orderCount() === $before && $remain($brb) === 10 && $remain($brb2) === 10
    && (int) scalar('SELECT points FROM customers WHERE phone = ?', ['0900000' . $run]) === 5);
check('hóa đơn lỗi không để lại dòng/payment mồ côi', (int) scalar('SELECT COUNT(*) FROM order_items WHERE order_id NOT IN (SELECT id FROM orders)') === 0);

echo "9. Khách hàng theo SĐT\n";
$p = $mkProduct('cus', 10000); $bc = $mkBatch($p, null, 100);
$phone = '0911' . random_int(100000, 999999);
$db->prepare("INSERT INTO customers (phone, full_name, points) VALUES (?, 'KH', 10)")->execute([$phone]);
$r = $orders->checkout(1, $cashier, [$line($p, 5)], 'bank', 0, " $phone ");
check('50.000 ₫ -> +5 điểm', $r['points'] === 5 && (int) scalar('SELECT points FROM customers WHERE phone = ?', [$phone]) === 15);
check('SĐT không tồn tại -> lỗi, không bán', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 0, '0999999999')) !== null && $remain($bc) === 95);
check('SĐT rác -> lỗi', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 0, 'abc')) !== null);
$r = $orders->checkout(1, $cashier, [$line($p, 1)], 'bank', 0, '');
check('bỏ trống -> bán bình thường, không khách', $r['points'] === 0 && scalar('SELECT customer_id FROM orders WHERE id = ?', [$r['id']]) === null);

echo "10. Sản phẩm ngừng bán\n";
$p = $mkProduct('off', 1000, false); $mkBatch($p, null, 5);
check('bị từ chối', expectBusiness(fn() => $orders->checkout(1, $cashier, [$line($p, 1)], 'bank')) !== null);

echo "\nKết quả: $pass đạt, $fail lỗi\n";
exit($fail ? 1 : 0);
