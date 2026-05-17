<?php

declare(strict_types=1);

namespace Portal\Service;

final class HealthChecker
{
    private const TIMEOUT_SECONDS = 2;

    public function checkUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return 'unknown';
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return 'offline';
        }

        if (is_string($response) && str_contains($response, '"status"')) {
            $decoded = json_decode($response, true);
            if (is_array($decoded) && ($decoded['status'] ?? '') === 'ok') {
                return 'online';
            }
        }

        $headers = $http_response_header ?? [];
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+2\d{2}#', $header)) {
                return 'online';
            }
        }

        return 'offline';
    }

    /**
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, string>
     */
    public function checkMany(array $tools, ToolsRegistry $registry): array
    {
        $results = [];

        foreach ($tools as $tool) {
            $id = $tool['id'] ?? '';
            if ($id === '') {
                continue;
            }

            $results[$id] = $this->checkUrl($registry->healthUrl($tool));
        }

        return $results;
    }
}
