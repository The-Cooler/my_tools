<?php

declare(strict_types=1);

use JsonFormatter\JsonService;
use Shared\ToolGate;

require_once dirname(__DIR__, 3) . '/shared/ToolGate.php';
ToolGate::check('json-formatter');

require_once dirname(__DIR__) . '/src/JsonService.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($uri === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'tool' => 'json-formatter']);
    exit;
}

$service = new JsonService();
$error = null;
$input = '';
$output = '';
$action = '';

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'format';
    $input = (string) ($_POST['input'] ?? '');

    try {
        $output = match ($action) {
            'minify' => $service->minify($input),
            'validate' => $service->validate($input),
            default => $service->format($input),
        };
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

require dirname(__DIR__) . '/templates/page.php';
