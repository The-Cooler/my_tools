<?php
/** @var string $pageTitle */
/** @var string $toolId */
/** @var string $toolName */
/** @var string $toolRuntime */
/** @var array<int, array<string, mixed>> $toolKeys */
/** @var array<int, array<string, mixed>> $accessLog */
/** @var array<int, string> $blockedIps */

ob_start();
?>
<header class="header">
    <div>
        <a href="/?tab=panels" class="back-link">← 工具面板</a>
        <h1><?= htmlspecialchars($toolName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle"><?= htmlspecialchars($toolId, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($toolRuntime, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="search-wrap">
        <a class="btn btn-ghost" href="/use/<?= htmlspecialchars($toolId, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">访客入口</a>
        <form method="post" action="/logout" class="logout-form">
            <button type="submit" class="btn btn-ghost">退出</button>
        </form>
    </div>
</header>

<nav class="main-tabs">
    <a href="/?tab=overview" class="main-tab">总览</a>
    <a href="/?tab=panels" class="main-tab active">工具面板</a>
</nav>

<main class="main panel-tool-main">
    <?php if (!empty($showKeys)): ?>
    <section class="panel-section">
        <h2>访问密钥</h2>
        <form id="form-create-key" class="inline-form">
            <input type="text" name="label" placeholder="备注名" required>
            <input type="number" name="expires_days" placeholder="有效天数（空=永久）" min="1" style="width:140px">
            <button type="submit" class="btn btn-primary">生成密钥</button>
        </form>
        <p id="new-secret-box" class="secret-box hidden"></p>
        <table class="data-table">
            <thead>
                <tr><th>备注</th><th>ID</th><th>过期时间</th><th>状态</th><th></th></tr>
            </thead>
            <tbody id="keys-tbody">
                <?php foreach ($toolKeys as $k): ?>
                <tr data-key-id="<?= htmlspecialchars((string) $k['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= htmlspecialchars((string) $k['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><code><?= htmlspecialchars((string) $k['id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= !empty($k['expires_at']) ? date('Y-m-d H:i', (int) $k['expires_at']) : '永久' ?></td>
                    <td><?= !empty($k['expired']) ? '<span class="text-danger">已过期</span>' : '有效' ?></td>
                    <td><button type="button" class="btn btn-stop btn-sm btn-delete-key">删除</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php elseif ($toolRuntime === 'docker'): ?>
    <p class="panel-hint">Docker 工具需在 docker-compose.yml 中自行配置上游镜像；密钥用于门户 /use 入口。</p>
    <?php else: ?>
    <p class="panel-hint">外部链接工具无需配置访问密钥。</p>
    <?php endif; ?>

    <section class="panel-section">
        <h2>访问记录</h2>
        <p class="panel-hint">记录访客 IP、密钥、设备、是否持有有效令牌（最近 200 条）</p>
        <table class="data-table">
            <thead>
                <tr><th>时间</th><th>IP</th><th>事件</th><th>密钥</th><th>设备</th><th>令牌</th></tr>
            </thead>
            <tbody>
                <?php if ($accessLog === []): ?>
                    <tr><td colspan="6" class="empty-cell">暂无记录</td></tr>
                <?php else: ?>
                    <?php foreach ($accessLog as $row): ?>
                    <tr>
                        <td><?= date('m-d H:i:s', (int) ($row['at'] ?? 0)) ?></td>
                        <td><code><?= htmlspecialchars((string) ($row['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars((string) ($row['event'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['key_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars(substr((string) ($row['device_id'] ?? '-'), 0, 12), ENT_QUOTES, 'UTF-8') ?>…</td>
                        <td><?= !empty($row['has_token']) ? '是' : '否' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="panel-section">
        <h2>IP 黑名单</h2>
        <form id="form-block-ip" class="inline-form">
            <input type="text" name="ip" placeholder="IP 地址" required pattern="^[\d.:a-fA-F]+$">
            <button type="submit" class="btn btn-stop">拉黑</button>
        </form>
        <ul class="ip-list" id="blocked-ip-list">
            <?php foreach ($blockedIps as $ip): ?>
                <li><code><?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?></code>
                    <button type="button" class="btn btn-ghost btn-sm btn-unblock" data-ip="<?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') ?>">解除</button>
                </li>
            <?php endforeach; ?>
            <?php if ($blockedIps === []): ?>
                <li class="muted">暂无拉黑 IP</li>
            <?php endif; ?>
        </ul>
    </section>
</main>

<script>
(function () {
    const toolId = <?= json_encode($toolId, JSON_UNESCAPED_UNICODE) ?>;

    const formKey = document.getElementById('form-create-key');
    if (formKey) formKey.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('/api/tools/' + encodeURIComponent(toolId) + '/keys', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(fd)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.plain_secret) {
                const box = document.getElementById('new-secret-box');
                box.textContent = '新密钥（仅显示一次）：' + data.plain_secret;
                box.classList.remove('hidden');
            }
            if (data.ok) location.reload();
        });
    });

    document.querySelectorAll('.btn-delete-key').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const row = btn.closest('tr');
            const keyId = row.getAttribute('data-key-id');
            if (!confirm('确定删除该密钥？')) return;
            fetch('/api/tools/' + encodeURIComponent(toolId) + '/keys/' + encodeURIComponent(keyId), {
                method: 'DELETE'
            }).then(function () { location.reload(); });
        });
    });

    document.getElementById('form-block-ip').addEventListener('submit', function (e) {
        e.preventDefault();
        const ip = this.querySelector('[name=ip]').value.trim();
        fetch('/api/tools/' + encodeURIComponent(toolId) + '/blocked-ips', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ip: ip })
        }).then(function () { location.reload(); });
    });

    document.querySelectorAll('.btn-unblock').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const ip = btn.getAttribute('data-ip');
            fetch('/api/tools/' + encodeURIComponent(toolId) + '/blocked-ips/' + encodeURIComponent(ip), {
                method: 'DELETE'
            }).then(function () { location.reload(); });
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layout.php';
