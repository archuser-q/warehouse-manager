<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getInstance(): PDO
    {
        if (self::$connection === null) {
            $host     = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port     = $_ENV['DB_PORT'] ?? '5432';
            $dbName   = $_ENV['DB_DATABASE'] ?? 'warehouse_manager';
            $username = $_ENV['DB_USERNAME'] ?? 'postgres';
            $password = $_ENV['DB_PASSWORD'] ?? 'postgres';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

            $options = [
                // Báo lỗi dưới dạng Exception để dễ xử lý try-catch
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Mặc định trả về dữ liệu dạng mảng kết hợp (assoc array)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Giữ kiểu dữ liệu chuẩn từ DB (int/float không bị biến thành string)
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new PDOException("Lỗi kết nối PostgreSQL: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$connection;
    }

    private function __construct() {}

    private function __clone() {}
}