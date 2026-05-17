<?php

declare(strict_types=1);

namespace Portal\Middleware;

use Portal\Service\AdminAuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AdminAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (AdminAuthService::isLoggedIn()) {
            return $handler->handle($request);
        }

        $response = new Response(302);

        return $response->withHeader('Location', '/login');
    }
}
