<?php
/** @var string $toolId */
/** @var string $toolName */
/** @var string|null $error */
$pageTitle = '访问 · ' . $toolName;
ob_start();
?>
<div class="auth-card">
    <h1><?= htmlspecialchars($toolName, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="auth-hint">请输入管理员分发的访问密钥。每把密钥仅可绑定<strong>一台设备</strong>（本浏览器）。</p>
    <?php if (!empty($error)): ?>
        <p class="auth-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post" action="/use/<?= htmlspecialchars($toolId, ENT_QUOTES, 'UTF-8') ?>" class="auth-form" id="use-form">
        <input type="hidden" name="device_id" id="device_id" value="">
        <label>
            访问密钥
            <input type="password" name="secret" required autocomplete="off" placeholder="请输入密钥">
        </label>
        <button type="submit" class="btn btn-primary">进入工具</button>
    </form>
</div>
<script>
(function () {
    var key = 'my_tools_device_id';
    var id = localStorage.getItem(key);
    if (!id) {
        id = (crypto.randomUUID && crypto.randomUUID()) ||
            'd-' + Date.now() + '-' + Math.random().toString(36).slice(2);
        localStorage.setItem(key, id);
    }
    document.getElementById('device_id').value = id;
})();
</script>
<?php
$content = ob_get_clean();
$authLayout = true;
require __DIR__ . '/layout.php';
