<?php
/**
 * EMSHOP 同系统对接 - 用户订单详情
 */

defined('EM_ROOT') || exit('access denied!');

// 获取发货记录
$secrets = emGetOrderSecrets($child_order['id']);

// 获取对接信息
$emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
$orderNo = $order['out_trade_no'] ?? '';
$orderStatus = (int)($order['status'] ?? $child_order['status'] ?? 0);
$merchantRemark = trim((string)($goods['pay_content'] ?? ''));
$isVisitorView = !empty($GLOBALS['EM_VISITOR_ORDER_VIEW']);
$canManageUnpaid = !empty($orderNo) && empty($order['pay_time']) && $orderStatus === 0;
$repayUrl = $canManageUnpaid ? EM_URL . '?action=pay&out_trade_no=' . rawurlencode($orderNo) : '';
$cancelUrl = (!$isVisitorView && $canManageUnpaid) ? EM_URL . 'user/order.php?action=cancel&out_trade_no=' . rawurlencode($orderNo) : '';
$backUrl = $isVisitorView ? EM_URL . 'user/visitors.php' : EM_URL . 'user/order.php';
$backText = $isVisitorView ? '返回游客查单' : '返回订单列表';
?>
<div class="order-detail-container" style="padding: 20px;">
    <div class="order-header" style="margin-bottom: 20px;">
        <h3 style="margin: 0 0 10px 0;">订单详情</h3>
        <?php $displayNo = $order['out_trade_no'] ?? ($order['order_no'] ?? '-'); ?>
        <p style="color: #666;">订单号：<?php echo htmlspecialchars($displayNo); ?></p>
    </div>

    <div class="order-info" style="background: #f8f8f8; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                <p><strong>商品名称：</strong><?php echo htmlspecialchars($goods['title']); ?></p>
                <p><strong>购买数量：</strong><?php echo $child_order['quantity']; ?></p>
            </div>
            <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                <p><strong>订单金额：</strong><?php echo number_format(($order['amount'] ?? 0) / 100, 2); ?> 元</p>
                <p><strong>订单状态：</strong>
                    <?php
                    switch ($child_order['status']) {
                        case 2:
                            echo '<span style="color: green;">已发货</span>';
                            break;
                        case 1:
                            echo '<span style="color: orange;">待发货</span>';
                            break;
                        case -2:
                            echo '<span style="color: #cf1322;">已取消</span>';
                            break;
                        default:
                            echo '<span style="color: gray;">未支付</span>';
                    }
                    ?>
                </p>
            </div>
            <div style="flex: 1; min-width: 200px; margin-bottom: 10px;">
                <p><strong>下单时间：</strong><?php echo date('Y-m-d H:i:s', $order['create_time']); ?></p>
                <?php if ($order['pay_time']): ?>
                <p><strong>支付时间：</strong><?php echo date('Y-m-d H:i:s', $order['pay_time']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($merchantRemark !== ''): ?>
    <div class="order-merchant-note" style="margin-bottom: 20px; padding: 16px 18px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; color: #9a3412; line-height: 1.8;">
        <div style="font-weight: 600; margin-bottom: 6px;">商户留言</div>
        <div><?php echo $merchantRemark; ?></div>
    </div>
    <?php endif; ?>

    <?php if ($child_order['status'] == 2 && !empty($secrets)): ?>
    <div class="order-secrets" style="margin-bottom: 20px;">
        <h4 style="margin: 0 0 10px 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">卡密信息</h4>
        <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
            <?php foreach ($secrets as $index => $secret): ?>
            <div style="padding: 10px; background: #f9f9f9; margin-bottom: 10px; border-radius: 3px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="color: #666; font-size: 12px;">#<?php echo $index + 1; ?></span>
                    <button class="copy-btn" data-content="<?php echo htmlspecialchars($secret['content']); ?>"
                            style="padding: 3px 10px; font-size: 12px; cursor: pointer;">
                        复制
                    </button>
                </div>
                <div style="margin-top: 5px; word-break: break-all; font-family: monospace;">
                    <?php echo nl2br(htmlspecialchars($secret['content'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($child_order['status'] == 1): ?>
    <div class="order-pending" style="text-align: center; padding: 40px; background: #fffbe6; border-radius: 5px;">
        <p style="font-size: 16px; color: #faad14;">订单正在处理中，请稍候...</p>
        <p style="color: #999; font-size: 14px;">
            <?php if ($goods['type'] == 'em_manual'): ?>
            人工发货商品需要等待商家处理，请耐心等待
            <?php else: ?>
            如长时间未发货，请联系客服
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="order-actions" style="text-align: center; margin-top: 20px;">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" style="padding: 10px 20px; background: #1890ff; color: white; text-decoration: none; border-radius: 5px;">
            <?php echo $backText; ?>
        </a>
        <?php if (!empty($repayUrl)): ?>
        <a href="<?php echo htmlspecialchars($repayUrl); ?>" style="padding: 10px 20px; background: #1677ff; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
            再次进入支付页
        </a>
        <?php if (!empty($cancelUrl)): ?>
        <a href="<?php echo htmlspecialchars($cancelUrl); ?>" onclick="return confirm('确认取消当前订单吗？');" style="padding: 10px 20px; background: #fff2f0; color: #cf1322; text-decoration: none; border: 1px solid rgba(207, 19, 34, 0.25); border-radius: 5px; margin-left: 10px;">
            取消订单
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <a href="<?php echo $child_order['url'] ?? 'index.php'; ?>" target="_blank"
           style="padding: 10px 20px; background: #52c41a; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
            再次购买
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // 复制功能
    var copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(function(btn){
        btn.addEventListener('click', function(){
            var content = this.getAttribute('data-content');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(content).then(function(){
                    alert('复制成功');
                });
            } else {
                // 兼容旧浏览器
                var textarea = document.createElement('textarea');
                textarea.value = content;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('复制成功');
            }
        });
    });
});
</script>
