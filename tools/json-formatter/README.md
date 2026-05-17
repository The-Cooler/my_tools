# JSON 格式化

端口 `8101`（见 `config/tools.yaml`）。

## 启动

门户总览页点击 **启动**，或：

```sh
cd public
php -S 127.0.0.1:8101 index.php
```

访客密钥在门户 **工具面板** 中管理。

## 端点

- `GET /health`
- `GET /`、`POST /`（action: format | minify | validate）
