<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Middleware\AuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';

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

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->add(function ($request, $handler) use ($twig) {
    $twig->getEnvironment()->addGlobal('currentPath', $request->getUri()->getPath());
    $user = $request->getAttribute('user');
    $twig->getEnvironment()->addGlobal('currentUser', $user['username'] ?? null);
    return $handler->handle($request);
});

$app->add(new AuthMiddleware());

(require __DIR__ . '/../routes/web.php')($app);

$app->run();