# 配置文件说明

本目录及项目根目录的 `*.template` 为**说明与示例**，复制为正式文件名后才会被程序读取。

| 模板文件 | 复制为 | 作用 |
|----------|--------|------|
| [tools.yaml.template](tools.yaml.template) | `config/tools.yaml` | 工具注册表（端口、类型、门户展示） |
| [auth.yaml.template](auth.yaml.template) | `config/auth.yaml` | 管理员登录、`app_secret` |
| [redis.yaml.template](redis.yaml.template) | `config/redis.yaml` | **可选**；Session + tools.yaml 缓存（宝塔推荐开启） |
| [../docker-compose.yml.template](../docker-compose.yml.template) | `docker-compose.yml` | **可选**；上游 Docker 镜像编排 |
| [../nginx/index.conf.template](../nginx/index.conf.template) | 纳入 Nginx `include` | **可选**；生产环境门户 Web |

## 环境变量（无 .env 文件）

本项目**不读取** `.env`。若门户与调用方不在同一台机器，需在 PHP-FPM / systemd 等运行环境中设置：

| 变量 | 作用 |
|------|------|
| `PORTAL_MANAGE_TOKEN` | 非本机调用 `POST /api/tools/{id}/start\|stop` 时，请求头须带 `X-Portal-Token` |

本机（`127.0.0.1`）调用启停 API 时可不设置。

## 运行时自动生成（勿手改结构）

| 路径 | 作用 |
|------|------|
| `storage/auth/keys.json` | 访客密钥（在门户工具面板增删） |
| `storage/auth/bindings.json` | 密钥与设备绑定 |
| `storage/auth/access.log` | 访问审计 |
| `storage/auth/ip_blocks.json` | IP 黑名单 |
| `storage/cache/tools.php` | tools.yaml 解析缓存（未启用 Redis 时） |
| `storage/runtime/` | PHP 工具进程 PID、日志 |

## 其它

| 文件 | 作用 |
|------|------|
| [hash-password.php](hash-password.php) | CLI 生成管理员 `password_hash`，见 `auth.yaml.template` |

宝塔部署见 [docs/baota.md](../docs/baota.md)。
