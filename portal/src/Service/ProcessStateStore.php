<?php

declare(strict_types=1);

namespace Portal\Service;

final class ProcessStateStore
{
    public function __construct(
        private readonly string $rootPath,
    ) {
    }

    public function savePhpProcess(string $toolId, int $pid): void
    {
        $this->write($toolId, [
            'runtime' => 'php',
            'pid' => $pid,
            'started_at' => time(),
        ]);
    }

    public function load(string $toolId): ?array
    {
        $path = $this->path($toolId);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    public function clear(string $toolId): void
    {
        $path = $this->path($toolId);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function runtimeDir(): string
    {
        $dir = $this->rootPath . '/storage/runtime';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function path(string $toolId): string
    {
        return $this->runtimeDir() . '/' . preg_replace('/[^a-z0-9_-]/i', '', $toolId) . '.json';
    }

    private function write(string $toolId, array $data): void
    {
        file_put_contents(
            $this->path($toolId),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
