<?php
/**
 * 单规格 SKU 表格模板
 * 渲染单规格商品的价格输入表格
 *
 * 所需变量:
 *   $price_fields - 价格字段定义数组
 *   $sku_data - 已有的 SKU 数据（可选）
 *
 * @package EMSHOP
 */

$price_fields = isset($price_fields) ? $price_fields : [];
$sku_data = isset($sku_data) ? $sku_data : [];
?>

<div style="overflow: auto;">
    <table class="layui-table" id="em-single-sku-table" lay-skin="line">
        <colgroup>
            <?php foreach ($price_fields as $field): ?>
            <col width="120">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr>
                <?php foreach ($price_fields as $field): ?>
                <th><?= htmlspecialchars($field['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($price_fields as $field):
                    $field_name = $field['name'];
                    $value = '';

                    // 处理会员价格字段
                    if (isset($field['member_id'])) {
                        $value = isset($sku_data['member_' . $field['member_id']])
                            ? $sku_data['member_' . $field['member_id']]
                            : '';
                        $input_name = 'config[tier_price][' . $field['member_id'] . '][0]';
                    } else {
                        $value = isset($sku_data[$field_name]) ? $sku_data[$field_name] : '';
                        $input_name = 'skus[' . $field_name . ']';
                    }

                    $input_type = isset($field['type']) ? $field['type'] : 'text';
                ?>
                <td>
                    <input type="<?= $input_type ?>"
                           name="<?= $input_name ?>"
                           class="layui-input"
                           value="<?= htmlspecialchars($value) ?>"
                           placeholder="<?= htmlspecialchars($field['label']) ?>">
                </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</div>
