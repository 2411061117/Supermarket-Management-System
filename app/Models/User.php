<?php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findActiveByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.username = ? AND u.is_active = 1'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function touchLastLogin(int $id): void
    {
        $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$id]);
    }
}
