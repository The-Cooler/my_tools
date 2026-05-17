<?php

declare(strict_types=1);

namespace Portal\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class ManageAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = getenv('PORTAL_MANAGE_TOKEN') ?: '';
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? '';
        $isLocal = in_array($remote, ['127.0.0.1', '::1'], true);

        if ($token !== '') {
            if ($request->getHeaderLine('X-Portal-Token') !== $token) {
                return $this->forbidden();
            }
        } elseif (!$isLocal) {
            return $this->forbidden();
        }

        return $handler->handle($request);
    }

    private function forbidden(): ResponseInterface
    {
        $response = new Response(403);
        $response->getBody()->write(json_encode([
            'ok' => false,
            'error' => '仅允许本机访问，或需提供有效的 X-Portal-Token。',
        ], JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
