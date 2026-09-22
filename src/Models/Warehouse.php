<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Warehouse
{
    /**
     * Lấy danh sách tất cả các nhà kho, sắp xếp theo tên
     */
    public static function all(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM warehouses ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Tìm thông tin một nhà kho theo ID
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM warehouses WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Tạo mới một nhà kho
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $sql = "INSERT INTO warehouses (name, location, created_at) VALUES (:name, :location, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name'     => $data['name'] ?? '',
            'location' => $data['location'] ?? null,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Cập nhật thông tin nhà kho theo ID
     */
    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();

        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('name', $data)) {
            $fields[] = "name = :name";
            $params['name'] = $data['name'];
        }

        if (array_key_exists('location', $data)) {
            $fields[] = "location = :location";
            $params['location'] = $data['location'];
        }

        if (empty($fields)) {
            return;
        }

        $sql = "UPDATE warehouses SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Xóa một nhà kho theo ID
     * (Xóa kho sẽ đồng thời xóa tất cả vật dụng thuộc kho đó)
     */
    public static function delete(int $id): void
    {
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 1. Xóa tất cả các vật dụng thuộc nhà kho này
            $stmtItems = $db->prepare("DELETE FROM items WHERE warehouse_id = :warehouse_id");
            $stmtItems->execute(['warehouse_id' => $id]);

            // 2. Xóa nhà kho
            $stmtWarehouse = $db->prepare("DELETE FROM warehouses WHERE id = :id");
            $stmtWarehouse->execute(['id' => $id]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Đếm tổng số nhà kho
     */
    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM warehouses");
        return (int) $stmt->fetchColumn();
    }
}