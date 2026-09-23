<?php

namespace App\Controllers;

use App\Models\Warehouse;
use App\Models\Item;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Models\StockMovement;

class DashboardController
{
    public function index(Request $request, Response $response): Response
    {
        // 1. Lấy toàn bộ nhà kho (không phân trang) để tạo bảng ánh xạ ID -> Tên kho
        $warehouses = Warehouse::allUnpaginated();
        $warehouseMap = [];
        foreach ($warehouses as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }

        // 2. Số liệu thống kê gom nhóm theo nhà kho cho biểu đồ
        $byWarehouseRaw = Item::countByWarehouse();
        $chartLabels = [];
        $chartQuantities = [];
        foreach ($byWarehouseRaw as $row) {
            $wid = $row['_id'] ?? null;
            $chartLabels[] = $warehouseMap[$wid] ?? 'Chưa gán kho';
            $chartQuantities[] = (int) $row['total_quantity'];
        }

        // 3. Hoạt động gần đây: gộp vật dụng + nhà kho mới thêm, sắp xếp theo thời gian tạo
        $recentActivityFull = $this->buildRecentActivity($warehouseMap, 50);

        $data = [
            'totalWarehouses'    => Warehouse::count(),
            'totalItems'         => Item::count(),
            'totalQuantity'      => Item::totalQuantity(),
            'lowStockItems'      => Item::lowStock(),
            'chartLabels'        => $chartLabels,
            'chartQuantities'    => $chartQuantities,
            'recentActivity'     => array_slice($recentActivityFull, 0, 5),
            'recentActivityFull' => $recentActivityFull,
            'stockMovements' => StockMovement::recent(50),
        ];

        $view = Twig::fromRequest($request);
        return $view->render($response, 'dashboard.twig', $data);
    }

    /**
     * Gộp vật dụng + nhà kho mới thêm gần đây thành 1 danh sách hoạt động, sắp theo created_at giảm dần.
     */
    private function buildRecentActivity(array $warehouseMap, int $limit): array
    {
        $activity = [];

        foreach (Item::recent($limit) as $it) {
            $activity[] = [
                'type'       => 'item',
                'name'       => $it['name'],
                'meta'       => 'Kho: ' . ($warehouseMap[$it['warehouse_id']] ?? 'Chưa gán kho'),
                'created_at' => $it['created_at'],
                'url'        => '/items/' . $it['id'],
            ];
        }

        foreach (Warehouse::recent($limit) as $w) {
            $activity[] = [
                'type'       => 'warehouse',
                'name'       => $w['name'],
                'meta'       => $w['location'] ?: '—',
                'created_at' => $w['created_at'],
                'url'        => '/warehouses/' . $w['id'],
            ];
        }

        usort($activity, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        return array_slice($activity, 0, $limit);
    }
}