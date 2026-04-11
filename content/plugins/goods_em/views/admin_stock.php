<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    .em-stock-sold {
        padding: 24px;
        background: #f8fafb;
    }
    .em-stock-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .em-stock-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .em-stock-title::before {
        content: '';
        width: 4px;
        height: 16px;
        border-radius: 2px;
        background: linear-gradient(180deg, #0f766e, #14b8a6);
    }
    .em-stock-total {
        color: #6b7280;
        font-size: 12px;
    }
    .em-search {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .em-search .layui-input {
        width: 220px;
        border-radius: 8px;
    }
    .em-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .layui-table {
        margin: 0;
    }
    .layui-table th {
        background: #f8fafc;
        color: #374151;
        font-weight: 600;
    }
    .layui-table td {
        color: #4b5563;
    }
    .content-cell {
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #9ca3af;
    }
    .layui-laypage {
        text-align: center;
        margin-top: 18px;
    }
    @media (max-width: 768px) {
        .em-stock-sold { padding: 16px; }
        .em-search .layui-input { width: 160px; }
    }
</style>

<div class="em-stock-sold" id="open-box">
    <div class="em-stock-header">
        <div>
            <div class="em-stock-title">已售列表</div>
            <div class="em-stock-total">共 <?= (int)$total ?> 条</div>
        </div>
        <form class="layui-form em-search" method="get" action="stock.php">
            <input type="hidden" name="action" value="index">
            <input type="hidden" name="goods_id" value="<?= (int)$goods['id'] ?>">
            <input type="text" name="keyword" placeholder="搜索订单号/内容" class="layui-input" value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="layui-btn layui-btn-sm">搜索</button>
        </form>
    </div>

    <div class="em-table-wrap">
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
                        <div class="empty-state">暂无发货记录</div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $record): ?>
                <?php
                    $content = (string)($record['content'] ?? '');
                    $skuText = '默认规格';
                    if (!empty($record['sku']) && $record['sku'] !== '0') {
                        if (function_exists('emFormatSkuOptionIds')) {
                            $skuText = rtrim(emFormatSkuOptionIds($goods['id'], $record['sku']), '；');
                        } else {
                            $skuText = (string)$record['sku'];
                        }
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
                    <td class="content-cell"><?= htmlspecialchars($content) ?></td>
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
    <div id="pagination"></div>
    <?php endif; ?>
</div>

<script>
layui.use(['layer', 'laypage'], function(){
    var layer = layui.layer;
    var laypage = layui.laypage;

    window.showContent = function(content) {
        layer.open({
            title: '发货内容',
            content: '<div style="padding: 10px; word-break: break-all; white-space: pre-wrap;">' + (content || '') + '</div>',
            area: ['420px', '240px'],
            shadeClose: true
        });
    };

    <?php if ($total > $pageSize): ?>
    laypage.render({
        elem: 'pagination',
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
