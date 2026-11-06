<?php
namespace App\Core;

use PDO;

/** Model cơ sở: CRUD chung bằng prepared statements. */
abstract class Model
{
    protected string $table;
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Chuỗi dùng cho LIKE ? ESCAPE '!' : thoát % _ ! để người dùng gõ ký tự đó thì tìm đúng ký tự đó.
     *   WHERE name LIKE ? ESCAPE '!'   với tham số self::likePattern($q)
     */
    protected static function likePattern(string $q): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->query("SELECT * FROM {$this->table} ORDER BY $orderBy")->fetchAll();
    }

    /** $data: ['column' => value]. Tên cột chỉ được lấy từ code, không lấy từ input người dùng. */
    public function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO {$this->table} ($cols) VALUES ($marks)")->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        $this->db->prepare("UPDATE {$this->table} SET $set WHERE id = ?")->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }
}
