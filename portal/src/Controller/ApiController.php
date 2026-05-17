<?php

declare(strict_types=1);

namespace Portal\Controller;

use Portal\Service\HealthChecker;
use Portal\Service\ToolProcessManager;
use Portal\Service\ToolsRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiController
{
    public function __construct(
        private readonly ToolsRegistry $registry,
        private readonly HealthChecker $healthChecker,
        private readonly ToolProcessManager $processManager,
    ) {
    }

    public function tools(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $tools = $this->registry->all(true);
        $payload = [];

        foreach ($tools as $tool) {
            try {
                $tool['url_resolved'] = $this->registry->resolveUrl($tool);
            } catch (\Throwable $e) {
                $tool['url_resolved'] = null;
                $tool['url_error'] = $e->getMessage();
            }
            $payload[] = $tool;
        }

        return $this->json($response, ['tools' => $payload]);
    }

    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $tools = $this->registry->all(true);
        $statuses = $this->healthChecker->checkMany($tools, $this->registry);

        return $this->json($response, ['health' => $statuses]);
    }

    public function start(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->control($response, $args, 'start');
    }

    public function stop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->control($response, $args, 'stop');
    }

    private function control(ResponseInterface $response, array $args, string $action): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $tool = $this->registry->findById($id);

        if ($tool === null || ($tool['enabled'] ?? true) !== true) {
            return $this->json($response->withStatus(404), ['ok' => false, 'error' => '工具不存在或未启用']);
        }

        $runtime = $tool['runtime'] ?? '';
        if (!in_array($runtime, ['php', 'docker'], true)) {
            return $this->json($response->withStatus(400), [
                'ok' => false,
                'error' => '该工具为外部链接，无法在门户启停',
            ]);
        }

        try {
            $result = $action === 'start'
                ? $this->processManager->start($tool)
                : $this->processManager->stop($tool);

            return $this->json($response, array_merge(['ok' => true, 'id' => $id], $result));
        } catch (\Throwable $e) {
            return $this->json($response->withStatus(500), [
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function json(ResponseInterface $response, array $data): ResponseInterface
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $response->getBody()->write($body === false ? '{}' : $body);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
