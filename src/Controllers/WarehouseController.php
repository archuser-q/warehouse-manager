<?php

namespace App\Controllers;

use App\Models\Warehouse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class WarehouseController
{
    public function index(Request $request, Response $response): Response
    {
        $warehouses = Warehouse::all();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/list.twig', [
            'warehouses' => $warehouses,
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
        
        // Phương thức Warehouse::delete() trong Model đã tự xử lý Transaction 
        // để xóa sạch các items thuộc nhà kho này trước khi xóa nhà kho.
        Warehouse::delete($id);

        return $response->withHeader('Location', '/warehouses')->withStatus(302);
    }
}