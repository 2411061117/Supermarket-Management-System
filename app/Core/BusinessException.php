<?php
namespace App\Core;

/**
 * Lỗi nghiệp vụ có thể báo thẳng cho người dùng (hết hàng, chưa mở ca, tiền khách đưa thiếu...).
 * Khác với lỗi hệ thống (PDOException...) vốn không được hiện nguyên văn.
 * $details: dữ liệu kèm theo cho giao diện, vd danh sách sản phẩm thiếu tồn.
 */
class BusinessException extends \RuntimeException
{
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    public function details(): array
    {
        return $this->details;
    }
}
