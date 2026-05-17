<?php

declare(strict_types=1);

/**
 * PHP 内置服务器路由：仅允许访问 public 目录内的非 PHP 静态资源，其余走 index.php。
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uri !== '/' && ($uri[0] !== '/' || str_contains($uri, '..'))) {
    http_response_code(403);
    exit;
}

$publicRoot = realpath(__DIR__);
if ($publicRoot === false) {
    require __DIR__ . '/index.php';
    return;
}

$candidate = $publicRoot . $uri;
$resolved = realpath($candidate);

$isSafeFile = $resolved !== false
    && is_file($resolved)
    && str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR);

if ($uri !== '/' && $isSafeFile) {
    $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        require __DIR__ . '/index.php';
        return;
    }

    return false;
}

require __DIR__ . '/index.php';
