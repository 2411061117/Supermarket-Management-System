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

    /** Dữ liệu người dùng cho session; null nếu tài khoản đã bị khóa hoặc không còn tồn tại. */
    public function findActiveForSession(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.full_name, u.store_id, r.code AS role, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? AND u.is_active = 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        $row['store_id'] = (int) $row['store_id'];
        return $row;
    }

    /** Một nhân viên kèm vai trò (không dùng để đưa thẳng ra view vì có password_hash). */
    public function findWithRole(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function countFiltered(string $q, int $roleId, string $status): int
    {
        [$where, $params] = $this->filter($q, $roleId, $status);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users u $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Không lấy password_hash. $limit/$offset là int do Paginator tạo ra nên nối thẳng vào SQL là an toàn. */
    public function search(string $q, int $roleId, string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->filter($q, $roleId, $status);
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.full_name, u.phone, u.email, u.is_active, u.last_login_at,
                    r.code AS role_code, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
               $where
              ORDER BY u.is_active DESC, r.id, u.full_name
              LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Số quản trị viên đang hoạt động (bỏ qua $excludeId) - để luôn còn ít nhất 1 người quản trị. */
    public function activeAdminCount(?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE r.code = 'admin' AND u.is_active = 1";
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND u.id <> ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Điều kiện WHERE: tìm theo tên đăng nhập/họ tên/SĐT/email, lọc vai trò ($roleId = 0: tất cả) và trạng thái. */
    private function filter(string $q, int $roleId, string $status): array
    {
        $conds = [];
        $params = [];
        if ($q !== '') {
            $like = self::likePattern($q);
            $conds[] = "(u.username LIKE ? ESCAPE '!' OR u.full_name LIKE ? ESCAPE '!' OR u.phone LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!')";
            array_push($params, $like, $like, $like, $like);
        }
        if ($roleId > 0) {
            $conds[] = 'u.role_id = ?';
            $params[] = $roleId;
        }
        if ($status === 'active' || $status === 'inactive') {
            $conds[] = 'u.is_active = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }
        return [$conds ? 'WHERE ' . implode(' AND ', $conds) : '', $params];
    }

    public function touchLastLogin(int $id): void
    {
        $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$id]);
    }
}
