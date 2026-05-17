<?php

declare(strict_types=1);

namespace Portal\Controller;

use Portal\Service\KeyRepository;
use Portal\Service\ToolsRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shared\AccessLogger;
use Shared\IpBlocklist;

final class PanelApiController
{
    public function __construct(
        private readonly ToolsRegistry $registry,
        private readonly KeyRepository $keys,
    ) {
    }

    public function listKeys(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['keys' => $this->keys->listForTool($tool['id'])]);
    }

    public function createKey(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        $body = $request->getParsedBody() ?? [];
        $label = trim((string) ($body['label'] ?? ''));
        $expiresAt = null;
        $days = (int) ($body['expires_days'] ?? 0);
        if ($days > 0) {
            $expiresAt = time() + $days * 86400;
        } elseif (!empty($body['expires_at'])) {
            $expiresAt = strtotime((string) $body['expires_at']) ?: null;
        }

        $created = $this->keys->create($tool['id'], $label, $expiresAt);

        return $this->json($response, [
            'ok' => true,
            'key' => [
                'id' => $created['key']['id'],
                'label' => $created['key']['label'],
                'expires_at' => $created['key']['expires_at'],
            ],
            'plain_secret' => $created['plain_secret'],
            'message' => '密钥仅显示一次，请妥善保存。',
        ]);
    }

    public function deleteKey(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        $keyId = $args['keyId'] ?? '';
        if (!$this->keys->delete($tool['id'], $keyId)) {
            return $this->json($response->withStatus(404), ['ok' => false, 'error' => '密钥不存在']);
        }

        return $this->json($response, ['ok' => true]);
    }

    public function accessLog(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['log' => AccessLogger::listForTool($tool['id'])]);
    }

    public function listBlockedIps(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        return $this->json($response, ['ips' => IpBlocklist::forTool($tool['id'])]);
    }

    public function blockIp(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        $body = $request->getParsedBody() ?? [];
        $ip = trim((string) ($body['ip'] ?? ''));

        try {
            IpBlocklist::block($tool['id'], $ip);
        } catch (\Throwable $e) {
            return $this->json($response->withStatus(400), ['ok' => false, 'error' => $e->getMessage()]);
        }

        return $this->json($response, ['ok' => true]);
    }

    public function unblockIp(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $tool = $this->findTool($args['id'] ?? '');
        if ($tool === null) {
            return $this->notFound($response);
        }

        IpBlocklist::unblock($tool['id'], urldecode($args['ip'] ?? ''));

        return $this->json($response, ['ok' => true]);
    }

    private function findTool(string $id): ?array
    {
        $tool = $this->registry->findById($id);

        return ($tool !== null && ($tool['enabled'] ?? true)) ? $tool : null;
    }

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response->withStatus(404), ['ok' => false, 'error' => '工具不存在']);
    }

    private function json(ResponseInterface $response, array $data): ResponseInterface
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $response->getBody()->write($body === false ? '{}' : $body);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
