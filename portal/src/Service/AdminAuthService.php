<?php

declare(strict_types=1);

namespace Portal\Service;

use RuntimeException;

final class AdminAuthService
{
    public function __construct(
        private readonly AuthConfig $authConfig,
    ) {
    }

    public function attemptLogin(string $username, string $password): bool
    {
        $admin = $this->authConfig->admin();
        $expectedUser = (string) ($admin['username'] ?? '');
        $hash = (string) ($admin['password_hash'] ?? '');

        if ($expectedUser === '' || $hash === '' || str_contains($hash, 'REPLACE_ME')) {
            throw new RuntimeException('请先在 config/auth.yaml 配置管理员账号。');
        }

        if (!hash_equals($expectedUser, $username)) {
            return false;
        }

        return password_verify($password, $hash);
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_logged_in']);
    }

    public static function login(): void
    {
        $_SESSION['admin_logged_in'] = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_logged_in']);
    }
}
