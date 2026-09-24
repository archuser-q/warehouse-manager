<?php

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth
{
    private static function secret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? null;
        if (!$secret) {
            throw new \RuntimeException('Thiếu cấu hình JWT_SECRET trong file .env.');
        }
        return $secret;
    }

    public static function issue(int $userId, string $username): string
    {
        $ttl = (int) ($_ENV['JWT_TTL'] ?? 86400);
        $now = time();

        $payload = [
            'sub'      => $userId,
            'username' => $username,
            'iat'      => $now,
            'exp'      => $now + $ttl,
        ];

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(self::secret(), 'HS256'));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null; 
        }
    }
}