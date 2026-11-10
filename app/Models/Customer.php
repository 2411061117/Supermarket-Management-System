<?php
namespace App\Models;

use App\Core\Model;

/** Khách hàng (tra theo SĐT ở POS, tích điểm 1 điểm / 10.000 ₫). */
class Customer extends Model
{
    protected string $table = 'customers';

    /** Số điện thoại chỉ gồm chữ số (cho phép 8–15 số). */
    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE phone = ?');
        $stmt->execute([self::normalizePhone($phone)]);
        return $stmt->fetch() ?: null;
    }
}
