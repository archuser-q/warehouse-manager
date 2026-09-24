<?php

namespace App\Controllers;

use App\Models\Warehouse;
use App\Models\Item;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

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
            'queryString' => $search !== '' ? '?' . http_build_query(['q' => $search]) : '',
            'imported' => $params['imported'] ?? null,
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

    public function exportExcel(Request $request, Response $response): Response
    {
        $warehouses = Warehouse::allUnpaginated();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nhà kho');
        $sheet->fromArray(['Tên kho', 'Vị trí', 'Mô tả'], null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        foreach ($warehouses as $w) {
            $sheet->fromArray([$w['name'], $w['location'], $w['description']], null, "A{$row}");
            $row++;
        }
        foreach (range('A', 'C') as $col) {
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
            ->withHeader('Content-Disposition', 'attachment; filename="nha-kho.xlsx"');
    }

    public function exportPdf(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        $html = $view->fetch('warehouses/export_pdf.twig', ['warehouses' => Warehouse::allUnpaginated()]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $response->getBody()->write($dompdf->output());
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="nha-kho.pdf"');
    }

    public function importForm(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'warehouses/import.twig', ['error' => isset($params['error'])]);
    }

    public function importExcel(Request $request, Response $response): Response
    {
        $file = $request->getUploadedFiles()['file'] ?? null;
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $response->withHeader('Location', '/warehouses/import?error=1')->withStatus(302);
        }

        $tmpPath = sys_get_temp_dir() . '/' . uniqid('import_') . '.xlsx';
        $file->moveTo($tmpPath);
        $rows = IOFactory::load($tmpPath)->getActiveSheet()->toArray();
        @unlink($tmpPath);

        $imported = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) continue;
            [$name, $location, $description] = array_pad($row, 3, null);
            $name = trim((string) $name);
            if ($name === '') continue;

            Warehouse::create([
                'name'        => $name,
                'location'    => trim((string) $location),
                'description' => trim((string) $description),
            ]);
            $imported++;
        }

        return $response->withHeader('Location', '/warehouses?imported=' . $imported)->withStatus(302);
    }
}