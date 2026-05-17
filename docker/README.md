# Docker 工具（可选）

仅当你要挂载**上游开源镜像**时才需要 `docker-compose.yml`。

1. 阅读 [docker-compose.yml.template](../docker-compose.yml.template)
2. 复制为 `docker-compose.yml` 并填写 `image:`、`ports`
3. 在 [config/tools.yaml.template](../config/tools.yaml.template) 中参考 `runtime: docker` 示例，写入 `config/tools.yaml`

本仓库**不会**预置任何 Docker 工具；由你自行选择要运行的开源项目。
