<?php

use App\Controllers\DashboardController;
use App\Controllers\WarehouseController;
use App\Controllers\ItemController;

return function (\Slim\App $app) {
    $app->get('/', [DashboardController::class, 'index']);
    $app->get('/dashboard', [DashboardController::class, 'index']);

    // Warehouses (Kho)
    $app->get('/warehouses', [WarehouseController::class, 'index']);
    $app->get('/warehouses/create', [WarehouseController::class, 'createForm']);
    $app->post('/warehouses', [WarehouseController::class, 'store']);
    $app->get('/warehouses/{id}', [WarehouseController::class, 'show']);
    $app->get('/warehouses/{id}/edit', [WarehouseController::class, 'editForm']);
    $app->post('/warehouses/{id}', [WarehouseController::class, 'update']);
    $app->post('/warehouses/{id}/delete', [WarehouseController::class, 'delete']);

    // Items (Vật dụng)
    $app->get('/items', [ItemController::class, 'index']);
    $app->get('/items/create', [ItemController::class, 'createForm']);
    $app->post('/items', [ItemController::class, 'store']);
    $app->get('/items/{id}', [ItemController::class, 'show']);
    $app->get('/items/{id}/edit', [ItemController::class, 'editForm']);
    $app->post('/items/{id}', [ItemController::class, 'update']);
    $app->post('/items/{id}/delete', [ItemController::class, 'delete']);

    // History (Lịch sử)
    $app->get('/items/{id}/receive', [ItemController::class, 'receiveForm']);
    $app->post('/items/{id}/receive', [ItemController::class, 'receive']);
};
