# Eshop 前端整治备忘

记录当前 `eshop` 前台已经落地的改造，以及仍未收口的工作。原始评估稿里的“四阶段排期”绝大多数已经完成，这份文档不再展开评估，只保留事实和待办。

## 已落地的改造

### 安全与下单链路（原阶段一）

- 商品列表链接走统一的安全函数：`goods_list.php` 中 PHP 闭包 `getSafeHref()` 与 JS 函数 `getSafeUrl()` 同时处理协议白名单、`//` 起始、控制字符、`filter_var` 校验，非法链接回退为 `#`，并自动撤销 `target="_blank"`。
- 列表页所有"无目标 URL 的可点击元素"（精选卡片、分类卡片、子分类 chip、搜索 form）从 `<a href="javascript:;">` 改为 `<button type="button">`，仓库内不再存在 `javascript:;` 链接。
- `em.js` 引入了 `orderState` 统一持有 `goodsId / skuIds / quantity / paymentPlugin / couponCode / isSubmitting`。
- 下单提交链路接入了 `setSubmittingState()`，提交期间禁用按钮、设置 `aria-busy`、防止重复点击。
- 历史路径 `toBuyNow()` 已移除，仓库无任何调用点。

### 详情页状态与下单逻辑（原阶段二）

- SKU 可用性计算抽离为 `applySkuAvailability()` / `isSkuCompatible()` / `getSelectedSpecMap()`，`initSku()` 退化为只做状态同步。
- `getSelectedPayment()`、`getCouponCode()`、`getQuantityValue()`、`syncOrderStateFromDom()` 等小函数承担状态收集，下单 payload 不再从 DOM 临时拼装。
- 优惠券、支付方式、SKU 三处交互最终都走同一个状态对象。

### 详情页结构与样式来源（原阶段三）

- `content/templates/default/goods.php` 不再使用 `row / col-md-* / card-body` 等历史命名，改为 `goods-detail-card / goods-detail-layout / goods-detail-media / goods-purchase-form` 等明确的页面结构类。
- 模板内的 `<style>` 块和零散的内联 `style=` 已全部清理，对应规则收回到 `content/templates/default/css/style.css`。
- emoji 状态图标统一替换为 Font Awesome 图标。

### 前后台静态资源解耦

- 前台公共依赖从 `admin/views/...` 迁移到 `content/static/vendor/` 并入库为真实文件，目前包含 `jquery`、`font-awesome-4.7.0`、`layui-v2.11.6`、`clipboard`。
- `content/common/header.php`、`content/blog/default/header.php`、`user/views/*` 等入口都已切到新路径，前后台资源升级互不影响。
- 默认封面占位图也搬到了 `content/static/images/cover.svg`。`goods.php`、`goods_list.php` 中所有 `<img onerror>` 兜底全部改用新路径，前台模板不再引用任何 `admin/views/...` 资源。

## 仍未收口的剩余项

### 1. 设计 token 仍重复定义

`content/common/common.css` 与 `content/templates/default/css/style.css` 仍各自维护一套色板、阴影、圆角变量。
建议：保留一份权威定义（推荐放在 `common.css` 的 `:root`），另一份只覆盖差异化变量，避免后续视觉漂移。

### 2. 详情页 CSS 仍大量硬编码色值

`style.css` 里仍有约 200 处 `rgba(...)` / `#xxxxxx` 直接写死，特别是 `rgba(95, 132, 255, 0.xx)` 这类品牌色衍生值。
建议：在 token 去重时一并把这些魔法值替换为 `var(--brand)` / `var(--brand-soft)` / `var(--shadow-*)` 等变量。

### 3. 价格切换与缺货态体验打磨（低优先级）

`refreshGoodsInfo()` 仍是硬切换价格，`.spec-option.disabled` 已经有透明度 + 删除线，但没有过渡。属于体验打磨项，可在后续版本酌情处理。

### 4. 框架级接管暂不立项

只有当详情页继续大改时才需要重新评估是否引入 Alpine.js 之类的轻量方案。当前仓库没有这种需求，保持现状。

## 下一步建议

如果接下来还要继续整治前端，按以下顺序最稳：

1. 设计 token 去重，合并到一份权威配置。
2. 把详情页 CSS 中剩余的硬编码色值替换为变量。
3. 评估列表页与详情页是否需要再做一次组件命名上的统一。
