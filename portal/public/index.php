<?php

declare(strict_types=1);

use Portal\App;
use Slim\Factory\AppFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$rootPath = dirname(__DIR__, 2);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

App::register($app, $rootPath);

$app->run();
