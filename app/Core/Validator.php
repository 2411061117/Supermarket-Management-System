<?php
namespace App\Core;

/**
 * Kiểm tra dữ liệu đầu vào.
 *   $v = new Validator($_POST, [
 *       'name'  => 'required|max:50|unique:units,name,' . $id,   // $id = bỏ qua chính bản ghi đang sửa
 *       'price' => 'required|integer|min:0',
 *   ], ['name' => 'Tên đơn vị', 'price' => 'Giá bán']);
 *   if ($v->fails()) { $v->errors(); }
 *
 * Rule: required, min, max, numeric, integer, email, phone, alpha_dash, in, same, unique, exists.
 * - Giá trị rỗng và không có `required` -> bỏ qua các rule còn lại (trường tùy chọn).
 * - min/max: với số (có rule numeric/integer) so sánh giá trị, còn lại so sánh độ dài chuỗi.
 */
class Validator
{
    private array $errors = [];

    public function __construct(private array $data, array $rules, private array $labels = [])
    {
        foreach ($rules as $field => $ruleString) {
            $this->validateField($field, explode('|', $ruleString));
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> field => thông báo lỗi đầu tiên */
    public function errors(): array
    {
        return $this->errors;
    }

    private function validateField(string $field, array $rules): void
    {
        $value = $this->data[$field] ?? null;
        $value = is_string($value) ? trim($value) : $value;
        $empty = $value === null || $value === '';
        $isNumeric = (bool) array_intersect($rules, ['numeric', 'integer']);

        foreach ($rules as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            if ($name === '') {
                continue;
            }
            if ($empty && $name !== 'required') {
                continue;
            }
            $error = $this->check($name, $arg, $field, $value, $isNumeric);
            if ($error !== null) {
                $this->errors[$field] = $error;
                return; // chỉ báo lỗi đầu tiên của mỗi trường
            }
        }
    }

    private function check(string $rule, ?string $arg, string $field, mixed $value, bool $isNumeric): ?string
    {
        $label = $this->labels[$field] ?? $field;
        $str = is_scalar($value) ? (string) $value : '';

        switch ($rule) {
            case 'required':
                return ($value === null || $value === '' || $value === []) ? "$label không được để trống." : null;

            case 'min':
            case 'max':
                $limit = (float) $arg;
                if ($isNumeric) {
                    $bad = $rule === 'min' ? (float) $str < $limit : (float) $str > $limit;
                    $msg = $rule === 'min' ? "$label phải lớn hơn hoặc bằng $arg." : "$label phải nhỏ hơn hoặc bằng $arg.";
                } else {
                    $len = mb_strlen($str);
                    $bad = $rule === 'min' ? $len < $limit : $len > $limit;
                    $msg = $rule === 'min' ? "$label phải có ít nhất $arg ký tự." : "$label tối đa $arg ký tự.";
                }
                return $bad ? $msg : null;

            case 'numeric':
                return is_numeric($str) ? null : "$label phải là số.";

            case 'integer':
                return preg_match('/^-?\d+$/', $str) ? null : "$label phải là số nguyên.";

            case 'email':
                return filter_var($str, FILTER_VALIDATE_EMAIL) ? null : "$label không đúng định dạng email.";

            case 'phone':
                return preg_match('/^(0|\+84)\d{9,10}$/', $str) ? null : "$label không đúng định dạng số điện thoại.";

            case 'alpha_dash':
                return preg_match('/^\w+$/', $str) ? null : "$label chỉ gồm chữ không dấu, số và dấu gạch dưới.";

            case 'in':
                return in_array($str, explode(',', (string) $arg), true) ? null : "$label không hợp lệ.";

            case 'same':
                $other = $this->data[$arg] ?? null;
                $other = is_string($other) ? trim($other) : $other;
                return $other === $value ? null : "$label không khớp.";

            case 'unique':
                // unique:table,column,ignoreId  (cột id luôn là "id")
                [$table, $column, $ignore] = array_pad(explode(',', (string) $arg), 3, null);
                return $this->exists($table, $column ?? $field, $str, $ignore !== null && $ignore !== '' ? (int) $ignore : null)
                    ? "$label đã tồn tại." : null;

            case 'exists':
                // exists:table,column  (mặc định cột id)
                [$table, $column] = array_pad(explode(',', (string) $arg), 2, 'id');
                return $this->exists($table, $column, $str) ? null : "$label không tồn tại.";
        }

        throw new \InvalidArgumentException("Rule không hỗ trợ: $rule");
    }

    /** Tên bảng/cột chỉ lấy từ code (không từ input) nhưng vẫn kiểm tra để tránh nhầm lẫn. */
    private function exists(string $table, string $column, string $value, ?int $ignoreId = null): bool
    {
        if (!preg_match('/^\w+$/', $table) || !preg_match('/^\w+$/', $column)) {
            throw new \InvalidArgumentException('Tên bảng/cột không hợp lệ.');
        }
        $sql = "SELECT 1 FROM `$table` WHERE `$column` = ?";
        $params = [$value];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}
