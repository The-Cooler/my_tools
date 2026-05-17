<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class ToolsRegistry
{
    private ?array $data = null;

    private readonly RedisConfig $redisConfig;

    public function __construct(
        private readonly string $rootPath,
    ) {
        $this->redisConfig = new RedisConfig($rootPath);
    }

    public function all(bool $enabledOnly = false): array
    {
        $tools = $this->load()['tools'] ?? [];

        if (!$enabledOnly) {
            return $tools;
        }

        return array_values(array_filter(
            $tools,
            static fn (array $tool): bool => ($tool['enabled'] ?? true) === true
        ));
    }

    public function findById(string $id): ?array
    {
        foreach ($this->all() as $tool) {
            if (($tool['id'] ?? '') === $id) {
                return $tool;
            }
        }

        return null;
    }

    public function resolveUrl(array $tool): string
    {
        $runtime = $tool['runtime'] ?? 'php';

        if ($runtime === 'external') {
            if (empty($tool['url'])) {
                throw new RuntimeException("External tool [{$tool['id']}] missing url.");
            }

            return (string) $tool['url'];
        }

        if (!empty($tool['url'])) {
            return (string) $tool['url'];
        }

        $defaults = $this->load()['defaults'] ?? [];
        $host = $defaults['host'] ?? '127.0.0.1';
        $port = $tool['port'] ?? null;
        $path = $tool['path'] ?? '/';

        if ($port === null) {
            throw new RuntimeException("Tool [{$tool['id']}] missing port.");
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        return sprintf('http://%s:%d%s', $host, (int) $port, $path);
    }

    public function healthUrl(array $tool): ?string
    {
        $runtime = $tool['runtime'] ?? 'php';

        if (!in_array($runtime, ['php', 'docker'], true)) {
            return null;
        }

        $defaults = $this->load()['defaults'] ?? [];
        $host = $defaults['host'] ?? '127.0.0.1';
        $healthPath = $tool['health_path'] ?? $defaults['health_path'] ?? '/health';
        $port = $tool['port'] ?? null;

        if ($port === null) {
            return null;
        }

        if ($healthPath === '' || $healthPath[0] !== '/') {
            $healthPath = '/' . ltrim($healthPath, '/');
        }

        return sprintf('http://%s:%d%s', $host, (int) $port, $healthPath);
    }

    public function defaults(): array
    {
        return $this->load()['defaults'] ?? [];
    }

    public function groupedByCategory(bool $enabledOnly = true): array
    {
        $grouped = [];

        foreach ($this->all($enabledOnly) as $tool) {
            $category = $tool['category'] ?? 'other';
            $grouped[$category][] = $tool;
        }

        ksort($grouped);

        return $grouped;
    }

    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $yamlPath = $this->rootPath . '/config/tools.yaml';

        if (!is_file($yamlPath)) {
            throw new RuntimeException("Registry not found: {$yamlPath}");
        }

        $yamlMtime = filemtime($yamlPath) ?: 0;
        $cached = $this->loadFromRedis($yamlMtime) ?? $this->loadFromFile($yamlMtime);

        if ($cached !== null) {
            $this->data = $cached;

            return $this->data;
        }

        $parsed = Yaml::parseFile($yamlPath);
        if (!is_array($parsed)) {
            throw new RuntimeException('Invalid tools.yaml structure.');
        }

        $this->saveCache($parsed, $yamlMtime);
        $this->data = $parsed;

        return $this->data;
    }

    private function loadFromRedis(int $yamlMtime): ?array
    {
        if (!$this->redisConfig->isEnabled()) {
            return null;
        }

        try {
            $raw = $this->redisConfig->createClient()->get($this->redisConfig->key('tools_registry'));
            if (!is_string($raw) || $raw === '') {
                return null;
            }

            $cached = json_decode($raw, true);
            if (!is_array($cached) || ($cached['__mtime'] ?? 0) < $yamlMtime) {
                return null;
            }

            return is_array($cached['data'] ?? null) ? $cached['data'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function loadFromFile(int $yamlMtime): ?array
    {
        $cacheFile = $this->rootPath . '/storage/cache/tools.php';
        if (!is_file($cacheFile)) {
            return null;
        }

        $cached = require $cacheFile;
        if (!is_array($cached) || ($cached['__mtime'] ?? 0) < $yamlMtime) {
            return null;
        }

        return is_array($cached['data'] ?? null) ? $cached['data'] : null;
    }

    private function saveCache(array $parsed, int $yamlMtime): void
    {
        $payload = ['__mtime' => $yamlMtime, 'data' => $parsed];

        if ($this->redisConfig->isEnabled()) {
            try {
                $this->redisConfig->createClient()->set(
                    $this->redisConfig->key('tools_registry'),
                    json_encode($payload, JSON_UNESCAPED_UNICODE)
                );

                return;
            } catch (\Throwable) {
                // 回退文件缓存
            }
        }

        $cacheDir = $this->rootPath . '/storage/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $export = var_export($payload, true);
        file_put_contents($cacheDir . '/tools.php', "<?php\n\nreturn {$export};\n");
    }
}
