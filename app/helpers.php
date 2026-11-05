<?php
use App\Core\App;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

/** Escape HTML – dùng cho MỌI dữ liệu xuất ra view (chống XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** URL tuyệt đối trong ứng dụng: url('/products') -> /BTHT6/public/products */
function url(string $path = '/'): string
{
    return App::basePath() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Giá trị đã nhập trước đó (khi form bị trả về do lỗi), nếu không có thì dùng $default (vd dữ liệu đang sửa). */
function old(string $field, mixed $default = ''): mixed
{
    $old = Session::old();
    return array_key_exists($field, $old) ? $old[$field] : $default;
}

/** Thông báo lỗi validate của 1 trường ('' nếu không lỗi). */
function error(string $field): string
{
    return Session::errors()[$field] ?? '';
}

/** Thêm class is-invalid của Bootstrap khi trường có lỗi: class="form-control<?= invalid('name') ?>" */
function invalid(string $field): string
{
    return error($field) !== '' ? ' is-invalid' : '';
}

/** In 1 partial trong app/Views/partials/: partial('field', ['name' => 'name', 'label' => 'Tên']) */
function partial(string $name, array $data = []): void
{
    View::partial($name, $data);
}

function money(int|float|string|null $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' ₫';
}

/** Đánh dấu menu đang active ('/' chỉ khớp chính xác, các mục khác khớp theo tiền tố). */
function active(string $prefix): string
{
    $path = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(App::basePath())), '/');
    $match = $prefix === '/' ? $path === '/' : str_starts_with($path, $prefix);
    return $match ? 'active' : '';
}
