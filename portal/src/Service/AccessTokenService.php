<?php

declare(strict_types=1);

namespace Portal\Service;

use Shared\AccessToken;

final class AccessTokenService
{
    public function __construct(
        private readonly AuthConfig $authConfig,
    ) {
    }

    public function issue(string $toolId, string $keyId, string $deviceId): string
    {
        return AccessToken::issue($this->authConfig->rootPath(), $toolId, $keyId, $deviceId);
    }
}
