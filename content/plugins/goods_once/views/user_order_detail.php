<?php defined('EM_ROOT') || exit('access denied!');



$viewOrder = $order ?? [];
$viewChild = $child_order ?? [];
$viewGoods = $goods ?? [];

if (!isset($viewOrder['out_trade_no']) && isset($viewGoods['out_trade_no'])) {
    $tmpOrder = $viewGoods;

    if (isset($viewChild['title'])) {
        $tmpGoods = $viewChild;
        $tmpChild = $viewOrder;
    } elseif (isset($viewOrder['title'])) {
        $tmpGoods = $viewOrder;
        $tmpChild = $viewChild;
    } else {
        $tmpGoods = $viewChild;
        $tmpChild = $viewOrder;
    }

    $viewOrder = $tmpOrder;
    $viewChild = $tmpChild;
    $viewGoods = $tmpGoods;
}

$orderStatus = isset($viewOrder['status']) ? (int)$viewOrder['status'] : 0;
$orderStatusText = function_exists('orderStatusText') ? orderStatusText($orderStatus) : '未知状态';
$orderAmount = isset($viewOrder['amount']) ? number_format($viewOrder['amount'] / 100, 2) : '0.00';
$unitPrice = isset($viewChild['unit_price']) ? number_format($viewChild['unit_price'] / 100, 2) : '';
$quantity = isset($viewChild['quantity']) ? (int)$viewChild['quantity'] : 0;
$createTimeText = !empty($viewOrder['create_time']) ? date('Y-m-d H:i:s', $viewOrder['create_time']) : '-';
$payTimeText = !empty($viewOrder['pay_time']) ? date('Y-m-d H:i:s', $viewOrder['pay_time']) : '未支付';
$goodsTitle = $viewGoods['title'] ?? '未知商品';
$goodsCover = $viewGoods['cover'] ?? '';
$goodsId = $viewGoods['id'] ?? ($viewChild['goods_id'] ?? 0);
$goodsUrl = $goodsId ? Url::goods($goodsId) : '#';
$orderNo = $viewOrder['out_trade_no'] ?? '';
$isVisitorView = !empty($GLOBALS['EM_VISITOR_ORDER_VIEW']);
$canManageUnpaid = !empty($orderNo) && empty($viewOrder['pay_time']) && $orderStatus === 0;
$repayUrl = $canManageUnpaid ? EM_URL . '?action=pay&out_trade_no=' . rawurlencode($orderNo) : '';
$cancelUrl = (!$isVisitorView && $canManageUnpaid) ? EM_URL . 'user/order.php?action=cancel&out_trade_no=' . rawurlencode($orderNo) : '';
$backUrl = $isVisitorView ? EM_URL . 'user/visitors.php' : EM_URL . 'user/order.php';
$backText = $isVisitorView ? '返回游客查单' : '返回订单列表';

$attachText = '';
if (!empty($viewChild['attach_user'])) {
    $attachUser = json_decode($viewChild['attach_user'], true);
    if (is_array($attachUser)) {
        foreach ($attachUser as $key => $val) {
            $attachText .= $key . '：' . $val . '；';
        }
    }
}

$secrets = [];
$secretTotal = 0;
if (!empty($viewChild['id'])) {
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $sql = "SELECT s.content, u.create_time AS deliver_time
            FROM {$db_prefix}stock_usage u
            INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
            WHERE u.order_list_id = {$viewChild['id']}
            ORDER BY u.id ASC limit 500";
    $secrets = $db->fetch_all($sql);

    $countRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM {$db_prefix}stock_usage WHERE order_list_id = {$viewChild['id']}");
    $secretTotal = (int)($countRow['total'] ?? 0);
}

$secretContents = [];
if (!empty($secrets) && is_array($secrets)) {
    foreach ($secrets as $row) {
        $secretContents[] = $row['content'];
    }
}

$secretCount = count($secretContents);
$statusClass = 'status-pending';
if ($orderStatus === 1) {
    $statusClass = 'status-delivering';
} elseif ($orderStatus === 2) {
    $statusClass = 'status-completed';
} elseif ($orderStatus === -1) {
    $statusClass = 'status-partial';
} elseif ($orderStatus !== 0) {
    $statusClass = 'status-cancelled';
}

?>



<style>
    .order-detail-page {
        display: grid;
        gap: 18px;
    }

    .detail-hero {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 22px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .hero-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--primary-strong);
    }

    .detail-title {
        margin: 8px 0 6px;
        font-size: 24px;
        font-weight: 700;
        font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
    }

    .detail-subtitle {
        margin: 0;
        font-size: 13px;
        color: var(--muted);
        word-break: break-all;
    }

    .status-pill {
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
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

    .status-partial {
        background: rgba(99, 102, 241, 0.16);
        color: #4338ca;
        border-color: rgba(99, 102, 241, 0.35);
    }

    .status-cancelled {
        background: rgba(239, 68, 68, 0.16);
        color: #b91c1c;
        border-color: rgba(239, 68, 68, 0.35);
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .detail-card {
        background: var(--panel);
        border: 1px solid var(--border-soft);
        border-radius: 20px;
        padding: 18px 20px;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
    }

    .detail-card h3 {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 700;
    }

    .goods-row {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .goods-cover {
        width: 78px;
        height: 78px;
        border-radius: 14px;
        object-fit: cover;
        background: #f3f4f6;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(15, 118, 110, 0.5);
        font-size: 28px;
    }

    .goods-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 6px;
        line-height: 1.5;
    }

    .goods-meta {
        font-size: 12px;
        color: var(--muted);
        line-height: 1.5;
    }

    .info-list {
        display: grid;
        gap: 10px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
    }

    .info-item span {
        color: var(--muted);
    }

    .info-item strong {
        font-weight: 600;
        color: var(--text);
    }

    .note-card {
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.28);
        border-radius: 16px;
        padding: 16px 18px;
        color: #92400e;
        font-size: 13px;
        line-height: 1.7;
    }

    .secret-panel {
        background: var(--panel);
        border: 1px solid var(--border-soft);
        border-radius: 20px;
        padding: 18px 20px;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
        display: grid;
        gap: 14px;
    }

    .secret-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .secret-count {
        font-size: 14px;
        color: var(--muted);
    }

    .secret-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .detail-btn {
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #ffffff;
        color: var(--text);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .detail-btn.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-strong));
        border-color: transparent;
        color: #ffffff;
    }

    .detail-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);
    }

    .secret-list {
        display: grid;
        gap: 12px;
    }

    .secret-item {
        background: var(--panel-soft);
        border: 1px solid var(--border-soft);
        border-radius: 14px;
        padding: 14px 16px;
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
        color: var(--muted);
        font-weight: 600;
    }

    .secret-content {
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        font-size: 13px;
        line-height: 1.6;
        color: #111827;
        word-break: break-all;
        background: #ffffff;
        border-radius: 10px;
        padding: 10px 12px;
        border: 1px dashed rgba(15, 118, 110, 0.2);
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--muted);
        background: var(--panel);
        border: 1px dashed var(--border);
        border-radius: 18px;
    }

    .empty-state i {
        font-size: 46px;
        color: rgba(15, 118, 110, 0.25);
        margin-bottom: 12px;
    }

    .detail-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .detail-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #ffffff;
        color: var(--text);
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .detail-link.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-strong));
        border-color: transparent;
        color: #ffffff;
    }

    .detail-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    @media screen and (max-width: 768px) {
        .detail-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .goods-row {
            flex-direction: column;
        }
    }
</style>

<div class="main-content">
    <div class="order-detail-page">
        <section class="detail-hero">
            <div>
                <div class="hero-tag"><i class="fa fa-file-text-o"></i>订单详情</div>
                <h1 class="detail-title"><?= htmlspecialchars($goodsTitle) ?></h1>
                <p class="detail-subtitle">订单号：<?= htmlspecialchars($viewOrder['out_trade_no'] ?? '-') ?></p>
            </div>
            <div class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($orderStatusText) ?></div>
        </section>

        <section class="detail-grid">
            <div class="detail-card">
                <h3>商品信息</h3>
                <div class="goods-row">
                    <?php if (!empty($goodsCover)): ?>
                        <img class="goods-cover" src="<?= htmlspecialchars($goodsCover) ?>" alt="<?= htmlspecialchars($goodsTitle) ?>">
                    <?php else: ?>
                        <div class="goods-cover"><i class="fa fa-cube"></i></div>
                    <?php endif; ?>
                    <div>
                        <div class="goods-title"><?= htmlspecialchars($goodsTitle) ?></div>
                        <?php if (!empty($viewChild['attr_spec'])): ?>
                            <div class="goods-meta">规格：<?= htmlspecialchars($viewChild['attr_spec']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($attachText)): ?>
                            <div class="goods-meta">附加信息：<?= htmlspecialchars($attachText) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($unitPrice)): ?>
                            <div class="goods-meta">单价：￥<?= $unitPrice ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h3>订单信息</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span>订单金额</span>
                        <strong>￥<?= $orderAmount ?></strong>
                    </div>
                    <div class="info-item">
                        <span>支付方式</span>
                        <strong><?= htmlspecialchars($viewOrder['payment'] ?? '-') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>购买数量</span>
                        <strong><?= $quantity ?> 件</strong>
                    </div>
                    <div class="info-item">
                        <span>发货数量</span>
                        <strong><?= $secretCount ?></strong>
                    </div>
                    <div class="info-item">
                        <span>下单时间</span>
                        <strong><?= $createTimeText ?></strong>
                    </div>
                    <div class="info-item">
                        <span>支付时间</span>
                        <strong><?= $payTimeText ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($viewGoods['pay_content'])): ?>
            <div class="note-card">
                <strong>商家留言：</strong><br>
                <?= $viewGoods['pay_content'] ?>
            </div>
        <?php endif; ?>

        <section class="secret-panel">
            <div class="secret-header">
                <div>
                    <h3 style="margin: 0 0 6px; font-size: 16px;">卡密信息</h3>
                    <div class="secret-count">
                        已展示 <?= $secretCount ?> 条
                        <?php if ($secretTotal > $secretCount): ?>
                            ，共 <?= $secretTotal ?> 条（仅显示前 500 条）
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($secretCount > 0): ?>
                    <div class="secret-actions">
                        <button class="detail-btn" id="copyAllBtn"><i class="fa fa-copy"></i> 复制全部(最多500条)</button>
                        <button class="detail-btn primary" id="downloadBtn"><i class="fa fa-download"></i> 下载全部卡密</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($secretCount > 0): ?>
                <div class="secret-list">
                    <?php foreach ($secrets as $index => $secret): ?>
                        <div class="secret-item">
                            <div class="secret-top">
                                <span class="secret-index">#<?= $index + 1 ?></span>
                                <button class="detail-btn copy-btn" data-content="<?= htmlspecialchars($secret['content']) ?>">
                                    <i class="fa fa-copy"></i> 复制
                                </button>
                            </div>
                            <div class="secret-content"><?= htmlspecialchars($secret['content']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-inbox"></i>
                    <?php if ($orderStatus === 0): ?>
                        <p>订单尚未支付，支付完成后将显示卡密信息。</p>
                    <?php elseif ($orderStatus === 1): ?>
                        <p>订单已支付，正在等待发货。</p>
                    <?php else: ?>
                        <p>暂无卡密信息，如有问题请联系客服。</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="detail-actions">
            <a class="detail-link" href="<?= htmlspecialchars($backUrl) ?>"><i class="fa fa-arrow-left"></i> <?= $backText ?></a>
            <?php if (!empty($repayUrl)): ?>
                <a class="detail-link primary" href="<?= htmlspecialchars($repayUrl) ?>"><i class="fa fa-qrcode"></i> 再次进入支付页</a>
                <?php if (!empty($cancelUrl)): ?>
                <a class="detail-link" href="<?= htmlspecialchars($cancelUrl) ?>" onclick="return confirm('确认取消当前订单吗？');"><i class="fa fa-times-circle"></i> 取消订单</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($goodsUrl !== '#'): ?>
                <a class="detail-link primary" href="<?= htmlspecialchars($goodsUrl) ?>" target="_blank"><i class="fa fa-refresh"></i> 再次购买</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (function () {
        var allSecrets = <?= json_encode($secretContents, JSON_UNESCAPED_UNICODE) ?>;
        var downloadUrl = <?= json_encode(EM_URL . '?plugin=goods_once&action=download&out_trade_no=' . rawurlencode($viewOrder['out_trade_no'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;

        function copyToClipboard(text) {
            return new Promise(function(resolve, reject) {
                if (!text || !text.trim()) {
                    reject(new Error('empty'));
                    return;
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(resolve).catch(reject);
                    return;
                }

                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.top = '50%';
                textarea.style.left = '50%';
                textarea.style.transform = 'translate(-50%, -50%)';
                textarea.style.width = '200px';
                textarea.style.height = '100px';
                textarea.style.opacity = '0';
                textarea.style.zIndex = '99999';
                document.body.appendChild(textarea);

                try {
                    textarea.focus({ preventScroll: true });
                    textarea.setSelectionRange(0, text.length);
                    var success = document.execCommand('copy');
                    success ? resolve() : reject(new Error('copy failed'));
                } catch (err) {
                    reject(err);
                } finally {
                    setTimeout(function() {
                        document.body.removeChild(textarea);
                    }, 300);
                }
            });
        }

        document.querySelectorAll('.copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var content = this.getAttribute('data-content') || '';
                var original = this.innerHTML;
                var self = this;
                copyToClipboard(content).then(function() {
                    self.innerHTML = '<i class="fa fa-check"></i> 已复制';
                    setTimeout(function() { self.innerHTML = original; }, 1600);
                }).catch(function() {
                    if (window.layui && window.layui.layer) {
                        layui.layer.msg('复制失败');
                    } else {
                        alert('复制失败');
                    }
                });
            });
        });

        var copyAllBtn = document.getElementById('copyAllBtn');
        if (copyAllBtn) {
            copyAllBtn.addEventListener('click', function() {
                var allContent = allSecrets.join('\n');
                var original = this.innerHTML;
                var self = this;
                copyToClipboard(allContent).then(function() {
                    self.innerHTML = '<i class="fa fa-check"></i> 已复制';
                    if (window.layui && window.layui.layer) {
                        layui.layer.msg('已复制 ' + allSecrets.length + ' 条卡密');
                    }
                    setTimeout(function() { self.innerHTML = original; }, 1800);
                }).catch(function() {
                    if (window.layui && window.layui.layer) {
                        layui.layer.msg('复制失败');
                    } else {
                        alert('复制失败');
                    }
                });
            });
        }

        var downloadBtn = document.getElementById('downloadBtn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                if (!downloadUrl) {
                    if (window.layui && window.layui.layer) {
                        layui.layer.msg('下载地址无效');
                    } else {
                        alert('下载地址无效');
                    }
                    return;
                }
                window.location.href = downloadUrl;
                if (window.layui && window.layui.layer) {
                    layui.layer.msg('下载已开始');
                }
            });
        }
    })();
</script>

<script>
    $('#menu-order').addClass('open');
    $('#menu-order > ul').css('display', 'block');
    $('#menu-order > a > i.nav_right').attr('class', 'fa fa-angle-down nav_right');
    $('#menu-order-user').addClass('menu-current');
</script>
