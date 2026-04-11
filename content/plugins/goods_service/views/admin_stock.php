<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    html, body {
        height: auto;
        overflow: auto;
    }

    .service-stock {
        padding: 24px;
        background: #f8fafb;
    }

    .stock-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #e5e8eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .stat-card.total {
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        border: none;
        color: #fff;
    }

    .stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stat-card.total .stat-label,
    .stat-card.total .stat-value,
    .stat-card.total .stat-sub {
        color: #fff;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
        line-height: 1.2;
    }

    .stat-sub {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }

    .stock-note {
        background: #ecfeff;
        border: 1px solid #a5f3fc;
        color: #0e7490;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 18px;
        font-size: 13px;
        line-height: 1.6;
    }

    .sku-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .sku-table th,
    .sku-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #eef2f5;
        font-size: 13px;
        text-align: left;
    }

    .sku-table th {
        background: #f8fafc;
        color: #374151;
        font-weight: 600;
    }

    .sku-table tr:last-child td {
        border-bottom: none;
    }

    .sku-table .num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .stock-input {
        width: 110px;
        padding: 6px 8px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        text-align: right;
        font-size: 13px;
    }

    .stock-actions {
        margin-top: 14px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .stock-btn {
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }

    .stock-btn.primary {
        background: #0f766e;
        color: #fff;
    }

    .stock-btn.secondary {
        background: #f3f4f6;
        color: #374151;
    }

    @media (max-width: 768px) {
        .service-stock {
            padding: 16px;
        }
    }

    .service-sold {
        margin-top: 24px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e8eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .service-sold-header {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        background: #fafbfc;
    }
    .service-sold-title {
        font-weight: 600;
        color: #1f2937;
    }
    .service-sold-total {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .service-sold-search {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .service-sold-search .layui-input {
        width: 220px;
        border-radius: 8px;
    }
    .service-sold-table .layui-table {
        margin: 0;
    }
    .service-sold-table .layui-table th {
        background: #f8fafc;
        color: #374151;
        font-weight: 600;
    }
    .service-sold-table .layui-table td {
        color: #4b5563;
    }
    .service-sold-content {
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .service-empty {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }
    .service-sold-page {
        text-align: center;
        padding: 16px 0;
    }
    @media (max-width: 768px) {
        .service-sold-search .layui-input {
            width: 160px;
        }
    }
</style>

<div class="service-stock" id="open-box">
    <div class="layui-tab layui-tab-brief" lay-filter="serviceStockTab">
        <ul class="layui-tab-title">
            <li class="layui-this" lay-id="stock">库存配置</li>
            <li lay-id="sold">已售数据</li>
        </ul>
        <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
                <div class="stock-stats">
                    <div class="stat-card total">
                        <div class="stat-label"><i class="fa fa-cubes"></i> 总库存</div>
                        <div class="stat-value"><?= $total_stock ?></div>
                        <div class="stat-sub">已售出 <?= $total_sales ?> 件</div>
                    </div>
                    <?php foreach($sku_stock_stats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-label"><i class="fa fa-tag"></i> <?= htmlspecialchars($stat['sku_name']) ?></div>
                            <div class="stat-value"><?= (int)$stat['stock'] ?></div>
                            <div class="stat-sub">销量 <?= (int)$stat['sales'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="stock-note">
                    你可以在此直接修改人工发货的库存数量（写入规格库存）。
                    保存后立即生效。
                </div>

                <table class="sku-table">
                    <thead>
                        <tr>
                            <th>规格</th>
                            <th class="num">库存</th>
                            <th class="num">销量</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sku_stock_stats as $stat): ?>
                            <tr>
                                <td><?= htmlspecialchars($stat['sku_name']) ?></td>
                                <td class="num">
                                    <input class="stock-input" type="number" min="0" data-sku-id="<?= (int)$stat['sku_id'] ?>" value="<?= (int)$stat['stock'] ?>">
                                </td>
                                <td class="num"><?= (int)$stat['sales'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="stock-actions">
                    <button class="stock-btn secondary" type="button" id="resetStockBtn">重置</button>
                    <button class="stock-btn primary" type="button" id="saveStockBtn">保存库存</button>
                </div>
            </div>
            <div class="layui-tab-item">
                <div class="service-sold">
                    <div class="service-sold-header">
                        <div>
                            <div class="service-sold-title">已售列表</div>
                            <div class="service-sold-total">共 <?= (int)$total ?> 条</div>
                        </div>
                        <form class="layui-form service-sold-search" method="get" action="stock.php">
                            <input type="hidden" name="action" value="index">
                            <input type="hidden" name="goods_id" value="<?= (int)$goods['id'] ?>">
                            <input type="hidden" name="tab" value="sold">
                            <input type="text" name="keyword" placeholder="搜索订单号/内容" class="layui-input" value="<?= htmlspecialchars($keyword) ?>">
                            <button type="submit" class="layui-btn layui-btn-sm">搜索</button>
                        </form>
                    </div>
                    <div class="service-sold-table">
                        <table class="layui-table" lay-skin="line">
                            <colgroup>
                                <col width="80">
                                <col width="180">
                                <col width="160">
                                <col>
                                <col width="140">
                                <col width="170">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>订单号</th>
                                    <th>规格</th>
                                    <th>发货内容</th>
                                    <th>操作</th>
                                    <th>发货时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="service-empty">暂无发货记录</div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                <?php
                                    $content = (string)($record['content'] ?? '');
                                    $skuText = '默认规格';
                                    if (!empty($record['sku']) && $record['sku'] !== '0') {
                                        $skuText = function_exists('getSkuName') ? getSkuName($record['sku']) : (string)$record['sku'];
                                    }
                                ?>
                                <tr>
                                    <td><?= (int)$record['id'] ?></td>
                                    <td>
                                        <a href="order.php?action=detail&out_trade_no=<?= urlencode($record['out_trade_no'] ?? '') ?>" target="_blank">
                                            <?= htmlspecialchars($record['order_no'] ?? '-') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($skuText) ?></td>
                                    <td class="service-sold-content"><?= htmlspecialchars($content) ?></td>
                                    <td>
                                        <?php if ($content !== ''): ?>
                                        <button type="button" class="layui-btn layui-btn-xs" onclick="showContent(<?= json_encode($content, JSON_UNESCAPED_UNICODE) ?>)">查看</button>
                                        <?php else: ?>
                                        <span class="layui-badge layui-bg-gray">待发货</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($record['create_time']) ? date('Y-m-d H:i:s', $record['create_time']) : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total > $pageSize): ?>
                    <div class="service-sold-page" id="sold-pagination"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['layer', 'laypage', 'element'], function() {
    var layer = layui.layer;
    var laypage = layui.laypage;
    var element = layui.element;
    var $ = layui.$;
    var goodsId = <?= (int)$goods['id'] ?>;
    var token = '<?= LoginAuth::genToken() ?>';

    var origin = {};
    $('.stock-input').each(function() {
        var skuId = $(this).data('sku-id');
        origin[skuId] = $(this).val();
    });

    $('#resetStockBtn').on('click', function() {
        $('.stock-input').each(function() {
            var skuId = $(this).data('sku-id');
            if (origin.hasOwnProperty(skuId)) {
                $(this).val(origin[skuId]);
            }
        });
    });

    $('#saveStockBtn').on('click', function() {
        var btn = $(this);
        var stockData = {};
        $('.stock-input').each(function() {
            var skuId = $(this).data('sku-id');
            var val = parseInt($(this).val(), 10);
            if (isNaN(val) || val < 0) {
                val = 0;
            }
            stockData[skuId] = val;
        });

        btn.prop('disabled', true).text('保存中...');
        $.ajax({
            url: '<?= PLUGIN_URL ?>goods_service/api.php?action=update_stock',
            type: 'POST',
            dataType: 'json',
            data: {
                goods_id: goodsId,
                stock: stockData,
                token: token
            },
            success: function(res) {
                btn.prop('disabled', false).text('保存库存');
                if (res.code === 0) {
                    layer.msg('库存已更新');
                    for (var key in stockData) {
                        origin[key] = stockData[key];
                    }
                } else {
                    layer.msg(res.msg || '保存失败');
                }
            },
            error: function() {
                btn.prop('disabled', false).text('保存库存');
                layer.msg('请求失败');
            }
        });
    });

    window.showContent = function(content) {
        layer.open({
            title: '发货内容',
            content: '<div style="padding: 10px; word-break: break-all; white-space: pre-wrap;">' + (content || '') + '</div>',
            area: ['420px', '240px'],
            shadeClose: true
        });
    };

    var activeTab = '<?= $tab ?>';
    if (activeTab === 'sold') {
        element.tabChange('serviceStockTab', 'sold');
    }

    <?php if ($total > $pageSize): ?>
    laypage.render({
        elem: 'sold-pagination',
        count: <?= (int)$total ?>,
        curr: <?= (int)$page ?>,
        limit: <?= (int)$pageSize ?>,
        layout: ['prev', 'page', 'next', 'skip'],
        jump: function(obj, first){
            if(!first){
                location.href = '<?= $baseUrl ?>' + '&page=' + obj.curr;
            }
        }
    });
    <?php endif; ?>
});
</script>
