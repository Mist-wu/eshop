<?php
defined('EM_ROOT') || exit('access denied!');

$db = Database::getInstance();
$db_prefix = DB_PREFIX;

$rawStatus = isset($child_order['status']) ? (int)$child_order['status'] : (int)($order['status'] ?? 0);
$orderStatusText = function_exists('orderStatusText') ? orderStatusText($rawStatus) : '未知状态';

$orderAmount = isset($order['amount']) ? number_format($order['amount'] / 100, 2) : '0.00';
$unitPrice = isset($child_order['unit_price']) ? number_format($child_order['unit_price'] / 100, 2) : '0.00';
$quantity = isset($child_order['quantity']) ? (int)$child_order['quantity'] : 0;

$createTimeText = !empty($order['create_time']) ? date('Y-m-d H:i:s', $order['create_time']) : '-';
$payTimeText = !empty($order['pay_time']) ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';

$user = isset($user) && is_array($user) ? $user : [];
if (empty($user) && !empty($order['user_id'])) {
    $user = $db->once_fetch_array("select * from {$db_prefix}user where uid = {$order['user_id']}");
}

$attrSpec = $child_order['attr_spec'] ?? '';
if (in_array($goods['type'] ?? '', ['em_auto', 'em_manual']) && function_exists('emFormatSkuOptionIds')) {
    $attrSpec = emFormatSkuOptionIds($child_order['goods_id'], $child_order['sku'] ?? '');
}
$attrSpec = $attrSpec ?: '默认规格';

$attachText = '';
if (!empty($child_order['attach_user'])) {
    $attachUser = json_decode($child_order['attach_user'], true);
    if (is_array($attachUser)) {
        foreach ($attachUser as $key => $val) {
            $attachText .= $key . '：' . $val . '；';
        }
    }
}
$attachText = $attachText ?: '无';

$remoteTradeNo = $child_order['remote_trade_no'] ?? '';
$emGoods = $db->once_fetch_array("SELECT * FROM {$db_prefix}em_goods WHERE goods_id = " . (int)$goods['id']);
$site = $emGoods ? $db->once_fetch_array("SELECT * FROM {$db_prefix}em_site WHERE id = " . (int)$emGoods['site_id']) : null;

$secretSql = "SELECT content, create_time AS deliver_time
              FROM {$db_prefix}stock_usage
              WHERE order_list_id = {$child_order['id']} AND stock_id = 0
              ORDER BY id ASC
              LIMIT 500";
$secrets = $db->fetch_all($secretSql);

$countRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM {$db_prefix}stock_usage WHERE order_list_id = {$child_order['id']} AND stock_id = 0");
$secretTotal = (int)($countRow['total'] ?? 0);

$secretContents = [];
if (!empty($secrets)) {
    foreach ($secrets as $row) {
        $secretContents[] = $row['content'];
    }
}

$statusClass = 'status-pending';
if ($rawStatus === 1) {
    $statusClass = 'status-delivering';
} elseif ($rawStatus === 2) {
    $statusClass = 'status-completed';
} elseif ($rawStatus === -1) {
    $statusClass = 'status-partial';
} elseif ($rawStatus !== 0) {
    $statusClass = 'status-cancelled';
}
?>

<style>
    html,
    body {
        height: auto;
        overflow: auto;
    }

    body {
        background: #f6f8fa;
    }

    .em-order-detail {
        padding: 20px;
        display: grid;
        gap: 16px;
    }

    .em-header {
        background: linear-gradient(135deg, #4C7D71 0%, #6BA596 100%);
        border-radius: 14px;
        padding: 20px 24px;
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(76, 125, 113, 0.25);
    }

    .em-header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .em-header-title h2 {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 600;
    }

    .em-header-title p {
        margin: 0;
        font-size: 13px;
        opacity: 0.9;
        word-break: break-all;
    }

    .status-pill {
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.15);
    }

    .status-pending { background: rgba(245, 158, 11, 0.2); color: #fde68a; }
    .status-delivering { background: rgba(14, 116, 144, 0.2); color: #bae6fd; }
    .status-completed { background: rgba(16, 185, 129, 0.2); color: #bbf7d0; }
    .status-partial { background: rgba(99, 102, 241, 0.2); color: #c7d2fe; }
    .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #fecaca; }

    .em-stats {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }

    .em-stat {
        background: rgba(255, 255, 255, 0.16);
        border-radius: 10px;
        padding: 10px 12px;
    }

    .em-stat .label {
        font-size: 12px;
        opacity: 0.8;
        margin-bottom: 4px;
    }

    .em-stat .value {
        font-size: 14px;
        font-weight: 600;
        word-break: break-all;
    }

    .em-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .em-card-header {
        padding: 12px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f5;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #334155;
    }

    .em-card-body {
        padding: 14px 18px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px 20px;
    }

    .info-item {
        display: flex;
        gap: 8px;
        font-size: 13px;
        line-height: 1.6;
    }

    .info-item span {
        color: #94a3b8;
        min-width: 70px;
        flex-shrink: 0;
    }

    .info-item strong {
        color: #1f2937;
        font-weight: 600;
        word-break: break-all;
    }

    .em-required {
        display: grid;
        gap: 8px;
    }

    .required-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        color: #334155;
    }

    .deliver-section {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .deliver-header {
        padding: 12px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .deliver-header .title {
        font-weight: 600;
        font-size: 14px;
        color: #334155;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .deliver-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #cbd5f5;
        font-size: 12px;
        background: #ffffff;
        color: #0f766e;
        cursor: pointer;
        transition: all 0.2s;
    }

    .action-btn.primary {
        background: #0f766e;
        border-color: #0f766e;
        color: #ffffff;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(15, 118, 110, 0.15);
    }

    .copy-btn.copied {
        background: #ecfdf5;
        border-color: #34d399;
        color: #059669;
    }

    .secret-list {
        max-height: 320px;
        overflow-y: auto;
        padding: 10px 18px 16px;
        display: grid;
        gap: 10px;
    }

    .secret-item {
        background: #f8fafc;
        border-radius: 10px;
        padding: 10px 12px;
        display: grid;
        gap: 8px;
    }

    .secret-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .secret-index {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 600;
    }

    .secret-content {
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        font-size: 13px;
        line-height: 1.6;
        color: #1f2937;
        word-break: break-all;
        background: #ffffff;
        border-radius: 8px;
        padding: 8px 10px;
        border: 1px dashed #cbd5f5;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 44px;
        color: #cbd5f5;
        margin-bottom: 10px;
    }
</style>

<div class="em-order-detail">
    <div class="em-header">
        <div class="em-header-top">
            <div class="em-header-title">
                <h2><?= htmlspecialchars($goods['title'] ?? '商品') ?></h2>
                <p>订单号：<?= htmlspecialchars($order['out_trade_no'] ?? '-') ?></p>
            </div>
            <span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($orderStatusText) ?></span>
        </div>
        <div class="em-stats">
            <div class="em-stat">
                <div class="label">订单金额</div>
                <div class="value">￥<?= $orderAmount ?></div>
            </div>
            <div class="em-stat">
                <div class="label">购买数量</div>
                <div class="value"><?= $quantity ?> 件</div>
            </div>
            <div class="em-stat">
                <div class="label">支付方式</div>
                <div class="value"><?= htmlspecialchars($order['payment'] ?? '-') ?></div>
            </div>
            <div class="em-stat">
                <div class="label">下单IP</div>
                <div class="value"><?= htmlspecialchars($order['client_ip'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="em-card">
        <div class="em-card-header">
            <i class="layui-icon layui-icon-user"></i>
            用户 & 订单信息
        </div>
        <div class="em-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span>用户昵称</span>
                    <strong><?= htmlspecialchars($user['nickname'] ?? '游客') ?></strong>
                </div>
                <div class="info-item">
                    <span>用户账号</span>
                    <strong><?= htmlspecialchars($user['email'] ?? '-') ?></strong>
                </div>
                <div class="info-item">
                    <span>联系方式</span>
                    <strong><?= htmlspecialchars($order['contact'] ?? '-') ?></strong>
                </div>
                <div class="info-item">
                    <span>商品规格</span>
                    <strong><?= htmlspecialchars($attrSpec) ?></strong>
                </div>
                <div class="info-item">
                    <span>商品单价</span>
                    <strong>￥<?= $unitPrice ?></strong>
                </div>
                <div class="info-item">
                    <span>下单时间</span>
                    <strong><?= $createTimeText ?></strong>
                </div>
                <div class="info-item">
                    <span>支付时间</span>
                    <strong><?= $payTimeText ?></strong>
                </div>
                <?php if (!empty($remoteTradeNo)): ?>
                <div class="info-item">
                    <span>远程订单</span>
                    <strong><?= htmlspecialchars($remoteTradeNo) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($site): ?>
                <div class="info-item">
                    <span>对接来源</span>
                    <strong><?= htmlspecialchars($site['sitename'] ?? '-') ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="em-card">
        <div class="em-card-header">
            <i class="layui-icon layui-icon-note"></i>
            附加选项
        </div>
        <div class="em-card-body">
            <div class="required-item"><?= htmlspecialchars($attachText) ?></div>
        </div>
    </div>

    <div class="deliver-section">
        <div class="deliver-header">
            <div class="title">
                <i class="layui-icon layui-icon-read"></i>
                发货内容
                <?php if ($secretTotal > 0): ?>
                    <span class="layui-badge layui-bg-blue"><?= $secretTotal ?> 条</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($secretContents)): ?>
            <div class="deliver-actions">
                <button class="action-btn" id="copyAllBtn">复制全部</button>
                <button class="action-btn primary" id="downloadBtn">下载卡密</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($secretContents)): ?>
            <div class="secret-list">
                <?php foreach ($secrets as $index => $secret): ?>
                    <div class="secret-item">
                        <div class="secret-top">
                            <span class="secret-index">#<?= $index + 1 ?></span>
                            <button class="action-btn copy-btn" data-content="<?= htmlspecialchars($secret['content']) ?>">复制</button>
                        </div>
                        <div class="secret-content"><?= htmlspecialchars($secret['content']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="layui-icon layui-icon-face-surprised"></i>
                <?php if ($rawStatus === 0): ?>
                    <p>订单尚未支付，暂无发货内容。</p>
                <?php elseif ($rawStatus === 1): ?>
                    <p>订单待发货，请检查上游发货状态。</p>
                <?php else: ?>
                    <p>暂无发货内容</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
layui.use(['layer'], function() {
    var layer = layui.layer;

    var allSecrets = <?= json_encode($secretContents, JSON_UNESCAPED_UNICODE) ?>;

    function copyToClipboard(text) {
        return new Promise(function(resolve, reject) {
            if (!text || !text.trim()) {
                reject(new Error('empty'));
                return;
            }
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus({ preventScroll: true });
            textarea.setSelectionRange(0, text.length);
            var success = document.execCommand('copy');
            document.body.removeChild(textarea);
            success ? resolve() : reject(new Error('copy failed'));
        });
    }

    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var content = this.getAttribute('data-content') || '';
            var self = this;
            var original = self.innerHTML;
            copyToClipboard(content).then(function() {
                self.innerHTML = '已复制';
                self.classList.add('copied');
                setTimeout(function() { self.innerHTML = original; }, 1500);
                setTimeout(function() { self.classList.remove('copied'); }, 1500);
            }).catch(function() {
                layer.msg('复制失败');
            });
        });
    });

    var copyAllBtn = document.getElementById('copyAllBtn');
    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', function() {
            var allContent = allSecrets.join('\n');
            copyToClipboard(allContent).then(function() {
                layer.msg('已复制 ' + allSecrets.length + ' 条卡密');
            }).catch(function() {
                layer.msg('复制失败');
            });
        });
    }

    var downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            var content = allSecrets.join('\r\n');
            var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            var filenameSeed = <?= json_encode($order['out_trade_no'] ?? 'order', JSON_UNESCAPED_UNICODE) ?>;
            a.download = '卡密_' + filenameSeed + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            layer.msg('下载已开始');
        });
    }
});
</script>
