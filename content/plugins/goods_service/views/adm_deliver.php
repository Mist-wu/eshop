<?php
defined('EM_ROOT') || exit('access denied!');

if (!User::isAdmin() && ROLE !== 'editor') {
    exit('access denied!');
}

$db = Database::getInstance();
$db_prefix = DB_PREFIX;

$secrets = $db->fetch_all("SELECT content, create_time FROM {$db_prefix}stock_usage WHERE order_list_id = {$child_order['id']} AND stock_id = 0 ORDER BY id ASC");
$deliveredCount = is_array($secrets) ? count($secrets) : 0;
?>
<style>
    .deliver-page {
        padding: 20px;
        background: #f8fafb;
        min-height: 100%;
    }
    .deliver-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        margin-bottom: 16px;
    }
    .deliver-card h3 {
        margin: 0 0 10px;
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px 16px;
        font-size: 13px;
        color: #4b5563;
    }
    .info-item span {
        color: #9ca3af;
        margin-right: 6px;
    }
    .deliver-form textarea {
        width: 100%;
        min-height: 160px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 13px;
        line-height: 1.6;
        resize: vertical;
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    }
    .deliver-actions {
        margin-top: 12px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }
    .btn-primary {
        background: #0f766e;
        color: #fff;
    }
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    .deliver-list {
        display: grid;
        gap: 10px;
        max-height: 220px;
        overflow-y: auto;
    }
    .deliver-item {
        padding: 10px 12px;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px dashed #cbd5f5;
        font-size: 13px;
        line-height: 1.6;
        word-break: break-all;
    }
    .deliver-hint {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
    }
</style>

<div class="deliver-page">
    <div class="deliver-card">
        <h3>订单信息</h3>
        <div class="info-grid">
            <div class="info-item"><span>订单号</span><?= htmlspecialchars($order['out_trade_no'] ?? '-') ?></div>
            <div class="info-item"><span>商品</span><?= htmlspecialchars($goods['title'] ?? '-') ?></div>
            <div class="info-item"><span>数量</span><?= (int)($child_order['quantity'] ?? 0) ?> 件</div>
            <div class="info-item"><span>状态</span><?= htmlspecialchars(orderStatusText($order['status'] ?? 0)) ?></div>
        </div>
    </div>

    <?php if ($deliveredCount > 0): ?>
        <div class="deliver-card">
            <h3>已发货内容（<?= $deliveredCount ?>）</h3>
            <div class="deliver-list">
                <?php foreach ($secrets as $row): ?>
                    <div class="deliver-item"><?= htmlspecialchars($row['content'] ?? '') ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="deliver-card deliver-form">
        <h3>填写发货内容</h3>
        <textarea id="deliver-content" placeholder="每行一条发货内容，例如账号、链接、兑换码等"></textarea>
        <div class="deliver-hint">提示：多条内容请换行分隔，系统会按行拆分保存。</div>
        <div class="deliver-actions">
            <button class="btn btn-secondary" type="button" onclick="if(window.parent && window.parent.AdminModal){window.parent.AdminModal.close();}">取消</button>
            <button class="btn btn-primary" id="deliver-submit" type="button">确认发货</button>
        </div>
    </div>
</div>

<script>
layui.use(['layer'], function() {
    var layer = layui.layer;
    var $ = layui.$;

    var orderId = <?= (int)$order['id'] ?>;
    var orderListId = <?= (int)$child_order['id'] ?>;
    var token = '<?= LoginAuth::genToken() ?>';

    $('#deliver-submit').on('click', function(){
        var content = $('#deliver-content').val();
        if (!content || !content.trim()) {
            layer.msg('请输入发货内容');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('提交中...');

        $.ajax({
            url: '<?= PLUGIN_URL ?>goods_service/api.php?action=deliver',
            type: 'POST',
            dataType: 'json',
            data: {
                order_id: orderId,
                order_list_id: orderListId,
                content: content,
                token: token
            },
            success: function(res){
                btn.prop('disabled', false).text('确认发货');
                if (res.code === 0) {
                    layer.msg('发货成功');
                    setTimeout(function(){
                        if (window.parent && window.parent.AdminModal) {
                            window.parent.AdminModal.close();
                        } else {
                            location.reload();
                        }
                    }, 800);
                } else {
                    layer.msg(res.msg || '发货失败');
                }
            },
            error: function(){
                btn.prop('disabled', false).text('确认发货');
                layer.msg('请求失败');
            }
        });
    });
});
</script>