<?php
/** @var string $pageTitle */
/** @var string $activeTab */
/** @var array<string, array<int, array<string, mixed>>> $groupedTools */
/** @var array<int, array<string, mixed>> $panelTools */

$categories = array_keys($groupedTools);
$tab = $activeTab ?? 'overview';

ob_start();
?>
<header class="header">
    <div>
        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">统一入口 · 启停 · 密钥与访问管理</p>
    </div>
    <div class="search-wrap">
        <?php if ($tab === 'overview'): ?>
            <input type="search" id="tool-search" placeholder="搜索工具名称、标签…" autocomplete="off">
        <?php endif; ?>
        <form method="post" action="/logout" class="logout-form">
            <button type="submit" class="btn btn-ghost">退出登录</button>
        </form>
    </div>
</header>

<nav class="main-tabs">
    <a href="/?tab=overview" class="main-tab <?= $tab === 'overview' ? 'active' : '' ?>">总览</a>
    <a href="/?tab=panels" class="main-tab <?= $tab === 'panels' ? 'active' : '' ?>">工具面板</a>
</nav>

<?php if ($tab === 'overview'): ?>
<div class="layout">
    <aside class="sidebar">
        <nav>
            <a href="#" data-cat="all" class="active">全部</a>
            <?php foreach ($categories as $cat): ?>
                <a href="#" data-cat="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="main">
        <?php if (empty($groupedTools)): ?>
            <p class="empty">暂无已启用的工具，请在 config/tools.yaml 中注册。</p>
        <?php else: ?>
            <?php foreach ($groupedTools as $category => $tools): ?>
                <section data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                    <h2 class="category-title"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="cards">
                        <?php foreach ($tools as $tool):
                            $id = (string) ($tool['id'] ?? '');
                            $name = (string) ($tool['name'] ?? '');
                            $desc = (string) ($tool['description'] ?? '');
                            $runtime = (string) ($tool['runtime'] ?? '');
                            $port = $tool['port'] ?? null;
                            $tags = $tool['tags'] ?? [];
                            $searchText = strtolower($name . ' ' . $desc . ' ' . implode(' ', $tags) . ' ' . $id);
                            $urlResolved = (string) ($tool['url_resolved'] ?? '#');
                        ?>
                        <article class="card" data-tool-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-runtime="<?= htmlspecialchars($runtime, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if (in_array($runtime, ['php', 'docker'], true)): ?>
                                    <span class="status-dot unknown" data-tool-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" title="检测中"></span>
                                <?php endif; ?>
                            </div>
                            <p class="desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="card-meta">
                                <span class="badge"><?= htmlspecialchars($runtime, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($port !== null): ?><span class="badge">:<?= (int) $port ?></span><?php endif; ?>
                            </div>
                            <div class="card-actions">
                                <?php if (in_array($runtime, ['php', 'docker'], true)): ?>
                                    <button type="button" class="btn btn-start" data-action="start">启动</button>
                                    <button type="button" class="btn btn-stop" data-action="stop">停止</button>
                                <?php endif; ?>
                                <a class="btn btn-primary" href="/go/<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">打开</a>
                                <a class="btn btn-ghost" href="/panel/tool/<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">管理</a>
                                <?php if ($runtime !== 'external'): ?>
                                    <a class="btn btn-ghost" href="/use/<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">访客入口</a>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<?php else: ?>
<main class="main panel-list-main">
    <p class="panel-list-hint">进入各工具面板：管理密钥、查看访问 IP、拉黑 IP。</p>
    <div class="panel-list">
        <?php foreach ($panelTools as $tool):
            $id = (string) ($tool['id'] ?? '');
            $name = (string) ($tool['name'] ?? $id);
            $runtime = (string) ($tool['runtime'] ?? '');
        ?>
        <a class="panel-list-item" href="/panel/tool/<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
            <span class="panel-list-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge"><?= htmlspecialchars($runtime, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="panel-list-arrow">→</span>
        </a>
        <?php endforeach; ?>
    </div>
</main>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
