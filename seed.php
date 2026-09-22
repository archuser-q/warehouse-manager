<?php
// Chạy: php seed.php
// Tạo dữ liệu mẫu để test nhanh dashboard và danh sách.

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use App\Models\Warehouse;
use App\Models\Item;

echo "Đang tạo dữ liệu mẫu...\n";

// Tạo nhà kho (Warehouse::create trả về ID dạng int)
$w1 = Warehouse::create([
    'name'        => 'Kho Trung Tâm', 
    'location'    => 'Quận 1, TP.HCM', 
    'description' => 'Kho chính'
]);

$w2 = Warehouse::create([
    'name'        => 'Kho Phụ - Bình Dương', 
    'location'    => 'Thuận An, Bình Dương', 
    'description' => 'Kho lưu trữ dự phòng'
]);

// Tạo vật dụng
Item::create([
    'name'         => 'Ốc vít 6mm', 
    'sku'          => 'OV-006', 
    'quantity'     => 500, 
    'unit'         => 'cái', 
    'min_stock'    => 100, 
    'price'        => 500, 
    'warehouse_id' => $w1
]);

Item::create([
    'name'         => 'Dây điện 2.5mm', 
    'sku'          => 'DD-025', 
    'quantity'     => 20, 
    'unit'         => 'cuộn', 
    'min_stock'    => 30, 
    'price'        => 250000, 
    'warehouse_id' => $w1
]);

Item::create([
    'name'         => 'Máy khoan cầm tay', 
    'sku'          => 'MK-001', 
    'quantity'     => 5, 
    'unit'         => 'cái', 
    'min_stock'    => 2, 
    'price'        => 1200000, 
    'warehouse_id' => $w1
]);

Item::create([
    'name'         => 'Găng tay bảo hộ', 
    'sku'          => 'GT-010', 
    'quantity'     => 8, 
    'unit'         => 'đôi', 
    'min_stock'    => 20, 
    'price'        => 30000, 
    'warehouse_id' => $w2
]);

Item::create([
    'name'         => 'Sơn chống rỉ', 
    'sku'          => 'SC-020', 
    'quantity'     => 40, 
    'unit'         => 'lon', 
    'min_stock'    => 10, 
    'price'        => 180000, 
    'warehouse_id' => $w2
]);

echo "Xong! Đã tạo 2 kho và 5 vật dụng mẫu.\n";