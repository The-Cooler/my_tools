<?php

declare(strict_types=1);

namespace Portal\Service;

final class AccessLogStats
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *   totals: array{all: int, ok: int, denied: int, unique_ips: int},
     *   by_day: array<int, array{label: string, count: int}>,
     *   by_event: array<int, array{label: string, count: int}>
     * }
     */
    public static function summarize(array $rows, int $dayLimit = 7): array
    {
        $okEvents = ['access_ok', 'auth_ok'];
        $deniedEvents = ['access_denied', 'auth_denied', 'auth_failed', 'auth_expired'];

        $totals = ['all' => 0, 'ok' => 0, 'denied' => 0, 'unique_ips' => 0];
        $ips = [];
        $byDay = [];
        $byEvent = [];

        foreach ($rows as $row) {
            $totals['all']++;
            $event = (string) ($row['event'] ?? 'unknown');
            $ip = (string) ($row['ip'] ?? '');
            if ($ip !== '') {
                $ips[$ip] = true;
            }

            if (in_array($event, $okEvents, true)) {
                $totals['ok']++;
            }
            if (in_array($event, $deniedEvents, true)) {
                $totals['denied']++;
            }

            $at = (int) ($row['at'] ?? 0);
            if ($at > 0) {
                $dayKey = date('Y-m-d', $at);
                $byDay[$dayKey] = ($byDay[$dayKey] ?? 0) + 1;
            }

            $byEvent[$event] = ($byEvent[$event] ?? 0) + 1;
        }

        $totals['unique_ips'] = count($ips);

        ksort($byDay);
        if (count($byDay) > $dayLimit) {
            $byDay = array_slice($byDay, -$dayLimit, null, true);
        }

        $daySeries = [];
        foreach ($byDay as $day => $count) {
            $daySeries[] = ['label' => date('m-d', strtotime($day)), 'count' => $count];
        }

        arsort($byEvent);
        $eventSeries = [];
        foreach ($byEvent as $event => $count) {
            $eventSeries[] = ['label' => $event, 'count' => $count];
        }

        return [
            'totals' => $totals,
            'by_day' => $daySeries,
            'by_event' => array_slice($eventSeries, 0, 8),
        ];
    }
}
