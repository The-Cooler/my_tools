<?php

declare(strict_types=1);

namespace Portal;

use Portal\Controller\ApiController;
use Portal\Controller\AuthController;
use Portal\Controller\GoController;
use Portal\Controller\PanelApiController;
use Portal\Controller\PanelController;
use Portal\Middleware\AdminAuthMiddleware;
use Portal\Middleware\ManageAuthMiddleware;
use Portal\Middleware\SessionMiddleware;
use Portal\Service\AccessTokenService;
use Portal\Service\AdminAuthService;
use Portal\Service\AuthConfig;
use Portal\Service\DeviceBindingStore;
use Portal\Service\HealthChecker;
use Portal\Service\KeyRepository;
use Portal\Service\ProcessStateStore;
use Portal\Service\RedisConfig;
use Portal\Service\ToolKeyService;
use Portal\Service\ToolProcessManager;
use Portal\Service\ToolsRegistry;
use Slim\App as SlimApp;
use Slim\Routing\RouteCollectorProxy;

final class App
{
    public static function register(SlimApp $app, string $rootPath): void
    {
        $redisConfig = new RedisConfig($rootPath);
        $app->add(new SessionMiddleware($redisConfig));

        $registry = new ToolsRegistry($rootPath, $redisConfig);
        $authConfig = new AuthConfig($rootPath);
        $healthChecker = new HealthChecker();
        $stateStore = new ProcessStateStore($rootPath);
        $processManager = new ToolProcessManager($rootPath, $registry, $stateStore, $healthChecker);
        $bindings = new DeviceBindingStore($rootPath);
        $keys = new KeyRepository($rootPath, $authConfig, $bindings);
        $tokens = new AccessTokenService($authConfig);
        $toolKeys = new ToolKeyService($keys, $bindings, $tokens);
        $adminAuth = new AdminAuthService($authConfig);

        $panel = new PanelController($registry, $keys);
        $panelApi = new PanelApiController($registry, $keys);
        $api = new ApiController($registry, $healthChecker, $processManager);
        $go = new GoController($registry, $tokens);
        $auth = new AuthController($adminAuth, $toolKeys, $registry);
        $manageAuth = new ManageAuthMiddleware();
        $adminMiddleware = new AdminAuthMiddleware();

        $app->get('/login', [$auth, 'loginForm']);
        $app->post('/login', [$auth, 'login']);
        $app->post('/logout', [$auth, 'logout']);
        $app->get('/use/{id}', [$auth, 'useForm']);
        $app->post('/use/{id}', [$auth, 'useSubmit']);
        $app->get('/go/{id}', [$go, 'redirect']);

        $app->group('', function (RouteCollectorProxy $group) use ($panel, $panelApi, $api): void {
            $group->get('/', [$panel, 'home']);
            $group->get('/panel/tool/{id}', [$panel, 'toolPanel']);
            $group->get('/api/tools', [$api, 'tools']);
            $group->get('/api/health', [$api, 'health']);
            $group->get('/api/tools/{id}/keys', [$panelApi, 'listKeys']);
            $group->post('/api/tools/{id}/keys', [$panelApi, 'createKey']);
            $group->delete('/api/tools/{id}/keys/{keyId}', [$panelApi, 'deleteKey']);
            $group->get('/api/tools/{id}/access-log', [$panelApi, 'accessLog']);
            $group->get('/api/tools/{id}/blocked-ips', [$panelApi, 'listBlockedIps']);
            $group->post('/api/tools/{id}/blocked-ips', [$panelApi, 'blockIp']);
            $group->delete('/api/tools/{id}/blocked-ips/{ip}', [$panelApi, 'unblockIp']);
        })->add($adminMiddleware);

        $app->group('/api/tools', function (RouteCollectorProxy $group) use ($api): void {
            $group->post('/{id}/start', [$api, 'start']);
            $group->post('/{id}/stop', [$api, 'stop']);
        })->add($manageAuth)->add($adminMiddleware);
    }
}
