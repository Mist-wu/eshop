<?php
/**
 * EMSHOP 同系统对接 - 库存查看（未售）
 */

defined('EM_ROOT') || exit('access denied!');

$db = Database::getInstance();
$goodsId = Input::getIntVar('id');

// 获取商品信息
$goodsRow = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "goods WHERE id = " . (int)$goodsId);
if (!$goodsRow) {
    echo '<script>layer.msg("商品不存在");history.back();</script>';
    exit;
}

// 获取对接信息
$emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goodsId);
$site = $emGoods ? emGetSite($emGoods['site_id']) : null;

// 获取 SKU 列表
$skus = $db->fetch_all("SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = " . (int)$goodsId);
$specInfo = function_exists('emGetRemoteSpecInfo') ? emGetRemoteSpecInfo($goodsId) : null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>库存管理 - <?php echo htmlspecialchars($goodsRow['title']); ?></title>
    <link rel="stylesheet" href="<?php echo LAYUI_PATH; ?>css/layui.css">
    <style>
        body {
            background: #f3f5f8;
        }
        .em-stock-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(22, 28, 45, 0.08);
            overflow: hidden;
        }
        .em-stock-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 14px 18px;
            background: linear-gradient(135deg, #1e9fff 0%, #5fb6ff 100%);
            color: #fff;
        }
        .em-stock-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .em-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .em-meta-item {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #eef1f6;
            border-radius: 10px;
            padding: 10px 12px;
            color: #2b3445;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }
        .em-meta-item strong {
            color: #667085;
            display: inline-block;
            min-width: 72px;
        }
        .em-table-wrap {
            border: 1px solid #eef1f6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .em-table-wrap .layui-table {
            margin: 0;
        }
        .em-table-wrap .layui-table thead tr {
            background: #f6f8fb;
        }
        .em-table-wrap .layui-table tbody tr:hover {
            background: #f9fbff;
        }
        .em-table-wrap .layui-table td,
        .em-table-wrap .layui-table th {
            border-color: #eef1f6;
        }
        @media (max-width: 768px) {
            .em-meta { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="layui-fluid" style="padding: 15px;">
    <div class="em-stock-card">
        <div class="layui-card-body">
            <div class="em-meta">
                <div class="em-meta-item"><strong>商品类型：</strong><?php echo $goodsRow['type'] == 'em_auto' ? 'EM对接(自动发货)' : 'EM对接(人工发货)'; ?></div>
                <div class="em-meta-item"><strong>对接站点：</strong><?php echo $site ? htmlspecialchars($site['sitename']) : '站点不存在'; ?></div>
                <?php if ($site): ?>
                <div class="em-meta-item"><strong>站点余额：</strong><?php echo number_format($site['balance'], 2); ?> 元</div>
                <?php endif; ?>
                <?php if ($emGoods): ?>
                <div class="em-meta-item"><strong>远程商品ID：</strong><?php echo $emGoods['remote_goods_id']; ?></div>
                <?php endif; ?>
            </div>

            <div class="em-table-wrap">
            <table class="layui-table" lay-skin="line">
                <colgroup>
                    <col width="200">
                    <col width="150">
                    <col width="150">
                    <col width="150">
                </colgroup>
                <thead>
                    <tr>
                        <th>规格</th>
                        <th>成本价</th>
                        <th>售价</th>
                        <th>库存</th>
                    </tr>
                </thead>
                <tbody id="em-stock-body">
                    <?php foreach ($skus as $sku): ?>
                    <tr>
                        <td>
                            <?php
                            if ($sku['option_ids'] == '0') {
                                echo '默认';
                            } else if (function_exists('emFormatSkuOptionIds') && !empty($specInfo)) {
                                echo htmlspecialchars(rtrim(emFormatSkuOptionIds($goodsId, $sku['option_ids']), '；'));
                            } else {
                                echo htmlspecialchars($sku['option_ids']);
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($sku['cost_price'] / 100, 2); ?> 元</td>
                        <td><?php echo number_format($sku['user_price'] / 100, 2); ?> 元</td>
                        <td>
                            <span class="layui-badge <?php echo $sku['stock'] > 0 ? 'layui-bg-green' : 'layui-bg-gray'; ?>">
                                <?php echo $sku['stock']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo LAYUI_PATH; ?>layui.js"></script>
<script>
layui.use(['layer', 'jquery'], function(){
    var layer = layui.layer;
    var $ = layui.$;
    var apiBase = '<?= EM_URL ?>?plugin=goods_em';

    function syncStock(auto) {
        var goodsId = <?php echo (int)$goodsId; ?>;
        var loadIndex = layer.load(1);
        $.post(apiBase, {action: 'sync_stock', goods_id: goodsId}, function(res){
            layer.close(loadIndex);
            if(res.code === 0){
                fetchStock();
            } else {
                if (!auto) layer.msg(res.msg || '同步失败', {icon: 2});
            }
        }, 'json');
    }

    function fetchStock() {
        var goodsId = <?php echo (int)$goodsId; ?>;
        $.post(apiBase, {action: 'get_stock', goods_id: goodsId}, function(res){
            if(res.code !== 0) return;
            var html = '';
            (res.data || []).forEach(function(item){
                html += '<tr>' +
                    '<td>' + escapeHtml(item.option_text) + '</td>' +
                    '<td>' + item.cost_price + ' 元</td>' +
                    '<td>' + item.user_price + ' 元</td>' +
                    '<td><span class="layui-badge ' + (item.stock > 0 ? 'layui-bg-green' : 'layui-bg-gray') + '">' + item.stock + '</span></td>' +
                    '</tr>';
            });
            $('#em-stock-body').html(html);
        }, 'json');
    }

    // 每次进入自动同步
    syncStock(true);

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

});
</script>
</body>
</html>
