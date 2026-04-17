# emshop

快速稳定的轻量级 PHP 虚拟商城系统。

## 环境要求

- PHP 7.2 及以上，推荐 7.4 或 8.1
- MySQL 5.6 及以上
- 推荐环境：Linux + Nginx

## 本地开发

1. 按需复制 `.env.example` 为 `.env`
2. 只在本地填写支付或其他敏感配置，不要提交 `.env`
3. 修改 PHP 文件后，优先执行 `php -l path/to/file.php` 做语法检查

## 开源约定

- 仓库不提交真实线上域名、服务器地址、SSH 凭据、后台账号口令、API Token、支付私钥
- 本地完整运维说明保留在未跟踪的 `AGENTS.md`；公开版说明保留在 `AGENTS.public.md`
- 运行时目录和测试产物不进入 Git，例如缓存、上传、日志、临时文件、`test-results`
- 示例配置只保留在 `.env.example`

## 支付配置

易付通相关环境变量示例见 `.env.example`。

## License

This project is licensed under the GNU Affero General Public License v3.0 only. See `LICENSE`.
