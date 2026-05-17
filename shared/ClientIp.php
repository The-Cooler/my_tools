<?php

declare(strict_types=1);

namespace Shared;

final class ClientIp
{
    public static function resolve(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $key) {
            $value = $_SERVER[$key] ?? '';
            if ($value === '') {
                continue;
            }
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $value = trim(explode(',', $value)[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '0.0.0.0';
    }
}
