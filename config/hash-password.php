<?php

declare(strict_types=1);

/**
 * 生成管理员 password_hash，手动写入 config/auth.yaml 的 admin.password_hash。
 * 仅 CLI；若 Web 误映射到 config/ 目录，也会直接 403。
 *
 * 用法: php config/hash-password.php
 * 按提示输入密码（勿使用命令行参数，避免进入 shell history）。
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

if (isset($argv[1])) {
    fwrite(STDERR, "请勿在命令行传入密码（会留在 shell history）。\n");
    fwrite(STDERR, "请执行: php config/hash-password.php\n");
    exit(1);
}

fwrite(STDERR, "管理员密码: ");
$plain = fgets(STDIN);
if ($plain === false) {
    fwrite(STDERR, "读取失败。\n");
    exit(1);
}

$plain = rtrim($plain, "\r\n");
if ($plain === '') {
    fwrite(STDERR, "密码不能为空。\n");
    exit(1);
}

echo password_hash($plain, PASSWORD_DEFAULT) . PHP_EOL;
