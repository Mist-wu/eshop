<?php
/**
 * SKU 组件
 * 简化的商品规格管理组件
 *
 * 用法:
 *   $sku_goods_id = $goods['id'] ?? 0;
 *   $sku_type_id = $goods['attr_id'] ?? 0;
 *   $sku_goods_type = $goods['type'] ?? '';
 *   include View::getAdmView('components/sku/sku_widget');
 *
 * @package EMSHOP
 */

$sku_goods_id = isset($sku_goods_id) ? (int)$sku_goods_id : 0;
$sku_type_id = isset($sku_type_id) ? (int)$sku_type_id : 0;
$sku_goods_type = isset($sku_goods_type) ? $sku_goods_type : '';
$sku_mode = isset($sku_mode) ? $sku_mode : 'single';
?>

<style>
#em-sku-widget .em-sku-empty {
    padding: 30px;
    text-align: center;
    color: #999;
    background: #fafafa;
    border: 1px dashed #ddd;
    border-radius: 4px;
}
#em-sku-widget .em-batch-fill {
    cursor: pointer;
    color: #1e9fff;
    margin-left: 5px;
}
#em-sku-widget .em-batch-fill:hover {
    color: #009688;
}
#em-sku-widget #em-sku-table .layui-input {
    height: 32px;
    min-width: 80px;
}
#em-sku-widget #em-spec-select-table .layui-form-checkbox {
    margin: 3px 5px 3px 0;
}
</style>

<div id="em-sku-widget" class="layui-form"
     data-goods-id="<?= $sku_goods_id ?>"
     data-type-id="<?= $sku_type_id ?>"
     data-goods-type="<?= htmlspecialchars($sku_goods_type) ?>">

    <!-- 规格类型选择：单规格/多规格 -->
    <div class="layui-form-item" id="em-sku-mode">
        <label class="layui-form-label">规格类型</label>
        <div class="layui-input-block">
            <input type="radio" name="is_sku" value="n" lay-filter="em-sku-mode" title="单规格" checked>
            <input type="radio" name="is_sku" value="y" lay-filter="em-sku-mode" title="多规格">
        </div>
    </div>

    <!-- 规格模板选择（仅多规格时显示） -->
    <div class="layui-form-item" id="em-sku-template" style="display:none;">
        <label class="layui-form-label">规格模板</label>
        <div class="layui-input-block">
            <select name="group_id" id="em-template-select" lay-filter="em-template-select">
                <option value="">请选择规格模板</option>
            </select>
        </div>
    </div>

    <!-- 规格值选择（选择模板后显示） -->
    <div class="layui-form-item" id="em-sku-spec-wrap" style="display:none;">
        <label class="layui-form-label">选择规格</label>
        <div class="layui-input-block" id="em-sku-spec"></div>
    </div>

    <!-- SKU 价格表格 -->
    <div class="layui-form-item" id="em-sku-table-wrap">
        <label class="layui-form-label">设置价格信息</label>
        <div class="layui-input-block" id="em-sku-table">
            <div class="em-sku-empty">加载中...</div>
        </div>
    </div>

</div>

<script src="./views/components/sku/sku.js?v=<?= time() ?>"></script>
<script>
layui.use(['form', 'layer'], function() {
    // Layui 加载完成后初始化 SKU 组件
    EmSku.init({
        goods_id: <?= $sku_goods_id ?>,
        type_id: <?= $sku_type_id ?>,
        goods_type: '<?= addslashes($sku_goods_type) ?>'
    });
});
</script>
