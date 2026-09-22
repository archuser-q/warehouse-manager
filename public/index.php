<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Nạp biến môi trường từ .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(
    (bool) ($_ENV['APP_DEBUG'] ?? true),
    true,
    true
);

// Thiết lập Twig template engine
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Truyền đường dẫn hiện tại vào Twig để làm nổi bật mục đang chọn trên sidebar
$app->add(function ($request, $handler) use ($twig) {
    $twig->getEnvironment()->addGlobal('currentPath', $request->getUri()->getPath());
    return $handler->handle($request);
});

// Nạp routes
(require __DIR__ . '/../routes/web.php')($app);

$app->run();
