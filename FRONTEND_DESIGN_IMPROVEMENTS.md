# Eshop 前端整治备忘

记录当前 `eshop` 前台已落地的改造，以及仍未收口的工作。原始评估稿里的"四阶段排期"已全部收尾，这份文档只保留事实和待办。

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

### 设计 token 与颜色变量化

- `content/common/common.css :root` 已是全站唯一的权威 token 源，统一维护品牌主色 / 次品牌色 / 文本层级 / 表面 / 阴影 / 圆角档位；`--muted` 保留全局既有值，列表页浅灰升为 `--muted-soft`，避免牵动其它页面的文字层级。
- `content/templates/default/css/style.css` 的 `.df-list-page.blog-container` 作用域只剩列表页徽标专用的 `--df-gold / --df-gold-soft`，其余 `--df-*` 已直接对齐全局 token。
- 高频硬编码色值（`rgba(95,132,255,…)` / `rgba(109,158,234,…)` / `rgba(131,177,246,…)` / `rgba(15,23,42,…)` 及对应 hex）在 `style.css` 中已全部切到 `--brand-* / --brand-alt-* / --shadow-ink-*` 变量，渐变里的特殊 alpha 用 `rgba(var(--*-rgb), x)` 写法表达。

## 剩余项

1. **低频一次性色值**：`style.css` 的高频品牌色、阴影色、常用浅表面和输入框边框已变量化；纯 `#fff`、少量灰阶和一次性渐变端点继续保留，避免为了归零制造过多 token。
2. **交互体验打磨**：`refreshGoodsInfo()` 更新价格、库存、销量时已有淡入过渡；`.spec-option.disabled` 补了禁用态过渡；下单按钮在 `aria-busy="true"` 时显示 CSS spinner。后续只在真实投诉或新交互出现时继续微调。
3. **框架级接管暂不立项**：只有当详情页继续大改时才需要重新评估是否引入 Alpine.js 之类的轻量方案。

## 组件命名盘点（先不重构）

按钮类：

- `.header-auth-btn`（`is-ghost` / `is-solid` 两种变体）、`.spec-option`（规格 chip，行为强依赖 JS 状态）、`.df-subtab-chip`（列表页 chip action）是未来 `.u-btn` / `.u-chip` 基类的主要候选来源。
- 历史名 `.btn-submit` / `.goods-action-btn` / `.df-chip-button` / `.df-tag` / `.blog-card` 在当前默认模板里均无使用点，后续重新引入时直接对齐新基类即可，不必反向抽离。

卡片类：

- `.goods-detail-card` / `.goods-content-card` / `.df-site-notice` 共享白底 + 边框 + 圆角 + 阴影，是 `.u-card` 的主要抽取来源；保留各自的 padding 与头部区域差异。

徽标与 Chip：

- `.goods-stat-item` 更接近小卡片，`.df-pill` 是纯状态徽标，`.df-subtab-chip` 是筛选 chip，未来 `.u-chip` 主要以 `.df-pill` 的样式收敛。

## 下一步建议

如果接下来还要继续整治前端，按以下顺序最稳：

1. 在新增页面或下次详情页改动时试点 `.u-btn` / `.u-card` / `.u-chip` 基类，不回头批量改旧类名。
2. 当详情页结构继续大改时，再评估是否引入 Alpine.js 等轻量状态层；当前保持不立项。
3. 继续按重复度收口低频硬编码色值，避免为了纯粹归零引入过多一次性 token。
