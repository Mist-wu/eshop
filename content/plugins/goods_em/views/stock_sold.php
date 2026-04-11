<?php
/**
 * EMSHOP 同系统对接 - 发货记录（已售）
 */

defined('EM_ROOT') || exit('access denied!');

$db = Database::getInstance();
$goodsId = Input::getIntVar('id');
$page = Input::getIntVar('page', 1);
$pageSize = 20;

// 获取商品信息
$goodsRow = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "goods WHERE id = " . (int)$goodsId);
if (!$goodsRow) {
    echo '<script>layer.msg("商品不存在");history.back();</script>';
    exit;
}

// 获取发货记录总数
$countSql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "stock_usage su
             INNER JOIN " . DB_PREFIX . "order_list ol ON su.order_list_id = ol.id
             WHERE ol.goods_id = {$goodsId} AND su.stock_id = 0";
$countRow = $db->once_fetch_array($countSql);
$total = $countRow['total'] ?? 0;
$totalPages = ceil($total / $pageSize);

// 获取发货记录
$offset = ($page - 1) * $pageSize;
$sql = "SELECT su.*, ol.quantity, ol.order_id, o.order_no, o.out_trade_no
        FROM " . DB_PREFIX . "stock_usage su
        INNER JOIN " . DB_PREFIX . "order_list ol ON su.order_list_id = ol.id
        INNER JOIN " . DB_PREFIX . "order o ON su.order_id = o.id
        WHERE ol.goods_id = {$goodsId} AND su.stock_id = 0
        ORDER BY su.id DESC
        LIMIT {$offset}, {$pageSize}";
$records = $db->fetch_all($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>发货记录 - <?php echo htmlspecialchars($goodsRow['title']); ?></title>
    <link rel="stylesheet" href="<?php echo LAYUI_PATH; ?>css/layui.css">
</head>
<body>
<div class="layui-fluid" style="padding: 15px;">
    <div class="layui-card">
        <div class="layui-card-header">
            发货记录 - <?php echo htmlspecialchars($goodsRow['title']); ?>
            <span class="layui-badge layui-bg-blue" style="margin-left: 10px;">共 <?php echo $total; ?> 条</span>
        </div>
        <div class="layui-card-body">
            <table class="layui-table" lay-skin="line">
                <colgroup>
                    <col width="80">
                    <col width="180">
                    <col>
                    <col width="180">
                </colgroup>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>订单号</th>
                        <th>发货内容</th>
                        <th>发货时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:#999;">暂无发货记录</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo $record['id']; ?></td>
                        <td>
                            <a href="order.php?action=detail&out_trade_no=<?php echo $record['out_trade_no']; ?>" target="_blank">
                                <?php echo $record['order_no']; ?>
                            </a>
                        </td>
                        <td>
                            <div style="max-width: 400px; word-break: break-all;">
                                <?php echo htmlspecialchars(mb_substr($record['content'], 0, 100)); ?>
                                <?php if (mb_strlen($record['content']) > 100): ?>...<?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', $record['create_time']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
            <div id="pagination"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?php echo LAYUI_PATH; ?>layui.js"></script>
<script>
layui.use(['laypage'], function(){
    var laypage = layui.laypage;

    <?php if ($totalPages > 1): ?>
    laypage.render({
        elem: 'pagination',
        count: <?php echo $total; ?>,
        curr: <?php echo $page; ?>,
        limit: <?php echo $pageSize; ?>,
        jump: function(obj, first){
            if(!first){
                location.href = '?id=<?php echo $goodsId; ?>&ws=1&page=' + obj.curr;
            }
        }
    });
    <?php endif; ?>
});
</script>
</body>
</html>
