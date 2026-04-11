<?php
/**
 * EMSHOP 同系统对接 - 后台发货视图
 */

defined('EM_ROOT') || exit('access denied!');

// 获取发货记录
$secrets = emGetOrderSecrets($child_order['id']);

// 获取对接信息
$emGoods = $db->once_fetch_array("SELECT * FROM " . $db_prefix . "em_goods WHERE goods_id = " . (int)$goods['id']);
$site = $emGoods ? emGetSite($emGoods['site_id']) : null;
?>
<div class="layui-card" style="margin: 15px;">
    <div class="layui-card-header">
        订单发货 - <?php echo htmlspecialchars($goods['title']); ?>
    </div>
    <div class="layui-card-body">
        <div class="layui-row layui-col-space15" style="margin-bottom: 20px;">
            <div class="layui-col-md6">
                <table class="layui-table" lay-skin="nob">
                    <colgroup>
                        <col width="120">
                        <col>
                    </colgroup>
                    <tbody>
                        <tr>
                            <td><strong>订单号：</strong></td>
                            <?php $displayNo = $order['out_trade_no'] ?? ($order['order_no'] ?? '-'); ?>
                            <td><?php echo htmlspecialchars($displayNo); ?></td>
                        </tr>
                        <tr>
                            <td><strong>商品类型：</strong></td>
                            <td>
                                <?php echo $goods['type'] == 'em_auto' ? 'EM对接(自动发货)' : 'EM对接(人工发货)'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>购买数量：</strong></td>
                            <td><?php echo $child_order['quantity']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>订单状态：</strong></td>
                            <td>
                                <?php
                                switch ($child_order['status']) {
                                    case 2:
                                        echo '<span class="layui-badge layui-bg-green">已发货</span>';
                                        break;
                                    case 1:
                                        echo '<span class="layui-badge layui-bg-orange">待发货</span>';
                                        break;
                                    default:
                                        echo '<span class="layui-badge layui-bg-gray">未支付</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="layui-col-md6">
                <table class="layui-table" lay-skin="nob">
                    <colgroup>
                        <col width="120">
                        <col>
                    </colgroup>
                    <tbody>
                        <tr>
                            <td><strong>对接站点：</strong></td>
                            <td><?php echo $site ? htmlspecialchars($site['sitename']) : '<span style="color:red;">站点不存在</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>远程商品ID：</strong></td>
                            <td><?php echo $emGoods ? $emGoods['remote_goods_id'] : '-'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>远程订单号：</strong></td>
                            <td><?php echo $child_order['remote_trade_no'] ?: '-'; ?></td>
                        </tr>
                        <?php if ($site): ?>
                        <tr>
                            <td><strong>站点余额：</strong></td>
                            <td><span class="layui-badge layui-bg-green"><?php echo number_format($site['balance'], 2); ?> 元</span></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($secrets)): ?>
        <fieldset class="layui-elem-field layui-field-title">
            <legend>发货内容</legend>
        </fieldset>
        <table class="layui-table" lay-skin="line">
            <colgroup>
                <col width="80">
                <col>
                <col width="180">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>卡密内容</th>
                    <th>发货时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secrets as $index => $secret): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td style="word-break: break-all;"><?php echo nl2br(htmlspecialchars($secret['content'])); ?></td>
                    <td><?php echo date('Y-m-d H:i:s', $secret['create_time']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif ($child_order['status'] == 1): ?>
        <div style="text-align: center; padding: 40px; background: #fffbe6; border-radius: 5px;">
            <p style="font-size: 16px; color: #faad14;">订单待发货</p>
            <p style="color: #999;">
                <?php if ($goods['type'] == 'em_manual'): ?>
                人工发货商品需要上游商家处理
                <?php else: ?>
                自动发货失败，请检查上游库存和余额
                <?php endif; ?>
            </p>

            <?php if ($child_order['remote_trade_no']): ?>
            <div style="margin-top: 20px;">
                <button class="layui-btn layui-btn-normal" id="queryRemoteBtn"
                        data-order-list-id="<?php echo $child_order['id']; ?>">
                    查询上游订单状态
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
layui.use(['layer', 'jquery'], function(){
    var layer = layui.layer;
    var $ = layui.$;
    var apiBase = '<?= EM_URL ?>?plugin=goods_em';

    $('#queryRemoteBtn').click(function(){
        var orderListId = $(this).data('order-list-id');
        var loadIndex = layer.load(1);

        $.post(apiBase, {action: 'query_order', order_list_id: orderListId}, function(res){
            layer.close(loadIndex);
            if(res.code === 0){
                layer.msg('查询成功', {icon: 1});
                if(res.data && res.data.status == 2){
                    setTimeout(function(){
                        location.reload();
                    }, 1000);
                }
            } else {
                layer.msg(res.msg || '查询失败', {icon: 2});
            }
        }, 'json');
    });
});
</script>
