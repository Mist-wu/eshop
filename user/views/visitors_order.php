<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .orders-page {
        display: grid;
        gap: 18px;
    }

    .orders-hero {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 22px 24px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .hero-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--primary-strong);
    }

    .orders-title {
        margin: 6px 0 6px;
        font-size: 26px;
        font-weight: 700;
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
        color: var(--text);
    }

    .orders-subtitle {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        background: var(--panel-soft);
        border: 1px solid var(--border-soft);
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hero-pill i {
        color: var(--primary);
    }

    .hero-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
    }

    .order-list {
        display: grid;
        gap: 16px;
    }

    .order-card {
        background: var(--panel);
        border: 1px solid var(--border-soft);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .order-card:hover {
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
    }

    .order-head {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-soft);
        gap: 16px;
    }

    .order-label {
        font-size: 12px;
        color: var(--muted);
    }

    .order-no {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        margin-top: 4px;
        word-break: break-all;
    }

    .order-status {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-pending {
        background: rgba(245, 158, 11, 0.18);
        color: #b45309;
        border-color: rgba(245, 158, 11, 0.4);
    }

    .status-delivering {
        background: rgba(14, 116, 144, 0.16);
        color: #0e7490;
        border-color: rgba(14, 116, 144, 0.4);
    }

    .status-completed {
        background: rgba(16, 185, 129, 0.18);
        color: #047857;
        border-color: rgba(16, 185, 129, 0.35);
    }

    .status-cancelled {
        background: rgba(239, 68, 68, 0.16);
        color: #b91c1c;
        border-color: rgba(239, 68, 68, 0.35);
    }

    .order-body {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .goods-img {
        width: 72px;
        height: 72px;
        border-radius: 14px;
        object-fit: cover;
        background: #f3f4f6;
        flex-shrink: 0;
    }

    .goods-info {
        flex: 1;
        min-width: 0;
    }

    .goods-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        line-height: 1.5;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .goods-name a {
        color: inherit;
    }

    .goods-spec {
        font-size: 12px;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .order-meta {
        min-width: 160px;
        text-align: right;
        display: grid;
        gap: 6px;
    }

    .meta-item {
        display: grid;
        gap: 2px;
    }

    .meta-label {
        font-size: 11px;
        color: var(--muted);
    }

    .meta-value {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
    }

    .order-footer {
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: var(--panel-soft);
        border-top: 1px solid var(--border-soft);
        flex-wrap: wrap;
    }

    .order-total {
        display: grid;
        gap: 4px;
    }

    .order-total-label {
        font-size: 12px;
        color: var(--muted);
    }

    .order-total-value {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-strong);
    }

    .order-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .action-btn {
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #ffffff;
        color: var(--text);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    .action-btn.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-strong));
        border-color: transparent;
        color: #ffffff;
    }

    .action-btn.danger {
        background: rgba(15, 118, 110, 0.12);
        color: var(--primary-strong);
        border-color: rgba(15, 118, 110, 0.28);
    }

    .empty-order {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
        background: var(--panel);
        border-radius: 18px;
        border: 1px dashed var(--border);
    }

    .empty-icon {
        font-size: 52px;
        margin-bottom: 16px;
        color: rgba(15, 118, 110, 0.25);
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        padding: 8px 0 4px;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid var(--border-soft);
        color: var(--primary-strong);
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .pagination a:hover {
        background: rgba(15, 118, 110, 0.12);
        border-color: rgba(15, 118, 110, 0.3);
    }

    .pagination .current {
        background: var(--primary);
        color: #ffffff;
        border-color: var(--primary);
    }

    @media screen and (max-width: 768px) {
        .orders-hero {
            padding: 18px 18px;
        }

        .orders-title {
            font-size: 22px;
        }

        .order-body {
            flex-direction: column;
            align-items: flex-start;
        }

        .order-meta {
            width: 100%;
            text-align: left;
        }

        .order-actions {
            width: 100%;
        }

        .action-btn {
            flex: 1;
            text-align: center;
        }
    }
</style>

<div class="main-content">
    <?php $orderCount = isset($order_count) ? (int)$order_count : 0; ?>

    <div class="orders-page">
        <div class="order-list" id="orderContainer">
            <?php if (!empty($orders)): ?>
                <?php foreach($orders as $val): ?>
                    <div class="order-card" data-order-id="<?= $val['out_trade_no'] ?>">
                        <div class="order-head">
                            <div>
                                <div class="order-label">订单编号</div>
                                <div class="order-no"><?= $val['out_trade_no'] ?></div>
                            </div>
                            <div class="order-status <?= $val['status'] == 0 ? 'status-pending' : ($val['status'] == 1 ? 'status-delivering' : ($val['status'] == 2 ? 'status-completed' : 'status-cancelled')) ?>">
                                <?= $val['status_text'] ?>
                            </div>
                        </div>
                        <div class="order-body">
                            <a href="<?=  $val['url'] ?>" target="_blank">
                                <img src="<?= $val['cover'] ?>" class="goods-img" alt="<?= $val['title'] ?>">
                            </a>

                            <div class="goods-info">
                                <div class="goods-name"><a target="_blank" href="<?=  $val['url'] ?>"><?= $val['title'] ?></a></div>
                                <div class="goods-spec"><?= $val['attr_spec'] ?></div>
                                <div class="goods-spec"><?= $val['attach_user_text'] ?></div>
                            </div>

                            <div class="order-meta">
                                <?php if(!empty($val['pay_time'])): ?>
                                    <div class="meta-item">
                                        <span class="meta-label">付款时间</span>
                                        <span class="meta-value"><?= $val['pay_time_text'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="meta-item">
                                    <span class="meta-label">商品数量</span>
                                    <span class="meta-value"><?= $val['quantity'] ?> 件</span>
                                </div>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div class="order-total">
                                <span class="order-total-label">合计金额</span>
                                <span class="order-total-value">￥<?= $val['amount'] ?></span>
                            </div>
                            <div class="order-actions">
                                <?php if($val['status'] == 0): ?>
                                    <a href="javascript:;" class="action-btn danger" onclick="return cancelVisitorOrder('<?= htmlspecialchars($val['out_trade_no'], ENT_QUOTES) ?>');">取消订单</a>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($val['detail_url'] ?? ('visitors.php?action=visitors_order&order_id=' . (int)$val['id'])) ?>" class="action-btn">订单详情</a>
                                <?php if($val['status'] == 0): ?>
<!--                                    <a href="../pay.php?out_trade_no=--><?php //= $val['out_trade_no'] ?><!--" class="action-btn primary">立即支付</a>-->
                                <?php endif; ?>
                                <?php if($val['status'] == 2): ?>
<!--                                    <a href="order.php?action=download&order_list_id=--><?php //= $val['out_trade_no'] ?><!--" class="action-btn danger">下载卡密</a>-->
                                <?php endif; ?>

                                <?php
                                $buttons = [];
                                doMultiAction('user_order_list_btn', [
                                    'order' => $val,
                                    'child_order' => $val
                                ], $buttons);

                                if (!empty($buttons)) {
                                    foreach ($buttons as $btn) {
                                        if (empty($btn['text']) || empty($btn['url'])) continue;
                                        $class = $btn['class'] ?? 'action-btn';
                                        $target = !empty($btn['target']) ? ' target="' . $btn['target'] . '"' : '';
                                        $onclick = !empty($btn['onclick']) ? ' onclick="' . htmlspecialchars($btn['onclick']) . '"' : '';
                                        echo '<a href="' . $btn['url'] . '" class="' . $class . '"' . $target . $onclick . '>' . $btn['text'] . '</a>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-order">
                    <div class="empty-icon">📋</div>
                    <h3>暂无订单</h3>
                    <p>未查询到匹配的订单记录</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($pageurl)): ?>
        <div class="pagination">
            <?= $pageurl ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function cancelVisitorOrder(outTradeNo) {
        var search = new URLSearchParams(window.location.search);
        var payload = { out_trade_no: outTradeNo };
        if (search.get('contact')) {
            payload.contact = search.get('contact');
        }
        if (search.get('password')) {
            payload.password = search.get('password');
        }

        if (!window.confirm('确认取消当前订单吗？')) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: '<?= EM_URL ?>/user/visitors.php?action=cancel',
            data: payload,
            dataType: 'json',
            success: function (e) {
                if (e.code == 200) {
                    if (window.layui && layui.layer) {
                        layui.layer.msg('订单已取消', {time: 1200}, function () {
                            window.location.reload();
                        });
                    } else {
                        alert('订单已取消');
                        window.location.reload();
                    }
                } else {
                    if (window.layui && layui.layer) {
                        layui.layer.msg(e.msg || '取消失败');
                    } else {
                        alert(e.msg || '取消失败');
                    }
                }
            },
            error: function () {
                if (window.layui && layui.layer) {
                    layui.layer.msg('取消失败，请稍后重试');
                } else {
                    alert('取消失败，请稍后重试');
                }
            }
        });

        return false;
    }
</script>

<script>
    $(function () {
        $('.pay-btn').click(function(){
            let key = $(this).data('key');
            let pay_plugin = $(this).data('pay-plugin');
            let pay_name = $(this).data('pay-name');
            $('#pay_plugin-' + key).val(pay_plugin);
            $('#pay_name-' + key).val(pay_name);
            $('#payment-' + key).val(pay_name);
            $('.go-pay-' + key).click();
        })
    });
</script>

<script>
    $('#menu-order').addClass('open');
    $('#menu-order > ul').css('display', 'block');
    $('#menu-order > a > i.nav_right').attr('class', 'fa fa-angle-down nav_right');
    $('#menu-order-visitors').addClass('menu-current');
</script>
