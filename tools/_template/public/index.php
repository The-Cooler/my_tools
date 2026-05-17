<?php

declare(strict_types=1);

use Shared\ToolGate;

require_once dirname(__DIR__, 3) . '/shared/ToolGate.php';
ToolGate::check('your-tool-id');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uri === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'tool' => 'your-tool-id']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新工具模板</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        code { background: #f1f5f9; padding: 0.2em 0.4em; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>新工具模板</h1>
    <p>复制 <code>tools/_template</code> 目录，重命名为你的工具 id，并在 <code>config/tools.yaml</code> 中注册。</p>
</body>
</html>
