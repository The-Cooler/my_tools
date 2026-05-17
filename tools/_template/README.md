# 工具模板

1. 复制为 `tools/{your-id}/`
2. 将 `public/index.php` 里 `ToolGate::check('your-tool-id')` 改为你的 id
3. 在 `config/tools.yaml` 登记（`runtime: php`，分配端口）
4. 登录门户 → **工具面板** → 生成访客密钥
5. 在总览页 **启动** 工具

约定：`GET /health` 返回 `{"status":"ok"}`。
