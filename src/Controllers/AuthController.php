<?php

namespace App\Controllers;

use App\Models\User;
use App\Support\JwtAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function loginForm(Request $request, Response $response): Response
    {
        $token = $request->getCookieParams()['auth_token'] ?? null;
        if ($token && JwtAuth::verify($token)) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $params = $request->getQueryParams();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'auth/login.twig', ['error' => isset($params['error'])]);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $response->withHeader('Location', '/login?error=1')->withStatus(302);
        }

        $token = JwtAuth::issue((int) $user['id'], $user['username']);
        $ttl = (int) ($_ENV['JWT_TTL'] ?? 86400);
        $secure = (($_ENV['APP_ENV'] ?? 'local') === 'production') ? '; Secure' : '';

        $cookie = "auth_token={$token}; Max-Age={$ttl}; Path=/; HttpOnly; SameSite=Lax{$secure}";

        return $response
            ->withHeader('Set-Cookie', $cookie)
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        $expired = 'auth_token=; Max-Age=0; Path=/; HttpOnly; SameSite=Lax';

        return $response
            ->withHeader('Set-Cookie', $expired)
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}