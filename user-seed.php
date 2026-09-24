<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use App\Models\User;

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    echo "Cách dùng: php create_admin.php <username> <password>\n";
    exit(1);
}

if (User::findByUsername($username)) {
    echo "Tài khoản '{$username}' đã tồn tại.\n";
    exit(1);
}

$id = User::create($username, $password);
echo "Đã tạo tài khoản '{$username}' (id={$id}).\n";