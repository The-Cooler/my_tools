<?php

declare(strict_types=1);

namespace Portal\Controller;

use Portal\Service\KeyRepository;
use Portal\Service\ToolsRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shared\AccessLogger;
use Shared\IpBlocklist;

final class PanelController
{
    public function __construct(
        private readonly ToolsRegistry $registry,
        private readonly KeyRepository $keys,
    ) {
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $tab = (string) ($params['tab'] ?? 'overview');
        if (!in_array($tab, ['overview', 'panels'], true)) {
            $tab = 'overview';
        }

        $grouped = $this->registry->groupedByCategory(true);
        $toolsWithUrls = [];
        $allTools = [];

        foreach ($grouped as $category => $tools) {
            foreach ($tools as $tool) {
                try {
                    $tool['url_resolved'] = $this->registry->resolveUrl($tool);
                } catch (\Throwable) {
                    $tool['url_resolved'] = '#';
                }
                $toolsWithUrls[$category][] = $tool;
                $allTools[] = $tool;
            }
        }

        ob_start();
        $pageTitle = '我的工具工作站';
        $activeTab = $tab;
        $groupedTools = $toolsWithUrls;
        $panelTools = $allTools;
        require dirname(__DIR__, 2) . '/templates/home.php';
        $html = (string) ob_get_clean();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function toolPanel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $tool = $this->registry->findById($id);

        if ($tool === null || ($tool['enabled'] ?? true) !== true) {
            $response = new Response(404);
            $response->getBody()->write('工具不存在');

            return $response;
        }

        $toolRuntime = (string) ($tool['runtime'] ?? '');
        $showKeys = in_array($toolRuntime, ['php', 'docker'], true);
        $toolKeys = $showKeys ? $this->keys->listForTool($id) : [];
        $accessLog = AccessLogger::listForTool($id);
        $blockedIps = IpBlocklist::forTool($id);

        ob_start();
        $pageTitle = '面板 · ' . ($tool['name'] ?? $id);
        $toolId = $id;
        $toolName = (string) ($tool['name'] ?? $id);
        $showKeys = $showKeys;
        require dirname(__DIR__, 2) . '/templates/panel/tool.php';
        $html = (string) ob_get_clean();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
