<?php

declare(strict_types=1);

namespace Portal\Controller;

use Portal\Service\AdminAuthService;
use Portal\Service\AccessTokenService;
use Portal\Service\ToolsRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class GoController
{
    public function __construct(
        private readonly ToolsRegistry $registry,
        private readonly AccessTokenService $tokens,
    ) {
    }

    public function redirect(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $tool = $this->registry->findById($id);

        if ($tool === null || ($tool['enabled'] ?? true) !== true) {
            $response = new Response(404);
            $response->getBody()->write('Tool not found.');

            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        $runtime = $tool['runtime'] ?? '';

        if ($runtime === 'external') {
            return $response
                ->withHeader('Location', $this->registry->resolveUrl($tool))
                ->withStatus(302);
        }

        if (!AdminAuthService::isLoggedIn()) {
            return $response
                ->withHeader('Location', '/use/' . rawurlencode($id))
                ->withStatus(302);
        }

        $url = $this->registry->resolveUrl($tool);

        if (in_array($runtime, ['php', 'docker'], true)) {
            $deviceId = 'admin-' . session_id();
            $token = $this->tokens->issue($id, 'admin', $deviceId);
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . 'access_token=' . urlencode($token);
        }

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
