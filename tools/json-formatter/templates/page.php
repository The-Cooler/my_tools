<?php
/** @var string|null $error */
/** @var string $input */
/** @var string $output */
/** @var string $action */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON 格式化</title>
    <style>
        :root { --bg: #0f1419; --surface: #1a2332; --border: #2d3a4f; --text: #e7ecf3; --muted: #8b9cb3; --accent: #3b82f6; --error: #f87171; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", system-ui, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 1.5rem; }
        h1 { font-size: 1.25rem; margin-bottom: 1rem; }
        .toolbar { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        button, .btn-link {
            background: var(--accent); color: #fff; border: none; padding: 0.5rem 1rem;
            border-radius: 6px; cursor: pointer; font-size: 0.875rem; text-decoration: none;
        }
        button.secondary { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        label { display: block; font-size: 0.75rem; color: var(--muted); margin-bottom: 0.35rem; }
        textarea {
            width: 100%; min-height: 320px; background: var(--surface); border: 1px solid var(--border);
            color: var(--text); padding: 0.75rem; border-radius: 8px; font-family: ui-monospace, monospace;
            font-size: 0.8125rem; resize: vertical;
        }
        .error { color: var(--error); margin-bottom: 1rem; font-size: 0.875rem; }
        .output { white-space: pre-wrap; word-break: break-all; }
        a.back { color: var(--muted); font-size: 0.875rem; display: inline-block; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <a class="back" href="http://127.0.0.1:8080/">← 返回工作站</a>
    <h1>JSON 格式化</h1>

    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="toolbar">
            <button type="submit" name="action" value="format">格式化</button>
            <button type="submit" name="action" value="minify" class="secondary">压缩</button>
            <button type="submit" name="action" value="validate" class="secondary">校验</button>
        </div>
        <div class="grid">
            <div>
                <label for="input">输入</label>
                <textarea id="input" name="input" placeholder='{"hello":"world"}'><?= htmlspecialchars($input, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div>
                <label>输出</label>
                <textarea readonly class="output"><?= htmlspecialchars($output, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </form>
</body>
</html>
