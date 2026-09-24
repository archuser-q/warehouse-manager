<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Item
{
    public static function all(?int $warehouseId = null, ?string $search = null, int $page = 1, int $perPage = 5): array
    {
        $db = Database::getInstance();

        [$where, $params] = self::buildFilter($warehouseId, $search);

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM items{$where} ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countFiltered(?int $warehouseId = null, ?string $search = null): int
    {
        $db = Database::getInstance();

        [$where, $params] = self::buildFilter($warehouseId, $search);

        $stmt = $db->prepare("SELECT COUNT(*) FROM items{$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function buildFilter(?int $warehouseId, ?string $search): array
    {
        $conditions = [];
        $params = [];

        if ($warehouseId !== null) {
            $conditions[] = "warehouse_id = :warehouse_id";
            $params['warehouse_id'] = $warehouseId;
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $conditions[] = "(name ILIKE :search OR sku ILIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $where = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    public static function allByWarehouse(int $warehouseId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM items WHERE warehouse_id = :warehouse_id ORDER BY name ASC");
        $stmt->execute(['warehouse_id' => $warehouseId]);
        return $stmt->fetchAll();
    }

    /*Used for exporting pdf/excel*/
    public static function allUnpaginated(?int $warehouseId = null, ?string $search = null): array
    {
        $db = Database::getInstance();
        [$where, $params] = self::buildFilter($warehouseId, $search);
        $stmt = $db->prepare("SELECT * FROM items{$where} ORDER BY name ASC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM items WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $sql = "INSERT INTO items (warehouse_id, name, sku, unit, quantity, min_stock, price, created_at, updated_at)
                VALUES (:warehouse_id, :name, :sku, :unit, :quantity, :min_stock, :price, NOW(), NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'warehouse_id' => (int) ($data['warehouse_id'] ?? 0),
            'name'         => $data['name'] ?? '',
            'sku'          => $data['sku'] ?? '',
            'unit'         => $data['unit'] ?? '',
            'quantity'     => (int) ($data['quantity'] ?? 0),
            'min_stock'    => (int) ($data['min_stock'] ?? 0),
            'price'        => (float) ($data['price'] ?? 0.0),
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getInstance();

        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['warehouse_id', 'name', 'sku', 'unit', 'quantity', 'min_stock', 'price'];

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

    public static function delete(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM items WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM items");
        return (int) $stmt->fetchColumn();
    }

    public static function totalQuantity(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COALESCE(SUM(quantity), 0) FROM items");
        return (int) $stmt->fetchColumn();
    }

    public static function lowStock(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM items WHERE quantity <= min_stock ORDER BY quantity ASC");
        return $stmt->fetchAll();
    }

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

    public static function recent(int $limit = 5): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM items ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}