<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Item
{
    /**
     * Lấy danh sách tất cả vật dụng (có hỗ trợ lọc theo nhà kho)
     */
    public static function all(?int $warehouseId = null): array
    {
        $db = Database::getInstance();

        if ($warehouseId !== null) {
            $stmt = $db->prepare("SELECT * FROM items WHERE warehouse_id = :warehouse_id ORDER BY name ASC");
            $stmt->execute(['warehouse_id' => $warehouseId]);
        } else {
            $stmt = $db->query("SELECT * FROM items ORDER BY name ASC");
        }

        return $stmt->fetchAll();
    }

    /**
     * Tìm thông tin một vật dụng theo ID
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM items WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Thêm mới một vật dụng
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $sql = "INSERT INTO items (warehouse_id, name, quantity, min_stock, price, created_at, updated_at)
                VALUES (:warehouse_id, :name, :quantity, :min_stock, :price, NOW(), NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'warehouse_id' => (int) ($data['warehouse_id'] ?? 0),
            'name'         => $data['name'] ?? '',
            'quantity'     => (int) ($data['quantity'] ?? 0),
            'min_stock'    => (int) ($data['min_stock'] ?? 0),
            'price'        => (float) ($data['price'] ?? 0.0),
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Cập nhật thông tin vật dụng theo ID
     */
    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();

        // Xây dựng câu truy vấn UPDATE động dựa trên dữ liệu truyền vào
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['warehouse_id', 'name', 'quantity', 'min_stock', 'price'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'quantity' || $field === 'min_stock' || $field === 'warehouse_id') {
                    $params[$field] = (int) $data[$field];
                } elseif ($field === 'price') {
                    $params[$field] = (float) $data[$field];
                } else {
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE items SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Xóa một vật dụng theo ID
     */
    public static function delete(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM items WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Đếm tổng số loại vật dụng
     */
    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM items");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Tính tổng số lượng tất cả vật dụng trong kho
     */
    public static function totalQuantity(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COALESCE(SUM(quantity), 0) FROM items");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lấy danh sách các vật dụng chạm hoặc dưới ngưỡng cảnh báo (quantity <= min_stock)
     */
    public static function lowStock(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM items WHERE quantity <= min_stock ORDER BY quantity ASC");
        return $stmt->fetchAll();
    }

    /**
     * Thống kê tổng số loại mặt hàng và tổng số lượng tồn kho theo từng nhà kho
     */
    public static function countByWarehouse(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    warehouse_id AS _id, 
                    COUNT(*) AS total_items, 
                    COALESCE(SUM(quantity), 0) AS total_quantity 
                FROM items 
                GROUP BY warehouse_id";

        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
}