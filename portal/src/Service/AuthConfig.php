<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class AuthConfig
{
    private ?array $data = null;

    public function __construct(
        private readonly string $rootPath,
    ) {
    }

    public function get(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $path = $this->rootPath . '/config/auth.yaml';
        if (!is_file($path)) {
            throw new RuntimeException(
                '未找到 config/auth.yaml，请复制 config/auth.yaml.template 为 auth.yaml 并配置。'
            );
        }

        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed)) {
            throw new RuntimeException('config/auth.yaml 格式无效。');
        }

        $this->data = $parsed;

        return $this->data;
    }

    public function appSecret(): string
    {
        return (string) ($this->get()['app_secret'] ?? '');
    }

    public function admin(): array
    {
        return $this->get()['admin'] ?? [];
    }
}
