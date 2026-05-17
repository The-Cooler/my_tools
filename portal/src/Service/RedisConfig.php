<?php

declare(strict_types=1);

namespace Portal\Service;

use Predis\Client;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class RedisConfig
{
    private ?array $data = null;

    public function __construct(
        private readonly string $rootPath,
    ) {
    }

    public function isEnabled(): bool
    {
        return ($this->load()['enabled'] ?? false) === true;
    }

    public function prefix(): string
    {
        return (string) ($this->load()['prefix'] ?? 'my_tools:');
    }

    public function sessionTtl(): int
    {
        return (int) ($this->load()['session_ttl'] ?? 86400);
    }

    public function createClient(): Client
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Redis 未启用');
        }

        $c = $this->load();
        $params = [
            'scheme' => 'tcp',
            'host' => $c['host'] ?? '127.0.0.1',
            'port' => (int) ($c['port'] ?? 6379),
            'database' => (int) ($c['database'] ?? 0),
        ];
        $password = (string) ($c['password'] ?? '');
        if ($password !== '') {
            $params['password'] = $password;
        }

        return new Client($params);
    }

    public function key(string $suffix): string
    {
        return $this->prefix() . $suffix;
    }

    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $path = $this->rootPath . '/config/redis.yaml';
        if (!is_file($path)) {
            $this->data = ['enabled' => false];

            return $this->data;
        }

        $parsed = Yaml::parseFile($path);

        $this->data = is_array($parsed) ? $parsed : ['enabled' => false];

        return $this->data;
    }
}
