<?php defined('EM_ROOT') || exit('access denied!'); ?>
<?php doAction('adm_main_top') ?>

<div>
    <section class="index-kp">
        <div class="em-kpi-grid grid-cols-xs-1 grid-cols-sm-2 grid-cols-xl-4 grid-cols-lg-2 grid-cols-md-2 mb-3 grid-gap-10" style="width: 100%;">
            <div class="dashboard-card em-kpi-card" data-tone="sky" data-icon="sky">
                <div class="dashboard-card__header em-kpi-head">
                    <div class="em-kpi-title">
                        <span class="em-kpi-icon"><i class="layui-icon layui-icon-form"></i></span>
                        <span>订单数量</span>
                    </div>
                    <div class="em-kpi-tag">
                        <span class="em-kpi-pill em-kpi-pill--info">今日</span>
                    </div>
                </div>
                <div class="dashboard-card__body em-kpi-body">
                    <div class="em-kpi-value"><?= (int) $order_panel['today_orders'] ?></div>
                    <div class="em-kpi-sub">昨日 <?= (int) $order_panel['yesterday_orders'] ?> 单</div>
                </div>
                <div class="dashboard-card__footer em-kpi-foot">
                    <span class="em-kpi-meta-label">本月订单量</span>
                    <span class="em-kpi-foot-value"><?= (int) $order_panel['month_orders'] ?> 单</span>
                </div>
            </div>

            <div class="dashboard-card em-kpi-card" data-tone="amber" data-icon="amber">
                <div class="dashboard-card__header em-kpi-head">
                    <div class="em-kpi-title">
                        <span class="em-kpi-icon"><i class="layui-icon layui-icon-rmb"></i></span>
                        <span>销售额</span>
                    </div>
                    <div class="em-kpi-tag">
                        <span class="em-kpi-pill em-kpi-pill--info">今日</span>
                    </div>
                </div>
                <div class="dashboard-card__body em-kpi-body">
                    <div class="em-kpi-value"><?= number_format($today_sales_amount, 2) ?></div>
                    <div class="em-kpi-sub">昨日 <?= number_format($yesterday_sales_amount, 2) ?> 元</div>
                </div>
                <div class="dashboard-card__footer em-kpi-foot">
                    <span class="em-kpi-meta-label">本月销售额</span>
                    <span class="em-kpi-foot-value"><?= $current_month_sales_amount ?> 元</span>
                </div>
            </div>

            <div class="dashboard-card em-kpi-card" data-tone="violet" data-icon="violet">
                <div class="dashboard-card__header em-kpi-head">
                    <div class="em-kpi-title">
                        <span class="em-kpi-icon"><i class="layui-icon layui-icon-chart"></i></span>
                        <span>客单价</span>
                    </div>
                    <div class="em-kpi-tag">
                        <span class="em-kpi-pill em-kpi-pill--info">今日</span>
                    </div>
                </div>
                <div class="dashboard-card__body em-kpi-body">
                    <div class="em-kpi-value">
                        <?= empty($today_sales_amount) || empty($order_panel['today_orders']) ? 0 : number_format($today_sales_amount / $order_panel['today_orders'], 2) ?>
                    </div>
                    <div class="em-kpi-sub">
                        昨日 <?= empty($yesterday_sales_amount) || empty($order_panel['yesterday_orders']) ? 0 : number_format($yesterday_sales_amount / $order_panel['yesterday_orders'], 2) ?> 元
                    </div>
                </div>
                <div class="dashboard-card__footer em-kpi-foot">
                    <span class="em-kpi-meta-label">本月客单价</span>
                    <span class="em-kpi-foot-value">
                        <?= empty($current_month_sales_amount) || empty($order_panel['month_orders']) ? 0 : number_format(str_replace(',', '', $current_month_sales_amount) / $order_panel['month_orders'], 2) ?> 元
                    </span>
                </div>
            </div>

            <div class="dashboard-card em-kpi-card" data-tone="teal" data-icon="teal">
                <div class="dashboard-card__header em-kpi-head">
                    <div class="em-kpi-title">
                        <span class="em-kpi-icon"><i class="layui-icon layui-icon-user"></i></span>
                        <span>新增用户</span>
                    </div>
                    <div class="em-kpi-tag">
                        <span class="em-kpi-pill em-kpi-pill--info">今日</span>
                    </div>
                </div>
                <div class="dashboard-card__body em-kpi-body">
                    <div class="em-kpi-value"><?= (int) $user_panel['today_registrations'] ?></div>
                    <div class="em-kpi-sub">昨日 <?= (int) $user_panel['yesterday_registrations'] ?> 人</div>
                </div>
                <div class="dashboard-card__footer em-kpi-foot">
                    <span class="em-kpi-meta-label">本月新增用户</span>
                    <span class="em-kpi-foot-value"><?= (int) $user_panel['month_registrations'] ?> 人</span>
                </div>
            </div>
        </div>
    </section>

    <?php if (User::isAdmin()): ?>
        <?php doAction('adm_main_content') ?>
    <?php endif; ?>
</div>

<script>
    $("#menu-dashboard").addClass('active');
</script>

<style>
.index-kp .em-kpi-grid {
    grid-gap: 14px;
}

.index-kp .em-kpi-card {
    --kpi-accent: #0f766e;
    --kpi-accent-soft: rgba(15, 118, 110, 0.16);
    --kpi-icon: var(--kpi-accent);
    --kpi-icon-soft: var(--kpi-accent-soft);
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: #ffffff;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    min-height: 182px;
}

.index-kp .em-kpi-card[data-tone="sky"] {
    --kpi-accent: #0ea5e9;
    --kpi-accent-soft: rgba(14, 165, 233, 0.18);
}

.index-kp .em-kpi-card[data-tone="amber"] {
    --kpi-accent: #f59e0b;
    --kpi-accent-soft: rgba(245, 158, 11, 0.2);
}

.index-kp .em-kpi-card[data-tone="violet"] {
    --kpi-accent: #8b5cf6;
    --kpi-accent-soft: rgba(139, 92, 246, 0.18);
}

.index-kp .em-kpi-card[data-tone="teal"] {
    --kpi-accent: #0f766e;
    --kpi-accent-soft: rgba(15, 118, 110, 0.18);
}

.index-kp .em-kpi-card[data-icon="sky"] {
    --kpi-icon: #0ea5e9;
    --kpi-icon-soft: rgba(14, 165, 233, 0.18);
}

.index-kp .em-kpi-card[data-icon="amber"] {
    --kpi-icon: #f59e0b;
    --kpi-icon-soft: rgba(245, 158, 11, 0.2);
}

.index-kp .em-kpi-card[data-icon="violet"] {
    --kpi-icon: #8b5cf6;
    --kpi-icon-soft: rgba(139, 92, 246, 0.18);
}

.index-kp .em-kpi-card[data-icon="teal"] {
    --kpi-icon: #0f766e;
    --kpi-icon-soft: rgba(15, 118, 110, 0.18);
}

.index-kp .em-kpi-card .dashboard-card__header,
.index-kp .em-kpi-card .dashboard-card__body,
.index-kp .em-kpi-card .dashboard-card__footer {
    position: relative;
    z-index: 1;
}

.index-kp .em-kpi-card .dashboard-card__header {
    padding: 16px 18px 8px;
    border-bottom: none;
    background: transparent;
}

.index-kp .em-kpi-card .dashboard-card__body {
    padding: 4px 18px 14px;
    text-align: left;
}

.index-kp .em-kpi-card .dashboard-card__footer {
    padding: 12px 18px 16px;
    border-top: 1px dashed rgba(15, 23, 42, 0.1);
    background: rgba(249, 250, 251, 0.7);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.index-kp .em-kpi-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.index-kp .em-kpi-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #111827;
}

.index-kp .em-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--kpi-icon-soft);
    color: var(--kpi-icon);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.65);
}

.index-kp .em-kpi-icon i {
    font-size: 18px;
}

.index-kp .em-kpi-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.index-kp .em-kpi-value {
    font-size: 26px;
    font-weight: 700;
    line-height: 1.1;
    color: var(--kpi-accent);
    font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    text-align: center;
    width: 100%;
}

.index-kp .em-kpi-sub {
    margin-top: 6px;
    color: #6b7280;
    font-size: 12px;
    text-align: center;
}

.index-kp .em-kpi-meta-label {
    font-size: 12px;
    color: #6b7280;
}

.index-kp .em-kpi-foot-value {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

.index-kp .em-kpi-pill {
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
    background: rgba(15, 118, 110, 0.12);
    color: #0f766e;
}

.index-kp .em-kpi-pill--info {
    background: rgba(14, 165, 233, 0.16);
    color: #0284c7;
    border-color: rgba(14, 165, 233, 0.32);
}

@media (max-width: 768px) {
    .index-kp .em-kpi-card {
        min-height: 168px;
    }

    .index-kp .em-kpi-value {
        font-size: 22px;
    }
}
</style>
