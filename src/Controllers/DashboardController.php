<?php

namespace App\Controllers;

use App\Models\Warehouse;
use App\Models\Item;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController
{
    public function index(Request $request, Response $response): Response
    {
        // 1. Lấy danh sách nhà kho và tạo bảng ánh xạ ID -> Tên kho
        $warehouses = Warehouse::all();
        $warehouseMap = [];
        foreach ($warehouses as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }

        // 2. Lấy số liệu thống kê gom nhóm theo nhà kho cho biểu đồ
        $byWarehouseRaw = Item::countByWarehouse();
        $chartLabels = [];
        $chartQuantities = [];
        foreach ($byWarehouseRaw as $row) {
            $wid = $row['_id'] ?? null;
            $chartLabels[] = $warehouseMap[$wid] ?? 'Chưa gán kho';
            $chartQuantities[] = (int) $row['total_quantity'];
        }

        // 3. Gom dữ liệu tổng hợp truyền vào Twig View
        $data = [
            'totalWarehouses' => Warehouse::count(),
            'totalItems'      => Item::count(),
            'totalQuantity'   => Item::totalQuantity(),
            'lowStockItems'   => Item::lowStock(),
            'chartLabels'     => $chartLabels,
            'chartQuantities' => $chartQuantities,
        ];

        $view = Twig::fromRequest($request);
        return $view->render($response, 'dashboard.twig', $data);
    }
}