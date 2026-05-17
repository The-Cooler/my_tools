<?php

declare(strict_types=1);

namespace Portal\Service;

final class PanelChartStats
{
    private const EVENT_LABELS = [
        'auth_ok' => '验证成功',
        'auth_denied' => '验证失败',
        'auth_failed' => '密钥错误',
        'auth_expired' => '密钥过期',
        'access_ok' => '访问成功',
        'access_denied' => '访问拒绝',
    ];

    /**
     * @param array<int, array<string, mixed>> $accessLog
     * @param array<int, array<string, mixed>> $toolKeys
     * @param array<int, string> $blockedIps
     *
     * @return array{
     *   summary: array{access:int,unique_ip:int,keys:int,blocked:int,success_rate:int},
     *   days: array<int, array{label:string,count:int,pct:int}>,
     *   events: array<int, array{label:string,count:int,pct:int}>,
     *   top_ips: array<int, array{ip:string,count:int,pct:int}>
     * }
     */
    public static function build(array $accessLog, array $toolKeys, array $blockedIps, int $dayRange = 7): array
    {
        $byDay = [];
        $byEvent = [];
        $byIp = [];
        $successEvents = ['auth_ok', 'access_ok'];

        foreach ($accessLog as $row) {
            $at = (int) ($row['at'] ?? 0);
            if ($at <= 0) {
                continue;
            }

            $dayKey = date('Y-m-d', $at);
            $byDay[$dayKey] = ($byDay[$dayKey] ?? 0) + 1;

            $event = (string) ($row['event'] ?? 'unknown');
            $byEvent[$event] = ($byEvent[$event] ?? 0) + 1;

            $ip = (string) ($row['ip'] ?? '');
            if ($ip !== '') {
                $byIp[$ip] = ($byIp[$ip] ?? 0) + 1;
            }
        }

        $days = [];
        $maxDay = 0;
        for ($i = $dayRange - 1; $i >= 0; $i--) {
            $key = date('Y-m-d', strtotime("-{$i} days"));
            $count = $byDay[$key] ?? 0;
            $maxDay = max($maxDay, $count);
            $days[] = [
                'label' => date('m-d', strtotime($key)),
                'count' => $count,
                'pct' => 0,
            ];
        }
        $days = self::applyPct($days, $maxDay);

        arsort($byEvent);
        $events = [];
        $maxEvent = $byEvent !== [] ? max($byEvent) : 0;
        foreach ($byEvent as $event => $count) {
            $events[] = [
                'label' => self::EVENT_LABELS[$event] ?? $event,
                'count' => $count,
                'pct' => 0,
            ];
        }
        $events = self::applyPct($events, $maxEvent);

        arsort($byIp);
        $topIps = [];
        $maxIp = 0;
        $i = 0;
        foreach ($byIp as $ip => $count) {
            if ($i >= 5) {
                break;
            }
            $maxIp = max($maxIp, $count);
            $topIps[] = ['ip' => $ip, 'count' => $count, 'pct' => 0];
            $i++;
        }
        $topIps = self::applyPct($topIps, $maxIp);

        $accessTotal = count($accessLog);
        $success = 0;
        foreach ($accessLog as $row) {
            if (in_array((string) ($row['event'] ?? ''), $successEvents, true)) {
                $success++;
            }
        }

        return [
            'summary' => [
                'access' => $accessTotal,
                'unique_ip' => count($byIp),
                'keys' => count($toolKeys),
                'blocked' => count($blockedIps),
                'success_rate' => $accessTotal > 0 ? (int) round($success / $accessTotal * 100) : 0,
            ],
            'days' => $days,
            'events' => $events,
            'top_ips' => $topIps,
        ];
    }

    /**
     * @param array<int, array{label:string,count:int,pct:int}> $rows
     *
     * @return array<int, array{label:string,count:int,pct:int}>
     */
    private static function applyPct(array $rows, int $max): array
    {
        $max = max($max, 1);
        foreach ($rows as &$row) {
            $row['pct'] = (int) round($row['count'] / $max * 100);
        }
        unset($row);

        return $rows;
    }
}
