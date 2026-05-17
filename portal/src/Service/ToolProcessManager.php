<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;
use Symfony\Component\Process\Process;

final class ToolProcessManager
{
    public function __construct(
        private readonly string $rootPath,
        private readonly ToolsRegistry $registry,
        private readonly ProcessStateStore $stateStore,
        private readonly HealthChecker $healthChecker,
    ) {
    }

    public function start(array $tool): array
    {
        $runtime = $tool['runtime'] ?? '';

        return match ($runtime) {
            'php' => $this->startPhp($tool),
            'docker' => $this->startDocker($tool),
            default => throw new RuntimeException('外部链接工具无法通过门户启动。'),
        };
    }

    public function stop(array $tool): array
    {
        $runtime = $tool['runtime'] ?? '';

        return match ($runtime) {
            'php' => $this->stopPhp($tool),
            'docker' => $this->stopDocker($tool),
            default => throw new RuntimeException('外部链接工具无法通过门户停止。'),
        };
    }

    private function startPhp(array $tool): array
    {
        $id = (string) ($tool['id'] ?? '');
        $status = $this->healthChecker->checkUrl($this->registry->healthUrl($tool));
        if ($status === 'online') {
            return ['message' => '工具已在运行', 'health' => 'online'];
        }

        $publicDir = $this->rootPath . '/tools/' . $id . '/public';
        if (!is_file($publicDir . '/index.php')) {
            throw new RuntimeException("工具目录不存在或缺少 public/index.php: tools/{$id}");
        }

        $defaults = $this->registry->defaults();
        $host = $defaults['host'] ?? '127.0.0.1';
        $port = (int) ($tool['port'] ?? 0);
        if ($port <= 0) {
            throw new RuntimeException("工具 [{$id}] 未配置 port。");
        }

        $logDir = $this->stateStore->runtimeDir() . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/' . $id . '.log';

        $process = new Process(
            ['php', '-S', sprintf('%s:%d', $host, $port), 'index.php'],
            $publicDir,
            null,
            null,
            null
        );
        $process->start(function ($type, $buffer) use ($logFile): void {
            file_put_contents($logFile, $buffer, FILE_APPEND);
        });

        $pid = $process->getPid();
        if ($pid === null) {
            throw new RuntimeException('无法获取 PHP 工具进程 PID。');
        }

        $this->stateStore->savePhpProcess($id, $pid);

        usleep(300000);
        $health = $this->healthChecker->checkUrl($this->registry->healthUrl($tool));

        return [
            'message' => $health === 'online' ? '已启动' : '进程已创建，健康检查尚未通过',
            'health' => $health,
            'pid' => $pid,
        ];
    }

    private function stopPhp(array $tool): array
    {
        $id = (string) ($tool['id'] ?? '');
        $state = $this->stateStore->load($id);

        if ($state !== null && isset($state['pid'])) {
            $this->killPid((int) $state['pid']);
        }

        $this->stateStore->clear($id);
        usleep(200000);
        $health = $this->healthChecker->checkUrl($this->registry->healthUrl($tool));

        return [
            'message' => $health === 'offline' ? '已停止' : '已发送停止信号',
            'health' => $health,
        ];
    }

    private function startDocker(array $tool): array
    {
        $service = (string) ($tool['compose_service'] ?? $tool['id'] ?? '');
        $composeFile = $this->rootPath . '/docker-compose.yml';

        if (!is_file($composeFile)) {
            throw new RuntimeException('未找到 docker-compose.yml');
        }

        $process = new Process(
            ['docker', 'compose', '-f', $composeFile, 'up', '-d', $service],
            $this->rootPath,
            null,
            null,
            120
        );
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        usleep(500000);
        $health = $this->healthChecker->checkUrl($this->registry->healthUrl($tool));

        return [
            'message' => 'Docker 服务已启动',
            'health' => $health,
            'service' => $service,
        ];
    }

    private function stopDocker(array $tool): array
    {
        $service = (string) ($tool['compose_service'] ?? $tool['id'] ?? '');
        $composeFile = $this->rootPath . '/docker-compose.yml';

        $process = new Process(
            ['docker', 'compose', '-f', $composeFile, 'stop', $service],
            $this->rootPath,
            null,
            null,
            60
        );
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        usleep(300000);
        $health = $this->healthChecker->checkUrl($this->registry->healthUrl($tool));

        return [
            'message' => 'Docker 服务已停止',
            'health' => $health,
            'service' => $service,
        ];
    }

    private function killPid(int $pid): void
    {
        if ($pid <= 0) {
            return;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $kill = new Process(['taskkill', '/PID', (string) $pid, '/F']);
            $kill->run();

            return;
        }

        if (function_exists('posix_kill')) {
            posix_kill($pid, SIGTERM);

            return;
        }

        $kill = new Process(['kill', (string) $pid]);
        $kill->run();
    }
}
