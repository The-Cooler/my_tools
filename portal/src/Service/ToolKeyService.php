<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;
use Shared\AccessLogger;
use Shared\ClientIp;
use Shared\IpBlocklist;

final class ToolKeyService
{
    public function __construct(
        private readonly KeyRepository $keys,
        private readonly DeviceBindingStore $bindings,
        private readonly AccessTokenService $tokens,
    ) {
    }

    /**
     * @return array{token: string, key_id: string, label: string}
     */
    public function authenticate(string $toolId, string $plainSecret, string $deviceId, ?string $ip = null): array
    {
        $ip ??= ClientIp::resolve();

        if (IpBlocklist::isBlocked($toolId, $ip)) {
            AccessLogger::log($toolId, $ip, 'auth_denied', null, $deviceId, false);
            throw new RuntimeException('该 IP 已被禁止访问此工具。');
        }

        if ($deviceId === '' || strlen($deviceId) < 8) {
            throw new RuntimeException('缺少有效设备标识，请刷新页面重试。');
        }

        $matched = $this->keys->findBySecret($toolId, $plainSecret);
        if ($matched === null) {
            AccessLogger::log($toolId, $ip, 'auth_failed', null, $deviceId, false);
            throw new RuntimeException('密钥无效。');
        }

        if ($this->keys->isExpired($matched)) {
            AccessLogger::log($toolId, $ip, 'auth_expired', (string) $matched['id'], $deviceId, false);
            throw new RuntimeException('密钥已过期。');
        }

        $keyId = (string) $matched['id'];
        $this->bindings->assertDevice($toolId, $keyId, $deviceId);

        $token = $this->tokens->issue($toolId, $keyId, $deviceId);
        AccessLogger::log($toolId, $ip, 'auth_ok', $keyId, $deviceId, true);

        return [
            'token' => $token,
            'key_id' => $keyId,
            'label' => (string) ($matched['label'] ?? $keyId),
        ];
    }
}
