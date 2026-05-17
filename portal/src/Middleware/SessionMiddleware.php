<?php

declare(strict_types=1);

namespace Portal\Middleware;

use Portal\Service\RedisConfig;
use Portal\Service\RedisSessionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    private static bool $handlerRegistered = false;

    public function __construct(
        private readonly RedisConfig $redisConfig,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if ($this->redisConfig->isEnabled() && !self::$handlerRegistered) {
                $handler = new RedisSessionHandler(
                    $this->redisConfig->createClient(),
                    $this->redisConfig->key('sess:'),
                    $this->redisConfig->sessionTtl(),
                );
                session_set_save_handler($handler, true);
                self::$handlerRegistered = true;
            }

            session_start();
        }

        return $handler->handle($request);
    }
}
