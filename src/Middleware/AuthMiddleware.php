<?php

namespace App\Middleware;

use App\Support\JwtAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Factory\ResponseFactory;

class AuthMiddleware
{
    private array $publicPaths = ['/login'];

    public function __invoke(Request $request, Handler $handler): Response
    {
        $path = $request->getUri()->getPath();

        if (in_array($path, $this->publicPaths, true)) {
            return $handler->handle($request);
        }

        $token = $request->getCookieParams()['auth_token'] ?? null;
        $payload = $token ? JwtAuth::verify($token) : null;

        if (!$payload) {
            return (new ResponseFactory())->createResponse()
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        $request = $request->withAttribute('user', $payload);

        return $handler->handle($request);
    }
}