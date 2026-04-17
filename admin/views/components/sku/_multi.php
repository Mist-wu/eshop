<?php
/**
 * 多规格 SKU 表格模板
 * 渲染多规格商品的 SKU 组合表格
 *
 * 所需变量:
 *   $sku_combinations - SKU 组合数组 [{sku: '1-3', values: ['红色', '大号']}]
 *   $price_fields - 价格字段定义数组
 *   $sku_data - 已有的 SKU 数据，以 sku 字符串为键
 *   $spec_names - 规格属性名称数组 ['颜色', '尺码']
 *
 * @package ESHOP
 */

$sku_combinations = isset($sku_combinations) ? $sku_combinations : [];
$price_fields = isset($price_fields) ? $price_fields : [];
$sku_data = isset($sku_data) ? $sku_data : [];
$spec_names = isset($spec_names) ? $spec_names : [];
?>

<?php if (empty($sku_combinations)): ?>
<div class="em-sku-empty">
    请先选择规格值，系统将自动生成SKU组合
</div>
<?php else: ?>
<div style="overflow: auto;">
    <table class="layui-table" id="em-multi-sku-table" lay-skin="line">
        <colgroup>
            <?php foreach ($spec_names as $name): ?>
            <col width="100">
            <?php endforeach; ?>
            <?php foreach ($price_fields as $field): ?>
            <col width="120">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr>
                <?php foreach ($spec_names as $name): ?>
                <th><?= htmlspecialchars($name) ?></th>
                <?php endforeach; ?>
                <?php foreach ($price_fields as $field): ?>
                <th>
                    <?= htmlspecialchars($field['label']) ?>
                    <?php if ($field['name'] !== 'sales'): ?>
                    <i class="layui-icon layui-icon-edit em-batch-fill" data-field="<?= htmlspecialchars($field['name']) ?>" title="批量填充"></i>
                    <?php endif; ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sku_combinations as $combo):
                $sku_key = $combo['sku'];
                $current_sku_data = isset($sku_data[$sku_key]) ? $sku_data[$sku_key] : [];
            ?>
            <tr data-sku="<?= htmlspecialchars($sku_key) ?>">
                <?php foreach ($combo['values'] as $val): ?>
                <td><?= htmlspecialchars($val) ?></td>
                <?php endforeach; ?>

                <?php foreach ($price_fields as $field):
                    $field_name = $field['name'];

                    // 处理会员价格字段
                    if (isset($field['member_id'])) {
                        $value = isset($current_sku_data['member_' . $field['member_id']])
                            ? $current_sku_data['member_' . $field['member_id']]
                            : '';
                        $input_name = 'config[tier_price][' . $field['member_id'] . '][' . $sku_key . ']';
                    } else {
                        $value = isset($current_sku_data[$field_name]) ? $current_sku_data[$field_name] : '';
                        $input_name = 'skus[' . $sku_key . '][' . $field_name . ']';
                    }

                    $input_type = isset($field['type']) ? $field['type'] : 'text';
                ?>
                <td>
                    <input type="<?= $input_type ?>"
                           name="<?= $input_name ?>"
                           class="layui-input"
                           value="<?= htmlspecialchars($value) ?>"
                           data-field="<?= htmlspecialchars($field_name) ?>">
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
