<?php
/**
 * 规格选择表格模板
 * 渲染规格值勾选复选框
 *
 * 所需变量:
 *   $specs - 规格数据数组 [{id, title, options: [{id, title}], value: [已选中的ID]}]
 *
 * @package EMSHOP
 */

$specs = isset($specs) ? $specs : [];
?>

<?php if (empty($specs)): ?>
<div class="em-sku-empty">
    当前规格模板没有规格属性
</div>
<?php else: ?>
<table class="layui-table" id="em-spec-select-table" lay-skin="line">
    <colgroup>
        <col width="120">
        <col>
    </colgroup>
    <thead>
        <tr>
            <th>规格名称</th>
            <th>规格值 (勾选需要的规格)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($specs as $spec): ?>
        <tr data-spec-id="<?= $spec['id'] ?>">
            <td><strong><?= htmlspecialchars($spec['title']) ?></strong></td>
            <td>
                <?php if (empty($spec['options'])): ?>
                <span style="color: #999;">暂无规格值</span>
                <?php else: ?>
                <div class="layui-form">
                    <?php foreach ($spec['options'] as $opt):
                        $checked = in_array($opt['id'], $spec['value']) ? 'checked' : '';
                    ?>
                    <input type="checkbox"
                           name="spec_values[]"
                           value="<?= $opt['id'] ?>"
                           data-spec-id="<?= $spec['id'] ?>"
                           title="<?= htmlspecialchars($opt['title']) ?>"
                           lay-filter="em-spec-checkbox"
                           <?= $checked ?>>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
