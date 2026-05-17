<?php

declare(strict_types=1);

namespace Shared;

require_once __DIR__ . '/AccessToken.php';
require_once __DIR__ . '/ClientIp.php';
require_once __DIR__ . '/IpBlocklist.php';
require_once __DIR__ . '/AccessLogger.php';

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

        $cookieName = 'tool_access_' . preg_replace('/[^a-z0-9_-]/i', '', $toolId);
        $token = trim((string) ($_GET['access_token'] ?? $_COOKIE[$cookieName] ?? ''));

        $payload = null;
        try {
            $ok = $token !== '' && AccessToken::verify($token, $toolId, $root, $payload);
        } catch (\Throwable) {
            $ok = false;
        }

        if (!$ok) {
            AccessLogger::log($toolId, $ip, 'access_denied', null, null, false);
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="zh-CN"><meta charset="UTF-8"><body>';
            echo '<p>需要有效访问密钥。</p>';
            echo '<p>请从门户首页登录后点击「打开」；若刚修改过 config/auth.yaml 中的 app_secret，请重新打开，勿使用旧链接。</p>';
            echo '<p><a href="/">返回工作站</a></p>';
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
            $cookiePath = self::cookiePath();
            setcookie($cookieName, $token, [
                'expires' => time() + 86400 * 7,
                'path' => $cookiePath,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $target = self::publicPath();
            $query = $_GET;
            unset($query['access_token']);
            if ($query !== []) {
                $target .= '?' . http_build_query($query);
            }
            header('Location: ' . $target, true, 302);
            exit;
        }
    }

    /** Nginx 反代子路径时由 proxy_set_header X-Forwarded-Prefix 传入，如 /apps/json-formatter */
    private static function forwardedPrefix(): string
    {
        $prefix = trim((string) ($_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? ''));
        if ($prefix === '') {
            return '';
        }

        return '/' . trim($prefix, '/');
    }

    /** 浏览器应使用的路径（含反代前缀，避免 302 到站点根目录 /） */
    private static function publicPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $prefix = self::forwardedPrefix();
        if ($prefix === '') {
            return $path;
        }
        if ($path === '/' || $path === '') {
            return $prefix . '/';
        }

        return $prefix . $path;
    }

    private static function cookiePath(): string
    {
        $prefix = self::forwardedPrefix();

        return $prefix !== '' ? $prefix . '/' : '/';
    }

}
