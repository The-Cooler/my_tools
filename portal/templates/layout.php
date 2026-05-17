<?php
/** @var string $pageTitle */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a2332;
            --border: #2d3a4f;
            --text: #e7ecf3;
            --muted: #8b9cb3;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --online: #22c55e;
            --offline: #ef4444;
            --unknown: #6b7280;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
        }
        .header {
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .header h1 { font-size: 1.25rem; font-weight: 600; }
        .header .subtitle { color: var(--muted); font-size: 0.875rem; }
        .search-wrap input {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            width: 280px;
            font-size: 0.875rem;
        }
        .search-wrap input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            min-height: calc(100vh - 65px);
        }
        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { border-bottom: 1px solid var(--border); }
        }
        .sidebar {
            border-right: 1px solid var(--border);
            padding: 1rem;
        }
        .sidebar nav a {
            display: block;
            padding: 0.5rem 0.75rem;
            color: var(--muted);
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: var(--surface);
            color: var(--text);
        }
        .main { padding: 1.5rem 2rem; }
        .category-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin: 1.5rem 0 0.75rem;
        }
        .category-title:first-child { margin-top: 0; }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            transition: border-color 0.15s;
        }
        .card:hover { border-color: var(--accent); }
        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .card h3 { font-size: 1rem; font-weight: 600; }
        .card .desc {
            color: var(--muted);
            font-size: 0.8125rem;
            margin-bottom: 0.75rem;
            min-height: 2.5em;
        }
        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 1rem;
        }
        .badge {
            background: var(--bg);
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 0.35rem;
        }
        .status-dot.online { background: var(--online); }
        .status-dot.offline { background: var(--offline); }
        .status-dot.unknown { background: var(--unknown); }
        .card-actions { display: flex; gap: 0.5rem; }
        .btn {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { color: var(--text); border-color: var(--muted); }
        .btn-start { background: #16a34a; color: #fff; }
        .btn-start:hover { background: #15803d; }
        .btn-start:disabled, .btn-stop:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-stop { background: transparent; color: #f87171; border: 1px solid #7f1d1d; }
        .btn-stop:hover { background: rgba(248, 113, 113, 0.1); }
        .card-actions { flex-wrap: wrap; }
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            background: var(--surface); border: 1px solid var(--border);
            padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem;
            max-width: 360px; z-index: 100; display: none;
        }
        .toast.show { display: block; }
        .empty { color: var(--muted); padding: 2rem; text-align: center; }
        .hidden { display: none !important; }
        .logout-form { display: inline; margin-left: 0.75rem; }
        .auth-page { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; }
        .auth-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 2rem; max-width: 420px; width: 100%;
        }
        .auth-card h1 { font-size: 1.25rem; margin-bottom: 0.75rem; }
        .auth-hint { color: var(--muted); font-size: 0.875rem; margin-bottom: 1.25rem; }
        .auth-hint code { background: var(--bg); padding: 0.1em 0.35em; border-radius: 4px; }
        .auth-error { color: #f87171; font-size: 0.875rem; margin-bottom: 1rem; }
        .auth-form label { display: block; font-size: 0.8125rem; color: var(--muted); margin-bottom: 1rem; }
        .auth-form input {
            display: block; width: 100%; margin-top: 0.35rem;
            background: var(--bg); border: 1px solid var(--border); color: var(--text);
            padding: 0.5rem 0.75rem; border-radius: 6px;
        }
        .auth-form .btn-primary { width: 100%; margin-top: 0.5rem; }
        .main-tabs {
            display: flex; gap: 0; border-bottom: 1px solid var(--border);
            padding: 0 2rem; background: var(--bg);
        }
        .main-tab {
            padding: 0.75rem 1.25rem; color: var(--muted); text-decoration: none;
            font-size: 0.875rem; border-bottom: 2px solid transparent; margin-bottom: -1px;
        }
        .main-tab:hover { color: var(--text); }
        .main-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
        .back-link { color: var(--muted); font-size: 0.875rem; text-decoration: none; display: block; margin-bottom: 0.35rem; }
        .panel-list-main { padding: 1.5rem 2rem; }
        .panel-list-hint { color: var(--muted); font-size: 0.875rem; margin-bottom: 1rem; }
        .panel-list { display: flex; flex-direction: column; gap: 0.5rem; max-width: 640px; }
        .panel-list-item {
            display: flex; align-items: center; gap: 0.75rem;
            background: var(--surface); border: 1px solid var(--border);
            padding: 1rem 1.25rem; border-radius: 8px; text-decoration: none; color: var(--text);
        }
        .panel-list-item:hover { border-color: var(--accent); }
        .panel-list-name { flex: 1; font-weight: 500; }
        .panel-list-arrow { color: var(--muted); }
        .panel-tool-main { padding: 1.5rem 2rem; max-width: 1100px; }
        .panel-section { margin-bottom: 2.5rem; }
        .panel-section h2 { font-size: 1rem; margin-bottom: 1rem; }
        .panel-hint { color: var(--muted); font-size: 0.8125rem; margin-bottom: 0.75rem; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .data-table th, .data-table td { border: 1px solid var(--border); padding: 0.5rem 0.65rem; text-align: left; }
        .data-table th { background: var(--surface); color: var(--muted); }
        .data-table code, .mono { font-family: ui-monospace, monospace; font-size: 0.75rem; }
        .empty-cell { color: var(--muted); text-align: center; }
        .inline-form { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; align-items: center; }
        .inline-form input {
            background: var(--surface); border: 1px solid var(--border); color: var(--text);
            padding: 0.45rem 0.65rem; border-radius: 6px;
        }
        .secret-box { background: #14532d; color: #bbf7d0; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; word-break: break-all; }
        .text-danger { color: #f87171; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .ip-list { list-style: none; }
        .ip-list li { padding: 0.35rem 0; display: flex; align-items: center; gap: 0.75rem; }
        .ip-list .muted { color: var(--muted); }
    </style>
</head>
<body<?= !empty($authLayout) ? ' class="auth-page"' : '' ?>>
    <?= $content ?>
    <div id="toast" class="toast" role="status"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('tool-search');
            const cards = document.querySelectorAll('.card[data-search]');
            const navLinks = document.querySelectorAll('.sidebar nav a');
            const sections = document.querySelectorAll('[data-category]');

            if (search) {
                search.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    cards.forEach(function (card) {
                        const text = card.getAttribute('data-search') || '';
                        card.classList.toggle('hidden', q !== '' && !text.includes(q));
                    });
                    sections.forEach(function (section) {
                        const visible = section.querySelectorAll('.card:not(.hidden)').length;
                        section.classList.toggle('hidden', visible === 0);
                    });
                });
            }

            navLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const cat = this.getAttribute('data-cat');
                    navLinks.forEach(function (l) { l.classList.remove('active'); });
                    this.classList.add('active');
                    sections.forEach(function (section) {
                        if (cat === 'all') {
                            section.classList.remove('hidden');
                        } else {
                            section.classList.toggle('hidden', section.getAttribute('data-category') !== cat);
                        }
                    });
                    if (search) search.value = '';
                    cards.forEach(function (c) { c.classList.remove('hidden'); });
                });
            });

            const toast = document.getElementById('toast');

            function showToast(msg) {
                if (!toast) return;
                toast.textContent = msg;
                toast.classList.add('show');
                setTimeout(function () { toast.classList.remove('show'); }, 3500);
            }

            function refreshHealth() {
                return fetch('/api/health')
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        const health = data.health || {};
                        document.querySelectorAll('.status-dot[data-tool-id]').forEach(function (dot) {
                            const id = dot.getAttribute('data-tool-id');
                            const status = health[id] || 'unknown';
                            dot.className = 'status-dot ' + status;
                            dot.title = status;
                        });
                    });
            }

            document.querySelectorAll('.card[data-tool-id]').forEach(function (card) {
                const id = card.getAttribute('data-tool-id');
                const runtime = card.getAttribute('data-runtime');
                if (runtime !== 'php' && runtime !== 'docker') return;

                card.querySelectorAll('[data-action]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const action = btn.getAttribute('data-action');
                        btn.disabled = true;
                        fetch('/api/tools/' + encodeURIComponent(id) + '/' + action, { method: 'POST' })
                            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                            .then(function (res) {
                                showToast(res.data.message || res.data.error || (res.ok ? '完成' : '失败'));
                                return refreshHealth();
                            })
                            .catch(function () { showToast('请求失败'); })
                            .finally(function () {
                                btn.disabled = false;
                            });
                    });
                });
            });

            refreshHealth().catch(function () {});
        });
    </script>
</body>
</html>
