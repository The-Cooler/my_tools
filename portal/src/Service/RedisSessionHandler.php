<?php

declare(strict_types=1);

namespace Portal\Service;

use Predis\Client;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

final class RedisSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly string $keyPrefix,
        private readonly int $ttl,
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $value = $this->client->get($this->keyPrefix . $id);

        return is_string($value) ? $value : '';
    }

    public function write(string $id, string $data): bool
    {
        $this->client->setex($this->keyPrefix . $id, $this->ttl, $data);

        return true;
    }

    public function destroy(string $id): bool
    {
        $this->client->del([$this->keyPrefix . $id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    public function validateId(string $id): bool
    {
        return $id !== '';
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }
}
