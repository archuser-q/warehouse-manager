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
            $host     = $_ENV['DB_HOST'] ?? null;
            $port     = $_ENV['DB_PORT'] ?? null;
            $dbName   = $_ENV['DB_DATABASE'] ?? null;
            $username = $_ENV['DB_USERNAME'] ?? null;
            $password = $_ENV['DB_PASSWORD'] ?? null;

            if (!$host || !$port || !$dbName || !$username || $password === null) {
                throw new RuntimeException("Thiếu cấu hình CSDL trong file .env. Vui lòng kiểm tra lại các biến DB_*.");
            }

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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