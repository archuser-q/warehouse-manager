<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class StockMovement
{
    /**
     * Ghi lại 1 lần nhập hàng
     */
    public static function log(int $itemId, string $itemName, ?int $warehouseId, ?string $warehouseName, int $quantity, ?string $note = null): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO stock_movements (item_id, item_name, warehouse_id, warehouse_name, quantity, note, created_at)
             VALUES (:item_id, :item_name, :warehouse_id, :warehouse_name, :quantity, :note, NOW())"
        );
        $stmt->execute([
            'item_id'        => $itemId,
            'item_name'      => $itemName,
            'warehouse_id'   => $warehouseId,
            'warehouse_name' => $warehouseName,
            'quantity'       => $quantity,
            'note'           => $note,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Lấy N lần nhập hàng gần nhất (dùng cho Dashboard)
     */
    public static function recent(int $limit = 50): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM stock_movements ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM stock_movements");
        return (int) $stmt->fetchColumn();
    }
}