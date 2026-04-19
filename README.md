# ESHOP

轻量级 PHP 虚拟商品商城系统。

## 环境要求

- PHP 7.4+，推荐 8.1
- MySQL 5.6+
- 推荐部署：Linux + Nginx

## 本地开发

1. 复制 `.env.example` 为 `.env`，按需填写本地配置
2. 真实支付密钥、域名、口令等敏感信息只放在 `.env`，禁止提交
3. 修改 PHP 文件后执行 `php -l path/to/file.php` 做语法检查

## 仓库约定

- 仓库不提交真实域名、服务器地址、SSH 凭据、后台账号、API Token、支付私钥
- 公开协作说明见 `AGENTS.public.md`；本机私有运维说明保留在未跟踪的 `AGENTS.md`
- 缓存、上传、日志、临时文件、`test-results` 等运行时产物不入库
- 示例配置只保留 `.env.example`
- 可见品牌名统一为大写 `ESHOP`，包名/路径仍使用小写 `eshop`

## 支付配置

易付通相关环境变量示例见 `.env.example`，接入文档参考 [yifut.com/doc](https://www.yifut.com/doc/index.html)。

## License

GNU Affero General Public License v3.0 only，详见 `LICENSE`。
