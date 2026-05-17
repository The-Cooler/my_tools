<?php

declare(strict_types=1);

namespace Shared;

use RuntimeException;

/** 与 portal AccessTokenService 相同的签发/校验实现，避免两处逻辑不一致 */
final class AccessToken
{
    private const TTL_SECONDS = 86400 * 7;

    public static function appSecret(string $rootPath): string
    {
        $authPath = $rootPath . '/config/auth.yaml';
        if (!is_file($authPath)) {
            throw new RuntimeException('未找到 config/auth.yaml');
        }

        self::ensureYaml();

        $auth = \Symfony\Component\Yaml\Yaml::parseFile($authPath);
        if (!is_array($auth)) {
            throw new RuntimeException('config/auth.yaml 格式无效');
        }

        $secret = trim((string) ($auth['app_secret'] ?? ''));
        if ($secret === '' || str_contains($secret, '请替换')) {
            throw new RuntimeException('app_secret 未配置');
        }

        return $secret;
    }

    public static function issue(string $rootPath, string $toolId, string $keyId, string $deviceId): string
    {
        $secret = self::appSecret($rootPath);

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

    /**
     * @param array<string, mixed>|null $payloadOut
     */
    public static function verify(string $token, string $toolId, string $rootPath, ?array &$payloadOut = null): bool
    {
        try {
            $secret = self::appSecret($rootPath);
        } catch (RuntimeException) {
            return false;
        }

        return self::verifyWithSecret($token, $toolId, $secret, $payloadOut);
    }

    /**
     * @param array<string, mixed>|null $payloadOut
     */
    public static function verifyWithSecret(string $token, string $expectedToolId, string $secret, ?array &$payloadOut = null): bool
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $payloadJson = base64_decode(strtr($parts[0], '-_', '+/'), true);
        $sig = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payloadJson === false || $sig === false) {
            return false;
        }

        $expectedSig = hash_hmac('sha256', $parts[0], $secret, true);
        if (!hash_equals($expectedSig, $sig)) {
            return false;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return false;
        }

        if (($payload['tool_id'] ?? '') !== $expectedToolId) {
            return false;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return false;
        }

        $payloadOut = $payload;

        return true;
    }

    private static function ensureYaml(): void
    {
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            return;
        }

        $autoload = dirname(__DIR__) . '/portal/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }
}
