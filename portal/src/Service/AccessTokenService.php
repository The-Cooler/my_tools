<?php

declare(strict_types=1);

namespace Portal\Service;

final class AccessTokenService
{
    private const TTL_SECONDS = 86400 * 7;

    public function __construct(
        private readonly AuthConfig $authConfig,
    ) {
    }

    public function issue(string $toolId, string $keyId, string $deviceId): string
    {
        $secret = $this->authConfig->appSecret();
        if ($secret === '') {
            throw new \RuntimeException('app_secret 未配置');
        }

        $payload = json_encode([
            'tool_id' => $toolId,
            'key_id' => $keyId,
            'device_id' => $deviceId,
            'exp' => time() + self::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR);

        $payloadPart = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $payloadPart, $secret, true);
        $sigPart = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return $payloadPart . '.' . $sigPart;
    }
}
