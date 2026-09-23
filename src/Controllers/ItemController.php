<?php

namespace App\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ItemController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $warehouseId = !empty($params['warehouse_id']) ? (int) $params['warehouse_id'] : null;
        $search = trim((string) ($params['q'] ?? ''));
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = 5;

        $items = Item::all($warehouseId, $search !== '' ? $search : null, $page, $perPage);
        $total = Item::countFiltered($warehouseId, $search !== '' ? $search : null);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $warehouses = Warehouse::allUnpaginated();
        
        $warehouseMap = [];
        foreach ($warehouses as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/list.twig', [
            'items' => $items,
            'warehouses' => $warehouses,
            'warehouseMap' => $warehouseMap,
            'selectedWarehouse' => $warehouseId,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $item = Item::find($id);

        if (!$item) {
            return $response->withHeader('Location', '/items')->withStatus(302);
        }

        $warehouse = !empty($item['warehouse_id']) ? Warehouse::find((int) $item['warehouse_id']) : null;

        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/show.twig', [
            'item'      => $item,
            'warehouse' => $warehouse,
        ]);
    }

    public function createForm(Request $request, Response $response): Response
    {
        $warehouses = Warehouse::allUnpaginated();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/form.twig', [
            'item' => null,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        Item::create([
            'name'         => trim($data['name'] ?? ''),
            'sku'          => trim($data['sku'] ?? ''),
            'unit'         => trim($data['unit'] ?? ''),
            'quantity'     => (int) ($data['quantity'] ?? 0),
            'min_stock'    => (int) ($data['min_stock'] ?? 0),
            'price'        => (float) ($data['price'] ?? 0),
            'warehouse_id' => !empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
        ]);

        return $response->withHeader('Location', '/items')->withStatus(302);
    }

    public function editForm(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $item = Item::find($id);
        $warehouses = Warehouse::allUnpaginated();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/form.twig', [
            'item' => $item,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $data = (array) $request->getParsedBody();

        Item::update($id, [
            'name'         => trim($data['name'] ?? ''),
            'sku'          => trim($data['sku'] ?? ''),
            'unit'         => trim($data['unit'] ?? ''),
            'quantity'     => (int) ($data['quantity'] ?? 0),
            'min_stock'    => (int) ($data['min_stock'] ?? 0),
            'price'        => (float) ($data['price'] ?? 0),
            'warehouse_id' => !empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
        ]);

        return $response->withHeader('Location', '/items')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        Item::delete($id);

        return $response->withHeader('Location', '/items')->withStatus(302);
    }
}