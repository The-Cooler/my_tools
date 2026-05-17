<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class KeyRepository
{
    public function __construct(
        private readonly string $rootPath,
        private readonly AuthConfig $authConfig,
        private readonly DeviceBindingStore $bindings,
    ) {
        $this->migrateFromYamlIfNeeded();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForTool(string $toolId, bool $withHash = false): array
    {
        $all = $this->read();
        $keys = $all[$toolId] ?? [];

        return array_map(function (array $k) use ($withHash): array {
            $row = [
                'id' => $k['id'],
                'label' => $k['label'] ?? $k['id'],
                'expires_at' => $k['expires_at'] ?? null,
                'created_at' => $k['created_at'] ?? null,
                'expired' => $this->isExpired($k),
            ];
            if ($withHash) {
                $row['secret_hash'] = $k['secret_hash'] ?? '';
            }

            return $row;
        }, $keys);
    }

    public function find(string $toolId, string $keyId): ?array
    {
        foreach ($this->read()[$toolId] ?? [] as $key) {
            if (($key['id'] ?? '') === $keyId) {
                return $key;
            }
        }

        return null;
    }

    public function findBySecret(string $toolId, string $plainSecret): ?array
    {
        foreach ($this->read()[$toolId] ?? [] as $key) {
            $hash = (string) ($key['secret_hash'] ?? '');
            if ($hash !== '' && password_verify($plainSecret, $hash)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array{key: array<string, mixed>, plain_secret: string}
     */
    public function create(string $toolId, string $label, ?int $expiresAt): array
    {
        $plain = bin2hex(random_bytes(16));
        $id = 'key-' . substr(bin2hex(random_bytes(4)), 0, 8);

        $entry = [
            'id' => $id,
            'label' => $label !== '' ? $label : $id,
            'secret_hash' => password_hash($plain, PASSWORD_DEFAULT),
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];

        $all = $this->read();
        $all[$toolId] ??= [];
        $all[$toolId][] = $entry;
        $this->write($all);

        return ['key' => $entry, 'plain_secret' => $plain];
    }

    public function delete(string $toolId, string $keyId): bool
    {
        $all = $this->read();
        if (!isset($all[$toolId])) {
            return false;
        }

        $before = count($all[$toolId]);
        $all[$toolId] = array_values(array_filter(
            $all[$toolId],
            static fn (array $k): bool => ($k['id'] ?? '') !== $keyId
        ));

        if (count($all[$toolId]) === $before) {
            return false;
        }

        $this->write($all);
        $this->bindings->release($toolId, $keyId);

        return true;
    }

    public function isExpired(array $key): bool
    {
        $exp = $key['expires_at'] ?? null;
        if ($exp === null || $exp === '') {
            return false;
        }

        return (int) $exp < time();
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function read(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    private function write(array $data): void
    {
        $dir = dirname($this->path());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->path(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    private function path(): string
    {
        return $this->rootPath . '/storage/auth/keys.json';
    }

    private function migrateFromYamlIfNeeded(): void
    {
        if (is_file($this->path())) {
            return;
        }

        try {
            $yamlKeys = $this->authConfig->get()['tool_keys'] ?? [];
        } catch (RuntimeException) {
            return;
        }

        if (!is_array($yamlKeys) || $yamlKeys === []) {
            return;
        }

        $this->write($yamlKeys);
    }
}
