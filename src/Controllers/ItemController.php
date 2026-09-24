<?php

namespace App\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Models\StockMovement;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

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

        $qs = http_build_query(array_filter([
            'q' => $search !== '' ? $search : null,
            'warehouse_id' => $warehouseId,
        ]));

        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/list.twig', [
            'items' => $items,
            'warehouses' => $warehouses,
            'warehouseMap' => $warehouseMap,
            'selectedWarehouse' => $warehouseId,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'queryString' => $qs !== '' ? '?' . $qs : '',
            'imported' => $params['imported'] ?? null,
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
        $warehouseId = !empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
        $qty = (int) ($data['quantity'] ?? 0);

        $id = Item::create([
            'name'         => trim($data['name'] ?? ''),
            'sku'          => trim($data['sku'] ?? ''),
            'unit'         => trim($data['unit'] ?? ''),
            'quantity'     => $qty,
            'min_stock'    => (int) ($data['min_stock'] ?? 0),
            'price'        => (float) ($data['price'] ?? 0),
            'warehouse_id' => $warehouseId,
        ]);

        if ($qty > 0) {
            $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
            StockMovement::log($id, trim($data['name'] ?? ''), $warehouseId, $warehouse['name'] ?? null, $qty, 'Nhập ban đầu');
        }

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

    public function receiveForm(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $item = Item::find($id);

        if (!$item) {
            return $response->withHeader('Location', '/items')->withStatus(302);
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/receive.twig', ['item' => $item]);
    }

    public function receive(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $item = Item::find($id);

        if (!$item) {
            return $response->withHeader('Location', '/items')->withStatus(302);
        }

        $data = (array) $request->getParsedBody();
        $qty = (int) ($data['quantity'] ?? 0);
        $note = trim($data['note'] ?? '');

        if ($qty > 0) {
            Item::update($id, ['quantity' => (int) $item['quantity'] + $qty]);

            $warehouse = !empty($item['warehouse_id']) ? Warehouse::find((int) $item['warehouse_id']) : null;

            StockMovement::log(
                $id,
                $item['name'],
                $item['warehouse_id'] ?? null,
                $warehouse['name'] ?? null,
                $qty,
                $note !== '' ? $note : null
            );
        }

        return $response->withHeader('Location', '/items/' . $id)->withStatus(302);
    }
    public function exportExcel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $warehouseId = !empty($params['warehouse_id']) ? (int) $params['warehouse_id'] : null;
        $search = trim((string) ($params['q'] ?? ''));

        $items = Item::allUnpaginated($warehouseId, $search !== '' ? $search : null);
        $warehouseMap = [];
        foreach (Warehouse::allUnpaginated() as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vật dụng');

        $sheet->fromArray(['Tên vật dụng', 'SKU', 'Kho', 'Số lượng', 'Đơn vị', 'Ngưỡng tối thiểu', 'Đơn giá'], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($items as $it) {
            $sheet->fromArray([
                $it['name'], $it['sku'], $warehouseMap[$it['warehouse_id']] ?? '',
                $it['quantity'], $it['unit'], $it['min_stock'], $it['price'],
            ], null, "A{$row}");
            $row++;
        }
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $stream = fopen('php://temp', 'r+');
        (new Xlsx($spreadsheet))->save($stream);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="vat-dung.xlsx"');
    }

    public function exportPdf(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $warehouseId = !empty($params['warehouse_id']) ? (int) $params['warehouse_id'] : null;
        $search = trim((string) ($params['q'] ?? ''));

        $items = Item::allUnpaginated($warehouseId, $search !== '' ? $search : null);
        $warehouseMap = [];
        foreach (Warehouse::allUnpaginated() as $w) {
            $warehouseMap[$w['id']] = $w['name'];
        }

        $view = Twig::fromRequest($request);
        $html = $view->fetch('items/export_pdf.twig', ['items' => $items, 'warehouseMap' => $warehouseMap]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $response->getBody()->write($dompdf->output());
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="vat-dung.pdf"');
    }

    public function importForm(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'items/import.twig', ['error' => isset($params['error'])]);
    }

    public function importExcel(Request $request, Response $response): Response
    {
        $file = $request->getUploadedFiles()['file'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $response->withHeader('Location', '/items/import?error=1')->withStatus(302);
        }

        $tmpPath = sys_get_temp_dir() . '/' . uniqid('import_') . '.xlsx';
        $file->moveTo($tmpPath);

        $warehouseByName = [];
        foreach (Warehouse::allUnpaginated() as $w) {
            $warehouseByName[mb_strtolower(trim($w['name']))] = $w['id'];
        }

        $rows = IOFactory::load($tmpPath)->getActiveSheet()->toArray();
        @unlink($tmpPath);

        $imported = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // bỏ dòng tiêu đề
            [$name, $sku, $whName, $qty, $unit, $minStock, $price] = array_pad($row, 7, null);
            $name = trim((string) $name);
            if ($name === '') continue;

            Item::create([
                'name'         => $name,
                'sku'          => trim((string) $sku),
                'unit'         => trim((string) $unit),
                'quantity'     => (int) $qty,
                'min_stock'    => (int) $minStock,
                'price'        => (float) $price,
                'warehouse_id' => $warehouseByName[mb_strtolower(trim((string) $whName))] ?? null,
            ]);
            $imported++;
        }

        return $response->withHeader('Location', '/items?imported=' . $imported)->withStatus(302);
    }
}