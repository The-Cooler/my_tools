<?php

declare(strict_types=1);

namespace Shared;

final class AccessLogger
{
    private const MAX_LINES = 2000;

    public static function log(
        string $toolId,
        string $ip,
        string $event,
        ?string $keyId = null,
        ?string $deviceId = null,
        bool $hasToken = false,
    ): void {
        $dir = dirname(__DIR__) . '/storage/auth';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = json_encode([
            'at' => time(),
            'tool_id' => $toolId,
            'ip' => $ip,
            'event' => $event,
            'key_id' => $keyId,
            'device_id' => $deviceId,
            'has_token' => $hasToken,
        ], JSON_UNESCAPED_UNICODE);

        if ($line === false) {
            return;
        }

        $path = $dir . '/access.log';
        file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        self::trim($path);
    }

    /** @return array<int, array<string, mixed>> */
    public static function listForTool(string $toolId, int $limit = 200): array
    {
        $path = dirname(__DIR__) . '/storage/auth/access.log';
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $rows = [];
        foreach (array_reverse($lines) as $line) {
            $row = json_decode($line, true);
            if (!is_array($row) || ($row['tool_id'] ?? '') !== $toolId) {
                continue;
            }
            $rows[] = $row;
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    private static function trim(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || count($lines) <= self::MAX_LINES) {
            return;
        }

        $keep = array_slice($lines, -self::MAX_LINES);
        file_put_contents($path, implode(PHP_EOL, $keep) . PHP_EOL, LOCK_EX);
    }
}
