<?php

namespace App\Models;

use App\Config\Database;

class User
{
    public static function findByUsername(string $username): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(string $username, string $password): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, NOW())"
        );
        $stmt->execute([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        return (int) $db->lastInsertId();
    }
}