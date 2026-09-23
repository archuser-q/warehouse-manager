<?php

namespace App\Controllers;

use App\Models\Warehouse;
use App\Models\Item;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class WarehouseController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $search = trim((string) ($params['q'] ?? ''));
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = 5;

        $warehouses = Warehouse::all($search !== '' ? $search : null, $page, $perPage);
        $total = Warehouse::countFiltered($search !== '' ? $search : null);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/list.twig', [
            'warehouses' => $warehouses,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $response->withHeader('Location', '/warehouses')->withStatus(302);
        }

        $items = Item::allByWarehouse($id);

        $totalQuantity = 0;
        foreach ($items as $it) {
            $totalQuantity += (int) $it['quantity'];
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/show.twig', [
            'warehouse'     => $warehouse,
            'items'         => $items,
            'totalItems'    => count($items),
            'totalQuantity' => $totalQuantity,
        ]);
    }

    public function createForm(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/form.twig', [
            'warehouse' => null,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        Warehouse::create([
            'name'        => trim($data['name'] ?? ''),
            'location'    => trim($data['location'] ?? ''),
            'description' => trim($data['description'] ?? ''),
        ]);

        return $response->withHeader('Location', '/warehouses')->withStatus(302);
    }

    public function editForm(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $warehouse = Warehouse::find($id);

        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/form.twig', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $data = (array) $request->getParsedBody();

        Warehouse::update($id, [
            'name'        => trim($data['name'] ?? ''),
            'location'    => trim($data['location'] ?? ''),
            'description' => trim($data['description'] ?? ''),
        ]);

        return $response->withHeader('Location', '/warehouses')->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        Warehouse::delete($id);

        return $response->withHeader('Location', '/warehouses')->withStatus(302);
    }
}