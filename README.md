# PHP 个人工具工作站

门户聚合多工具；配置以 `*.template` 说明为准，复制为正式文件名后生效。详见 [config/README.md](config/README.md)。

## 目录结构

```
my_tools/
├── config/
│   ├── tools.yaml                 # 工具注册表（运行用，可自 template 复制）
│   ├── tools.yaml.template        # 字段说明与示例
│   ├── auth.yaml                  # 管理员、app_secret（勿提交，需自建）
│   ├── auth.yaml.template
│   ├── redis.yaml.template        # Redis（Session / 配置缓存，宝塔推荐）
│   ├── hash-password.php          # CLI 生成管理员 password_hash
│   └── README.md                  # 配置索引
├── docs/
│   └── baota.md                   # 宝塔面板部署说明
├── portal/                        # 门户（默认开发端口 8080）
│   ├── public/
│   │   ├── index.php              # Web 入口
│   │   └── router.php             # 仅 php -S 开发时用
│   ├── src/                       # 路由、启停、密钥、面板 API
│   └── templates/                 # 首页、登录、工具面板
├── tools/                         # 自研 PHP 工具（每工具独立端口）
│   ├── _template/
│   └── json-formatter/            # 示例（8101）
├── shared/                        # ToolGate、访问日志、IP 拉黑
├── storage/                       # 运行时（gitignore，自动创建）
│   ├── cache/                     # tools.yaml 文件缓存（未开 Redis 时）
│   ├── runtime/                   # PHP 工具进程 PID、日志
│   └── auth/                      # 密钥、绑定、访问日志、黑名单
├── docker/
│   └── README.md                  # Docker 工具说明（可选）
├── nginx/
│   ├── index.conf.template        # 生产 Nginx 模板
│   └── index.conf                 # 可按需修改的工作副本
├── docker-compose.yml             # Docker 编排（可选，默认可为空）
├── docker-compose.yml.template
└── README.md
```

## 配置文件一览

| 模板 | 正式文件 | 作用 |
|------|----------|------|
| [config/tools.yaml.template](config/tools.yaml.template) | `config/tools.yaml` | 工具列表、端口、类型 |
| [config/auth.yaml.template](config/auth.yaml.template) | `config/auth.yaml` | 管理员、`app_secret` |
| [config/redis.yaml.template](config/redis.yaml.template) | `config/redis.yaml` | 可选，Session 与配置缓存 |
| [docker-compose.yml.template](docker-compose.yml.template) | `docker-compose.yml` | 可选，Docker 上游镜像 |
| [nginx/index.conf.template](nginx/index.conf.template) | Nginx include | 可选，生产门户 |

环境变量（仅 `PORTAL_MANAGE_TOKEN`，无 `.env` 文件）见 [config/README.md](config/README.md)。

访客密钥、访问日志、IP 黑名单等由门户写入 `storage/`，见 [config/README.md](config/README.md)。

## 快速开始

```sh
cd portal && composer install
cp config/auth.yaml.template config/auth.yaml
cp config/tools.yaml.template config/tools.yaml   # 若尚无 tools.yaml
php config/hash-password.php
# 编辑 auth.yaml：app_secret、password_hash
cd portal/public && php -S 127.0.0.1:8080 router.php
```

浏览器打开 http://127.0.0.1:8080/ 登录。访客密钥在 **工具面板** 生成。

生产环境用 [nginx/index.conf.template](nginx/index.conf.template)，不用 `router.php`。宝塔部署见 [docs/baota.md](docs/baota.md)。

## 新增工具

- **PHP**：`tools/_template` → `tools/{id}`，登记 `tools.yaml`，面板发密钥，总览启停。
- **Docker（可选）**：自行在 `docker-compose.yml.template` 基础上添加**你需要的**上游 `image:`，再登记 `tools.yaml`；仓库不预置任何 Docker 工具。
- **外链**：`runtime: external` + `url`。

## API

见 [config/README.md](config/README.md) 或原门户路由：`/`、`/panel/tool/{id}`、`/use/{id}`、`/api/tools/...`。
