<?php
/** @var string|null $error */
$pageTitle = '登录 · 工具工作站';
ob_start();
?>
<div class="auth-card">
    <h1>管理员登录</h1>
    <p class="auth-hint">管理首页与工具启停需登录。访客请使用各工具的专用访问链接 <code>/use/{工具id}</code> 与密钥进入。</p>
    <?php if (!empty($error)): ?>
        <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post" action="/login" class="auth-form">
        <label>
            账号
            <input type="text" name="username" required autocomplete="username">
        </label>
        <label>
            密码
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary">登录</button>
    </form>
</div>
<?php
$content = ob_get_clean();
$authLayout = true;
require __DIR__ . '/layout.php';
