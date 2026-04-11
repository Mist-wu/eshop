<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .main-content {
        max-width: 100% !important;
        padding: 0 20px;
    }

    .visitors-page {
        display: grid;
        gap: 18px;
        max-width: 800px;
        margin: 0 auto;
        width: 100%;
    }

    .visitors-hero {
        background: var(--panel);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-soft);
        padding: 22px 24px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
    }

    .hero-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--primary-strong);
    }

    .hero-title {
        margin: 8px 0 6px;
        font-size: 26px;
        font-weight: 700;
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
        color: var(--text);
    }

    .hero-desc {
        margin: 0;
        font-size: 14px;
        color: var(--muted);
    }

    .query-card {
        background: var(--panel);
        border-radius: 20px;
        border: 1px solid var(--border-soft);
        padding: 24px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
    }

    .query-mode-tabs {
        display: flex;
        gap: 12px;
        padding: 4px;
        background: var(--panel-soft);
        border-radius: 12px;
    }

    .mode-tab {
        flex: 1;
        padding: 10px 16px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--muted);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .mode-tab:hover {
        color: var(--text);
        background: rgba(15, 118, 110, 0.05);
    }

    .mode-tab.active {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.25);
    }

    .query-form-section {
        display: none;
    }

    .query-form-section.active {
        display: block;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
    }

    .form-control {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(15, 118, 110, 0.2);
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        outline: none;
    }

    .form-hint {
        margin-top: 6px;
        font-size: 12px;
        color: var(--muted);
    }

    .form-actions {
        margin-top: 18px;
        display: flex;
        justify-content: flex-end;
    }

    .query-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--primary-strong));
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .query-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 118, 110, 0.25);
    }

    .tips-card {
        background: var(--panel-soft);
        border-radius: 18px;
        border: 1px dashed rgba(15, 118, 110, 0.25);
        padding: 18px 20px;
    }

    .tips-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 12px;
    }

    .tips-list {
        display: grid;
        gap: 8px;
        color: var(--muted);
        font-size: 13px;
        padding-left: 18px;
    }

    .tips-list li {
        list-style: disc;
    }

    @media (max-width: 768px) {
        .visitors-hero,
        .query-card {
            padding: 18px 18px;
        }

        .hero-title {
            font-size: 22px;
        }

        .form-actions {
            justify-content: stretch;
        }

        .query-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* 本地订单列表样式 */
    .local-orders-section {
        display: none;
    }

    .local-orders-section.active {
        display: block;
    }

    #local-orders-container {
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
        margin-bottom: 16px;
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
        text-decoration: none;
        display: inline-block;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    .loading-more {
        text-align: center;
        padding: 16px;
        color: var(--muted);
        font-size: 13px;
    }

    .no-more-data {
        text-align: center;
        padding: 16px;
        color: var(--muted);
        font-size: 13px;
    }

    .empty-local-orders {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
        background: var(--panel);
        border-radius: 18px;
        border: 1px dashed var(--border);
    }

    .empty-local-orders i {
        font-size: 52px;
        margin-bottom: 16px;
        color: rgba(15, 118, 110, 0.25);
    }

    @media screen and (max-width: 768px) {
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

<main class="main-content">
    <div class="visitors-page">
        <section class="visitors-hero">
            <div class="hero-label"><i class="fa fa-search"></i>游客查单</div>
            <h1 class="hero-title">查询游客订单</h1>
            <p class="hero-desc">请输入订单编号及下单时填写的信息，快速查询订单状态。</p>
        </section>

        <section class="query-card">
            <div class="query-mode-tabs">
                <button class="mode-tab active" data-mode="local">
                    <i class="fa fa-history"></i> 浏览器缓存订单
                </button>
                <button class="mode-tab" data-mode="info">
                    <i class="fa fa-user"></i> 通过下单信息查询
                </button>
                <button class="mode-tab" data-mode="order">
                    <i class="fa fa-hashtag"></i> 通过订单号查询
                </button>
            </div>

            <!-- 通过下单信息查询 -->
            <form class="layui-form query-form-section" id="form-info" data-mode="info">
                <div class="query-form">
                    <?php if(!empty($visitor_required)): ?>
                        <?php if(isset($visitor_required['contact'])): ?>
                        <div class="form-group">
                            <label><?= $visitor_required['contact']['title'] ?></label>
                            <input type="text" name="contact" placeholder="<?= $visitor_required['contact']['placeholder_query'] ?>" class="form-control layui-input" required>
                            <p class="form-hint">仅适用于下单时填写过<?= $visitor_required['contact']['title'] ?>的订单</p>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($visitor_required['password'])): ?>
                        <div class="form-group">
                            <label><?= $visitor_required['password']['title'] ?></label>
                            <input type="text" name="password" placeholder="<?= $visitor_required['password']['placeholder_query'] ?>" class="form-control layui-input" required>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="form-group">
                            <div style="padding: 16px; background: #fef3c7; border-radius: 8px; color: #92400e; text-align: center;">
                                <i class="fa fa-exclamation-triangle"></i> 管理员未配置游客查单信息，请使用订单号查询
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
                <div class="form-actions">
                    <button type="submit" lay-submit lay-filter="submit-info" class="query-btn" <?= empty($visitor_required) ? 'disabled' : '' ?>>
                        <i class="fa fa-search" aria-hidden="true"></i> 立即查询
                    </button>
                </div>
            </form>

            <!-- 通过订单号查询 -->
            <form class="layui-form query-form-section" id="form-order" data-mode="order">
                <div class="query-form">
                    <div class="form-group">
                        <label for="orderNumber">订单编号</label>
                        <input type="text" name="order_no" placeholder="请输入站内订单号或支付订单号" class="form-control layui-input" required>
                        <p class="form-hint">支持站内订单号和支付平台订单号；如该订单下单时未填写联系方式，请优先使用此方式</p>
                    </div>
                </div>
                <input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
                <div class="form-actions">
                    <button type="submit" lay-submit lay-filter="submit-order" class="query-btn">
                        <i class="fa fa-search" aria-hidden="true"></i> 立即查询
                    </button>
                </div>
            </form>
        </section>

        <!-- 浏览器缓存订单列表 -->
        <section id="section-local" class="local-orders-section" data-mode="local">
            <div id="local-orders-container">
                <div class="loading-placeholder" style="text-align: center; padding: 40px 20px; color: var(--muted);">
                    <i class="fa fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 12px;"></i>
                    <p>正在加载订单...</p>
                </div>
            </div>
        </section>

        <section class="tips-card">
            <div class="tips-title">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                查询说明
            </div>
            <ul class="tips-list">
                <li>如查询不到订单，请检查输入信息是否正确或联系客服咨询。</li>
                <li>订单支付后若未到账，请稍等片刻后再次查询。</li>
            </ul>
        </section>
    </div>
</main>

<script>
    layui.use(['form'], function(){
        var $ = layui.$;
        var form = layui.form;

        // 浏览器缓存订单相关变量
        var localOrdersPage = 1;
        var localOrdersLoading = false;
        var localOrdersHasMore = true;
        var localOrdersInitialized = false;

        // 搜索订单相关变量
        var searchMode = 'local'; // local, info
        var searchParams = {}; // 保存搜索参数
        var searchPage = 1;
        var searchLoading = false;
        var searchHasMore = true;

        function escapeHtmlAttr(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function buildVisitorOrderActions(order) {
            var html = '';
            var detailUrl = order.detail_url ? order.detail_url : ('visitors.php?action=visitors_order&order_id=' + order.id);
            if (order.status == 0) {
                html += '<a href="javascript:;" class="action-btn danger js-cancel-visitor-order" data-out-trade-no="' + escapeHtmlAttr(order.out_trade_no) + '">取消订单</a>';
            }
            html += '<a href="' + detailUrl + '" class="action-btn">订单详情</a>';
            return html;
        }

        function refreshVisitorOrders() {
            if (searchMode === 'local') {
                localOrdersPage = 1;
                localOrdersHasMore = true;
                loadLocalOrders(1, false);
                return;
            }

            if (searchMode === 'info') {
                searchPage = 1;
                searchHasMore = true;
                $.ajax({
                    type: "POST",
                    url: "<?= EM_URL ?>/user/visitors.php?action=visitors_search_by_info",
                    data: Object.assign({}, searchParams, {page: 1}),
                    dataType: "json",
                    success: function (e) {
                        if (e.code == 200 && e.data && e.data.list) {
                            renderOrders(e.data.list);
                            searchHasMore = e.data.hasMore;
                        }
                    }
                });
                return;
            }

            if (searchMode === 'order') {
                $.ajax({
                    type: "POST",
                    url: "<?= EM_URL ?>/user/visitors.php?action=visitors_search_order",
                    data: searchParams,
                    dataType: "json",
                    success: function (e) {
                        if (e.code == 200 && e.data && e.data.list) {
                            renderOrders(e.data.list);
                        } else {
                            renderOrders([]);
                        }
                    }
                });
            }
        }

        function cancelVisitorOrder(outTradeNo) {
            var payload = { out_trade_no: outTradeNo };
            if (searchMode === 'info' || searchMode === 'order') {
                payload = Object.assign({}, searchParams, payload);
            }

            layer.confirm('确认取消当前订单吗？', {icon: 3, title: '取消订单'}, function(index){
                layer.close(index);
                $.ajax({
                    type: "POST",
                    url: "<?= EM_URL ?>/user/visitors.php?action=cancel",
                    data: payload,
                    dataType: "json",
                    success: function (e) {
                        if (e.code == 200) {
                            layer.msg('订单已取消');
                            refreshVisitorOrders();
                        } else {
                            layer.msg(e.msg || '取消失败');
                        }
                    },
                    error: function () {
                        layer.msg('取消失败，请稍后重试');
                    }
                });
            });
            return false;
        }

        $(document).on('click', '.js-cancel-visitor-order', function () {
            return cancelVisitorOrder($(this).attr('data-out-trade-no'));
        });

        // 渲染订单列表的通用函数
        function renderOrders(orders) {
            $('#local-orders-container').html('');

            if (!orders || orders.length === 0) {
                $('#local-orders-container').html(
                    '<div class="empty-local-orders">' +
                    '<div style="font-size: 52px; margin-bottom: 16px;">📋</div>' +
                    '<h3>暂无订单</h3>' +
                    '<p>未查询到匹配的订单记录</p>' +
                    '</div>'
                );
                return;
            }

            orders.forEach(function(order) {
                var statusClass = '';
                if (order.status == 0) statusClass = 'status-pending';
                else if (order.status == 1) statusClass = 'status-delivering';
                else if (order.status == 2) statusClass = 'status-completed';
                else statusClass = 'status-cancelled';

                var orderHtml =
                    '<div class="order-card">' +
                    '<div class="order-head">' +
                    '<div>' +
                    '<div class="order-label">订单编号</div>' +
                    '<div class="order-no">' + order.out_trade_no + '</div>' +
                    '</div>' +
                    '<div class="order-status ' + statusClass + '">' + order.status_text + '</div>' +
                    '</div>' +
                    '<div class="order-body">' +
                    '<a href="' + order.url + '" target="_blank">' +
                    '<img src="' + order.cover + '" class="goods-img" alt="' + order.title + '">' +
                    '</a>' +
                    '<div class="goods-info">' +
                    '<div class="goods-name"><a target="_blank" href="' + order.url + '">' + order.title + '</a></div>' +
                    '<div class="goods-spec">' + order.attr_spec + '</div>' +
                    (order.attach_user_text ? '<div class="goods-spec">' + order.attach_user_text + '</div>' : '') +
                    '</div>' +
                    '<div class="order-meta">' +
                    (order.pay_time ? '<div class="meta-item"><span class="meta-label">付款时间</span><span class="meta-value">' + order.pay_time_text + '</span></div>' : '') +
                    '<div class="meta-item"><span class="meta-label">商品数量</span><span class="meta-value">' + order.quantity + ' 件</span></div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="order-footer">' +
                    '<div class="order-total">' +
                    '<span class="order-total-label">合计金额</span>' +
                    '<span class="order-total-value">¥' + order.amount + '</span>' +
                    '</div>' +
                    '<div class="order-actions">' +
                    buildVisitorOrderActions(order) +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $('#local-orders-container').append(orderHtml);
            });
        }

        // 获取本地标识
        function getLocalIdentifier() {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i].trim();
                if (cookie.indexOf('EM_LOCAL=') === 0) {
                    return cookie.substring('EM_LOCAL='.length);
                }
            }
            return null;
        }

        // 加载本地订单
        function loadLocalOrders(page, isLoadMore) {
            if (localOrdersLoading) return;

            var local = getLocalIdentifier();
            if (!local) {
                $('#local-orders-container').html(
                    '<div class="empty-local-orders">' +
                    '<div style="font-size: 52px; margin-bottom: 16px;">📋</div>' +
                    '<h3>暂无缓存订单</h3>' +
                    '<p>您还没有在此浏览器下过单</p>' +
                    '</div>'
                );
                return;
            }

            localOrdersLoading = true;

            if (isLoadMore) {
                $('#local-orders-container .loading-more').remove();
                $('#local-orders-container .no-more-data').remove();
                $('#local-orders-container').append(
                    '<div class="loading-more"><i class="fa fa-spinner fa-spin"></i> 加载中...</div>'
                );
            }

            $.ajax({
                type: "POST",
                url: "<?= EM_URL ?>/user/visitors.php?action=get_local_orders",
                data: {
                    local: local,
                    page: page
                },
                dataType: "json",
                success: function (e) {
                    localOrdersLoading = false;

                    if (e.code == 200) {
                        var orders = e.data.list;
                        var hasMore = e.data.hasMore;

                        if (isLoadMore) {
                            $('#local-orders-container .loading-more').remove();
                        }

                        if (orders.length === 0 && page === 1) {
                            $('#local-orders-container').html(
                                '<div class="empty-local-orders">' +
                                '<div style="font-size: 52px; margin-bottom: 16px;">📋</div>' +
                                '<h3>暂无缓存订单</h3>' +
                                '<p>您还没有在此浏览器下过单</p>' +
                                '</div>'
                            );
                            localOrdersHasMore = false;
                            return;
                        }

                        if (!isLoadMore) {
                            renderOrders(orders);
                        } else {
                            // 追加订单
                            orders.forEach(function(order) {
                                var statusClass = '';
                                if (order.status == 0) statusClass = 'status-pending';
                                else if (order.status == 1) statusClass = 'status-delivering';
                                else if (order.status == 2) statusClass = 'status-completed';
                                else statusClass = 'status-cancelled';

                                var orderHtml =
                                    '<div class="order-card">' +
                                    '<div class="order-head">' +
                                    '<div>' +
                                    '<div class="order-label">订单编号</div>' +
                                    '<div class="order-no">' + order.out_trade_no + '</div>' +
                                    '</div>' +
                                    '<div class="order-status ' + statusClass + '">' + order.status_text + '</div>' +
                                    '</div>' +
                                    '<div class="order-body">' +
                                    '<a href="' + order.url + '" target="_blank">' +
                                    '<img src="' + order.cover + '" class="goods-img" alt="' + order.title + '">' +
                                    '</a>' +
                                    '<div class="goods-info">' +
                                    '<div class="goods-name"><a target="_blank" href="' + order.url + '">' + order.title + '</a></div>' +
                                    '<div class="goods-spec">' + order.attr_spec + '</div>' +
                                    (order.attach_user_text ? '<div class="goods-spec">' + order.attach_user_text + '</div>' : '') +
                                    '</div>' +
                                    '<div class="order-meta">' +
                                    (order.pay_time ? '<div class="meta-item"><span class="meta-label">付款时间</span><span class="meta-value">' + order.pay_time_text + '</span></div>' : '') +
                                    '<div class="meta-item"><span class="meta-label">商品数量</span><span class="meta-value">' + order.quantity + ' 件</span></div>' +
                                    '</div>' +
                                    '</div>' +
                                    '<div class="order-footer">' +
                                    '<div class="order-total">' +
                                    '<span class="order-total-label">合计金额</span>' +
                                    '<span class="order-total-value">¥' + order.amount + '</span>' +
                                    '</div>' +
                                    '<div class="order-actions">' +
                                    buildVisitorOrderActions(order) +
                                    '</div>' +
                                    '</div>' +
                                    '</div>';

                                $('#local-orders-container').append(orderHtml);
                            });
                        }

                        localOrdersHasMore = hasMore;

                        if (!hasMore && orders.length > 0) {
                            $('#local-orders-container').append(
                                '<div class="no-more-data">没有更多订单了</div>'
                            );
                        }
                    } else {
                        if (isLoadMore) {
                            $('#local-orders-container .loading-more').remove();
                        }
                        layer.msg(e.msg || '加载失败');
                    }
                },
                error: function (xhr) {
                    localOrdersLoading = false;
                    if (isLoadMore) {
                        $('#local-orders-container .loading-more').remove();
                    }
                    layer.msg('加载失败，请稍后重试');
                }
            });
        }

        // 加载更多搜索结果
        function loadMoreSearchOrders() {
            if (searchLoading || !searchHasMore) return;

            searchLoading = true;
            searchPage++;

            $('#local-orders-container .loading-more').remove();
            $('#local-orders-container .no-more-data').remove();
            $('#local-orders-container').append(
                '<div class="loading-more"><i class="fa fa-spinner fa-spin"></i> 加载中...</div>'
            );

            $.ajax({
                type: "POST",
                url: "<?= EM_URL ?>/user/visitors.php?action=visitors_search_by_info",
                data: Object.assign({}, searchParams, {page: searchPage}),
                dataType: "json",
                success: function (e) {
                    searchLoading = false;
                    $('#local-orders-container .loading-more').remove();

                    if (e.code == 200 && e.data && e.data.list) {
                        var orders = e.data.list;
                        searchHasMore = e.data.hasMore;

                        // 追加订单
                        orders.forEach(function(order) {
                            var statusClass = '';
                            if (order.status == 0) statusClass = 'status-pending';
                            else if (order.status == 1) statusClass = 'status-delivering';
                            else if (order.status == 2) statusClass = 'status-completed';
                            else statusClass = 'status-cancelled';

                            var orderHtml =
                                '<div class="order-card">' +
                                '<div class="order-head">' +
                                '<div>' +
                                '<div class="order-label">订单编号</div>' +
                                '<div class="order-no">' + order.out_trade_no + '</div>' +
                                '</div>' +
                                '<div class="order-status ' + statusClass + '">' + order.status_text + '</div>' +
                                '</div>' +
                                '<div class="order-body">' +
                                '<a href="' + order.url + '" target="_blank">' +
                                '<img src="' + order.cover + '" class="goods-img" alt="' + order.title + '">' +
                                '</a>' +
                                '<div class="goods-info">' +
                                '<div class="goods-name"><a target="_blank" href="' + order.url + '">' + order.title + '</a></div>' +
                                '<div class="goods-spec">' + order.attr_spec + '</div>' +
                                (order.attach_user_text ? '<div class="goods-spec">' + order.attach_user_text + '</div>' : '') +
                                '</div>' +
                                '<div class="order-meta">' +
                                (order.pay_time ? '<div class="meta-item"><span class="meta-label">付款时间</span><span class="meta-value">' + order.pay_time_text + '</span></div>' : '') +
                                '<div class="meta-item"><span class="meta-label">商品数量</span><span class="meta-value">' + order.quantity + ' 件</span></div>' +
                                '</div>' +
                                '</div>' +
                                '<div class="order-footer">' +
                                '<div class="order-total">' +
                                '<span class="order-total-label">合计金额</span>' +
                                '<span class="order-total-value">¥' + order.amount + '</span>' +
                                '</div>' +
                                '<div class="order-actions">' +
                                buildVisitorOrderActions(order) +
                                '</div>' +
                                '</div>' +
                                '</div>';

                            $('#local-orders-container').append(orderHtml);
                        });

                        if (!searchHasMore && orders.length > 0) {
                            $('#local-orders-container').append(
                                '<div class="no-more-data">没有更多订单了</div>'
                            );
                        }
                    } else {
                        layer.msg('加载失败');
                    }
                },
                error: function (xhr) {
                    searchLoading = false;
                    $('#local-orders-container .loading-more').remove();
                    layer.msg('加载失败，请稍后重试');
                }
            });
        }

        // 滚动加载（监听 layui-tab-content 容器的滚动）
        var scrollTimer = null;
        $('.layui-tab-content').on('scroll', function() {
            // 防抖处理
            if (scrollTimer) {
                clearTimeout(scrollTimer);
            }

            scrollTimer = setTimeout(function() {
                // 只在本地订单模式下触发
                if (!$('#section-local').hasClass('active')) {
                    console.log('不在订单列表显示模式');
                    return;
                }

                var container = $('.layui-tab-content');
                var scrollTop = container.scrollTop();
                var scrollHeight = container[0].scrollHeight;
                var clientHeight = container.height();
                var distanceToBottom = scrollHeight - (scrollTop + clientHeight);

                console.log('滚动位置:', scrollTop, '容器高度:', clientHeight, '内容高度:', scrollHeight, '距离底部:', distanceToBottom);

                // 距离底部300px时触发加载
                if (distanceToBottom <= 300) {
                    // 根据当前模式加载数据
                    if (searchMode === 'local') {
                        // 浏览器缓存订单模式
                        if (localOrdersLoading || !localOrdersHasMore) {
                            console.log('本地订单：正在加载或没有更多数据');
                            return;
                        }
                        console.log('触发本地订单加载，当前页:', localOrdersPage);
                        localOrdersPage++;
                        loadLocalOrders(localOrdersPage, true);
                    } else if (searchMode === 'info') {
                        // 搜索订单模式
                        if (searchLoading || !searchHasMore) {
                            console.log('搜索订单：正在加载或没有更多数据');
                            return;
                        }
                        console.log('触发搜索订单加载，当前页:', searchPage);
                        loadMoreSearchOrders();
                    }
                }
            }, 100);
        });

        // 模式切换
        $('.mode-tab').on('click', function(){
            var mode = $(this).data('mode');
            $('.mode-tab').removeClass('active');
            $(this).addClass('active');
            $('.query-form-section').removeClass('active');
            $('.local-orders-section').removeClass('active');
            $('.query-form-section[data-mode="' + mode + '"]').addClass('active');

            // 切换到本地订单时
            if (mode === 'local') {
                searchMode = 'local';
                if (!localOrdersInitialized) {
                    localOrdersInitialized = true;
                    loadLocalOrders(1, false);
                }
                $('#section-local').addClass('active');
            } else {
                // 切换到查询表单时，隐藏订单列表
                $('#section-local').removeClass('active');
            }
        });

        // 页面加载时自动加载本地订单并显示
        $('#section-local').addClass('active');
        loadLocalOrders(1, false);
        localOrdersInitialized = true;

        // 通过下单信息查询
        form.on('submit(submit-info)', function(data){
            var field = data.field;
            console.log(field);

            // 保存搜索参数
            searchParams = field;
            searchMode = 'info';
            searchPage = 1;
            searchHasMore = true;

            var loadIndex = layer.load(2);
            $.ajax({
                type: "POST",
                url: "<?= EM_URL ?>/user/visitors.php?action=visitors_search_by_info",
                data: Object.assign({}, field, {page: 1}),
                dataType: "json",
                success: function (e) {
                    layer.close(loadIndex)
                    if(e.code == 200){
                        if(e.data && e.data.list && e.data.list.length > 0){
                            // 直接在页面渲染订单列表
                            renderOrders(e.data.list);
                            searchHasMore = e.data.hasMore;

                            // 切换到订单列表显示
                            $('.query-form-section').removeClass('active');
                            $('.local-orders-section').removeClass('active');
                            $('#section-local').addClass('active');
                        }else{
                            layer.msg('没有查找到匹配的订单');
                        }
                    }else{
                        layer.msg(e.msg)
                    }
                },
                error: function (xhr) {
                    layer.close(loadIndex)
                    layer.msg('出错啦~');
                }
            });
            return false;
        });

        // 通过订单号查询
        form.on('submit(submit-order)', function(data){
            var field = data.field;
            console.log(field);
            searchParams = field;
            var loadIndex = layer.load(2);
            $.ajax({
                type: "POST",
                url: "<?= EM_URL ?>/user/visitors.php?action=visitors_search_order",
                data: field,
                dataType: "json",
                success: function (e) {
                    layer.close(loadIndex)
                    if(e.code == 200){
                        if(e.data && e.data.list && e.data.list.length > 0){
                            // 直接在页面渲染订单列表
                            renderOrders(e.data.list);
                            searchMode = 'order';
                            searchHasMore = false; // 订单号查询不需要分页

                            // 切换到订单列表显示
                            $('.query-form-section').removeClass('active');
                            $('.local-orders-section').removeClass('active');
                            $('#section-local').addClass('active');
                        }else{
                            layer.msg('没有查找到匹配的订单');
                        }
                    }else{
                        layer.msg(e.msg)
                    }
                },
                error: function (xhr) {
                    layer.close(loadIndex)
                    layer.msg('出错啦~');
                }
            });
            return false;
        });

    })

</script>

<script>
    $('#menu-order').addClass('open');
    $('#menu-order > ul').css('display', 'block');
    $('#menu-order > a > i.nav_right').attr('class', 'fa fa-angle-down nav_right');
    $('#menu-order-visitors').addClass('menu-current');
</script>
