<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Validate $_POST. Lỗi -> quay lại $backTo kèm thông báo lỗi + dữ liệu đã nhập (trừ mật khẩu).
     * Hợp lệ -> trả về mảng dữ liệu đã trim, chỉ gồm các field có trong $rules.
     */
    protected function validate(array $rules, array $labels, string $backTo): array
    {
        $validator = new Validator($_POST, $rules, $labels);
        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $backTo);
        }
        $data = [];
        foreach (array_keys($rules) as $field) {
            $value = $_POST[$field] ?? null;
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        return $data;
    }

    protected function backWithErrors(array $errors, string $backTo): never
    {
        $old = array_diff_key($_POST, array_flip(['_csrf', 'password', 'password_confirmation']));
        Session::flashErrors($errors, $old);
        redirect($backTo);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }
}
