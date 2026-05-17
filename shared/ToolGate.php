<?php

declare(strict_types=1);

namespace Shared;

/**
 * PHP 工具入口校验：持门户签发的 access_token 或 Cookie 访问。
 * /health 不校验，供门户探活。
 */
final class ToolGate
{
    public static function check(string $toolId): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($uri === '/health') {
            return;
        }

        $ip = ClientIp::resolve();
        if (IpBlocklist::isBlocked($toolId, $ip)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo '访问被拒绝';
            exit;
        }

        $root = dirname(__DIR__);
        $authPath = $root . '/config/auth.yaml';
        if (!is_file($authPath)) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo '未配置 config/auth.yaml';
            exit;
        }

        if (!class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            $autoload = $root . '/portal/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }

        $auth = \Symfony\Component\Yaml\Yaml::parseFile($authPath);
        $secret = (string) ($auth['app_secret'] ?? '');
        if ($secret === '' || str_contains($secret, '请替换')) {
            http_response_code(503);
            echo 'app_secret 未配置';
            exit;
        }

        $cookieName = 'tool_access_' . preg_replace('/[^a-z0-9_-]/i', '', $toolId);
        $token = $_GET['access_token'] ?? $_COOKIE[$cookieName] ?? '';

        $payload = null;
        if ($token === '' || !self::verifyToken($token, $toolId, $secret, $payload)) {
            AccessLogger::log($toolId, $ip, 'access_denied', null, null, false);
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="zh-CN"><meta charset="UTF-8"><body>';
            echo '<p>需要有效访问密钥。请从工作站入口使用密钥进入。</p>';
            echo '</body></html>';
            exit;
        }

        AccessLogger::log(
            $toolId,
            $ip,
            'access_ok',
            $payload['key_id'] ?? null,
            $payload['device_id'] ?? null,
            true
        );

        if (isset($_GET['access_token'])) {
            setcookie($cookieName, $token, [
                'expires' => time() + 86400 * 7,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /**
     * @param array<string, mixed>|null $payloadOut
     */
    private static function verifyToken(string $token, string $expectedToolId, string $secret, ?array &$payloadOut = null): bool
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
}
