<?php

declare(strict_types=1);

namespace Portal\Controller;

use Portal\Service\AdminAuthService;
use Portal\Service\ToolsRegistry;
use Portal\Service\ToolKeyService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class AuthController
{
    public function __construct(
        private readonly AdminAuthService $adminAuth,
        private readonly ToolKeyService $toolKeys,
        private readonly ToolsRegistry $registry,
    ) {
    }

    public function loginForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (AdminAuthService::isLoggedIn()) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        ob_start();
        require dirname(__DIR__, 2) . '/templates/login.php';
        $html = (string) ob_get_clean();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        try {
            if ($this->adminAuth->attemptLogin($username, $password)) {
                AdminAuthService::login();

                return $response->withHeader('Location', '/')->withStatus(302);
            }
        } catch (\Throwable $e) {
            $_SESSION['login_error'] = $e->getMessage();

            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $_SESSION['login_error'] = '账号或密码错误';

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        AdminAuthService::logout();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function useForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $tool = $this->registry->findById($id);

        if ($tool === null || ($tool['enabled'] ?? true) !== true) {
            $response = new Response(404);
            $response->getBody()->write('工具不存在');

            return $response;
        }

        if (($tool['runtime'] ?? '') === 'external') {
            $url = $this->registry->resolveUrl($tool);

            return $response->withHeader('Location', $url)->withStatus(302);
        }

        $ip = \Shared\ClientIp::resolve();
        if (\Shared\IpBlocklist::isBlocked($id, $ip)) {
            $response = new Response(403);
            $response->getBody()->write('该 IP 已被禁止访问此工具。');

            return $response;
        }

        $error = $_SESSION['use_error_' . $id] ?? null;
        unset($_SESSION['use_error_' . $id]);

        ob_start();
        $toolId = $id;
        $toolName = (string) ($tool['name'] ?? $id);
        require dirname(__DIR__, 2) . '/templates/use.php';
        $html = (string) ob_get_clean();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function useSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $tool = $this->registry->findById($id);

        if ($tool === null) {
            return $response->withStatus(404);
        }

        $body = $request->getParsedBody();
        $secret = trim((string) ($body['secret'] ?? ''));
        $deviceId = trim((string) ($body['device_id'] ?? ''));

        try {
            $auth = $this->toolKeys->authenticate($id, $secret, $deviceId);
            $baseUrl = $this->registry->resolveUrl($tool);
            $sep = str_contains($baseUrl, '?') ? '&' : '?';
            $target = $baseUrl . $sep . 'access_token=' . urlencode($auth['token']);

            return $response->withHeader('Location', $target)->withStatus(302);
        } catch (\Throwable $e) {
            $_SESSION['use_error_' . $id] = $e->getMessage();

            return $response->withHeader('Location', '/use/' . rawurlencode($id))->withStatus(302);
        }
    }
}
