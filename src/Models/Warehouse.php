<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Warehouse
{
    public static function all(?string $search = null, int $page = 1, int $perPage = 5): array
    {
        $db = Database::getInstance();

        [$where, $params] = self::buildFilter($search);

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM warehouses{$where} ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countFiltered(?string $search = null): int
    {
        $db = Database::getInstance();

        [$where, $params] = self::buildFilter($search);

        $stmt = $db->prepare("SELECT COUNT(*) FROM warehouses{$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function buildFilter(?string $search): array
    {
        $search = trim((string) $search);
        if ($search === '') {
            return ['', []];
        }

        return [' WHERE name ILIKE :search OR location ILIKE :search', ['search' => '%' . $search . '%']];
    }

    public static function allUnpaginated(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM warehouses ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM warehouses WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $sql = "INSERT INTO warehouses (name, location, description, created_at) VALUES (:name, :location, :description, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name'        => $data['name'] ?? '',
            'location'    => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return (int) $db->lastInsertId();
    }

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

        if (array_key_exists('description', $data)) {
            $fields[] = "description = :description";
            $params['description'] = $data['description'];
        }

        if (empty($fields)) {
            return;
        }

        $sql = "UPDATE warehouses SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

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

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM warehouses");
        return (int) $stmt->fetchColumn();
    }
}