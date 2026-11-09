<?php
namespace App\Models;

use App\Core\Model;

/** Ca làm việc của thu ngân. Mỗi người tối đa 1 ca đang mở (kiểm tra ở ứng dụng). */
class Shift extends Model
{
    protected string $table = 'shifts';

    /** Ca đang mở của nhân viên (null nếu chưa mở). */
    public function findOpenByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
}
