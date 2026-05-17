<?php

declare(strict_types=1);

namespace Shared;

final class IpBlocklist
{
    private static function path(): string
    {
        $dir = dirname(__DIR__) . '/storage/auth';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/ip_blocks.json';
    }

    public static function isBlocked(string $toolId, string $ip): bool
    {
        $all = self::read();

        foreach ($all['global'] ?? [] as $blocked) {
            if ($blocked === $ip) {
                return true;
            }
        }

        foreach ($all['tools'][$toolId] ?? [] as $blocked) {
            if ($blocked === $ip) {
                return true;
            }
        }

        return false;
    }

    /** @return array{global: array<int, string>, tools: array<string, array<int, string>>} */
    public static function read(): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return ['global' => [], 'tools' => []];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? [
            'global' => array_values($data['global'] ?? []),
            'tools' => is_array($data['tools'] ?? null) ? $data['tools'] : [],
        ] : ['global' => [], 'tools' => []];
    }

    public static function block(string $toolId, string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('无效 IP');
        }

        $all = self::read();
        $list = $all['tools'][$toolId] ?? [];
        if (!in_array($ip, $list, true)) {
            $list[] = $ip;
        }
        $all['tools'][$toolId] = array_values($list);
        self::write($all);
    }

    public static function unblock(string $toolId, string $ip): void
    {
        $all = self::read();
        $all['tools'][$toolId] = array_values(array_filter(
            $all['tools'][$toolId] ?? [],
            static fn (string $v): bool => $v !== $ip
        ));
        self::write($all);
    }

    /** @return array<int, string> */
    public static function forTool(string $toolId): array
    {
        $all = self::read();

        return array_values($all['tools'][$toolId] ?? []);
    }

    private static function write(array $data): void
    {
        file_put_contents(
            self::path(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}
