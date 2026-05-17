<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;

final class DeviceBindingStore
{
    public function __construct(
        private readonly string $rootPath,
    ) {
    }

    /**
     * @return array{device_id: string, bound_at: int, last_seen: int}|null
     */
    public function get(string $toolId, string $keyId): ?array
    {
        $all = $this->read();

        return $all[$this->bindingKey($toolId, $keyId)] ?? null;
    }

    public function bind(string $toolId, string $keyId, string $deviceId): void
    {
        $all = $this->read();
        $bk = $this->bindingKey($toolId, $keyId);
        $now = time();

        $all[$bk] = [
            'device_id' => $deviceId,
            'bound_at' => $all[$bk]['bound_at'] ?? $now,
            'last_seen' => $now,
        ];

        $this->write($all);
    }

    public function assertDevice(string $toolId, string $keyId, string $deviceId): void
    {
        $existing = $this->get($toolId, $keyId);

        if ($existing === null) {
            $this->bind($toolId, $keyId, $deviceId);

            return;
        }

        if ($existing['device_id'] !== $deviceId) {
            throw new RuntimeException('该密钥已绑定其他设备，无法在本设备使用。');
        }

        $this->bind($toolId, $keyId, $deviceId);
    }

    public function release(string $toolId, string $keyId): void
    {
        $all = $this->read();
        unset($all[$this->bindingKey($toolId, $keyId)]);
        $this->write($all);
    }

    private function bindingKey(string $toolId, string $keyId): string
    {
        return $toolId . ':' . $keyId;
    }

    private function path(): string
    {
        $dir = $this->rootPath . '/storage/auth';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/bindings.json';
    }

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
        file_put_contents(
            $this->path(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
