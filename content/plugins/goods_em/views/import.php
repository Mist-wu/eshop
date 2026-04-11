<?php
/**
 * EMSHOP 同系统对接 - 商品导入
 */

defined('EM_ROOT') || exit('access denied!');

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    include View::getAdmView('open_head');
}

// 获取商品分类
$sortModel = new Sort_Model();
$sorts = $sortModel->getGoodsSortForHome();

// d($sorts);die;

// 获取远程商品列表
$api = EmApi::fromSite($site);
$remoteData = $api->getItems();
$categories = $remoteData['category'] ?? [];

// d($categories);die;

// 获取已导入的商品ID
$db = Database::getInstance();
$importedRows = $db->fetch_all("SELECT remote_goods_id FROM " . DB_PREFIX . "em_goods WHERE site_id = " . (int)$site['id']);
$importedIds = array_column($importedRows ?: [], 'remote_goods_id');
?>
<style>
    .em-import-banner {
        background: linear-gradient(135deg, #4C7D71, #6BA596);
        color: #fff;
        padding: 14px 18px;
        border-radius: 12px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }
    .em-import-banner strong {
        font-weight: 600;
    }
    .em-form-item {
        margin-bottom: 18px;
    }
    .em-form-item label {
        display: block;
        font-size: 14px;
        color: #374151;
        margin-bottom: 8px;
        font-weight: 500;
    }
    .em-form-item label em {
        color: #dc2626;
        margin-right: 6px;
        font-style: normal;
    }
    .em-form-item input[type="text"],
    .em-form-item input[type="number"],
    .em-form-item select {
        width: 100%;
        height: 42px;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background: #fff;
        box-sizing: border-box;
        transition: all 0.2s;
    }
    .em-form-item input:focus,
    .em-form-item select:focus {
        outline: none;
        border-color: #4C7D71;
        box-shadow: 0 0 0 3px rgba(76, 125, 113, 0.15);
    }
    .em-form-row {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .em-form-row .em-form-item {
        flex: 1;
        margin-bottom: 0;
    }
    .em-form-row .unit {
        font-size: 14px;
        color: #6b7280;
        min-width: 30px;
    }
    .em-hint {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
        padding-left: 2px;
    }
    .em-goods-select {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        max-height: 320px;
        overflow-y: auto;
    }
    .em-goods-header {
        padding: 10px 14px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        position: sticky;
        top: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        z-index: 1;
    }
    .em-goods-header label {
        margin: 0;
        font-size: 13px;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }
    .em-goods-count {
        font-size: 12px;
        color: #4C7D71;
        font-weight: 500;
    }
    .em-category-title {
        padding: 8px 14px;
        background: #f3f4f6;
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
        position: sticky;
        top: 40px;
    }
    .em-goods-item {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .em-goods-item:last-child {
        border-bottom: none;
    }
    .em-goods-item:hover {
        background: #f9fafb;
    }
    .em-goods-item.selected {
        background: #ecfdf5;
    }
    .em-goods-item input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #4C7D71;
        flex-shrink: 0;
    }
    .em-goods-info {
        flex: 1;
        min-width: 0;
    }
    .em-goods-name {
        font-size: 14px;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .em-goods-meta {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }
    .em-goods-tag {
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .em-goods-tag.auto {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .em-goods-tag.manual {
        background: #fef3c7;
        color: #b45309;
    }
    .em-goods-tag.imported {
        background: #e5e7eb;
        color: #6b7280;
    }
    .em-goods-item.imported {
        opacity: 0.6;
        background: #f9fafb;
    }
    .em-goods-item.imported:hover {
        background: #f9fafb;
    }
    .em-result-box {
        margin-top: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px;
        font-size: 13px;
        line-height: 1.8;
        max-height: 220px;
        overflow-y: auto;
    }
    .em-result-success {
        color: #059669;
    }
    .em-result-fail {
        color: #dc2626;
    }
    .em-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .em-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        line-height: 1.5;
        border: none;
        gap: 6px;
    }
    .em-btn-primary {
        background: linear-gradient(90deg, #4C7D71 0%, #6BA596 100%);
        color: #fff;
    }
    .em-btn-primary:hover {
        background: linear-gradient(90deg, #3D6A5F 0%, #5A9485 100%);
        box-shadow: 0 4px 12px rgba(76, 125, 113, 0.35);
        transform: translateY(-1px);
    }
    .em-btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    .em-btn-secondary:hover {
        background: #e5e7eb;
        border-color: #4C7D71;
        color: #4C7D71;
    }
</style>

<div class="em-import-banner">
    <strong><?php echo htmlspecialchars($site['sitename']); ?></strong>
    <span style="opacity: 0.9;">商品导入</span>
</div>

<form id="emImportForm">
    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">

    <div class="em-form-item">
        <label><em>*</em>本站分类</label>
        <select name="sort_id">
            <option value="">请选择分类</option>
            <?php foreach ($sorts as $sort): ?>
            <option value="<?php echo $sort['sort_id']; ?>"><?php echo htmlspecialchars($sort['sortname']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="em-form-item">
        <label><em>*</em>加价方式</label>
        <div class="em-form-row">
            <div class="em-form-item" style="max-width:150px;">
                <select name="raise_type" id="raiseType">
                    <option value="percent">按比例</option>
                    <option value="fixed">固定金额</option>
                </select>
            </div>
            <div class="em-form-item" style="max-width:120px;">
                <input type="number" name="raise_value" value="10" step="0.01" min="0">
            </div>
            <div class="unit" id="raiseUnit">%</div>
        </div>
        <div class="em-hint">在远程商品价格基础上加价销售</div>
    </div>

    <div class="em-form-item">
        <label><em>*</em>选择商品</label>
        <?php if (empty($categories)): ?>
            <div class="em-hint">暂无可导入的商品</div>
        <?php else: ?>
            <div class="em-goods-select" id="goodsSelectBox">
                <div class="em-goods-header">
                    <label><input type="checkbox" id="selectAll"> 全选</label>
                    <span class="em-goods-count">已选 <span id="selectedCount">0</span> 个</span>
                </div>
                <?php foreach ($categories as $category): ?>
                    <div class="em-category-title"><?php echo htmlspecialchars($category['name']); ?></div>
                    <?php foreach ($category['commodity'] ?? [] as $goods): ?>
                        <?php
                        $isImported = in_array($goods['id'], $importedIds);
                        $deliveryWay = (int)($goods['delivery_way'] ?? 0);
                        $tagClass = $deliveryWay === 1 ? 'manual' : 'auto';
                        $tagText = $deliveryWay === 1 ? '人工发货' : '自动发货';
                        ?>
                        <div class="em-goods-item<?php echo $isImported ? ' imported' : ''; ?>" data-id="<?php echo $goods['id']; ?>" data-imported="<?php echo $isImported ? 1 : 0; ?>">
                            <input type="checkbox" name="goods_ids[]" value="<?php echo $goods['id']; ?>" <?php echo $isImported ? 'disabled' : ''; ?>>
                            <div class="em-goods-info">
                                <div class="em-goods-name" title="<?php echo htmlspecialchars($goods['name']); ?>"><?php echo htmlspecialchars($goods['name']); ?></div>
                                <div class="em-goods-meta">ID：<?php echo (int)$goods['id']; ?></div>
                            </div>
                            <?php if ($isImported): ?>
                                <span class="em-goods-tag imported">已导入</span>
                            <?php endif; ?>
                            <span class="em-goods-tag <?php echo $tagClass; ?>"><?php echo $tagText; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="em-hint">对接商品导入后将自动生成本地库存与规格</div>
    </div>

    <div id="emResultBox" style="display:none;">
        <div style="font-weight:600;margin-bottom:8px;font-size:14px;color:#374151;">导入结果</div>
        <div class="em-result-box" id="emResultContent"></div>
    </div>
</form>

<div class="em-form-actions">
    <button type="button" class="em-btn em-btn-secondary" id="cancelImport">取消</button>
    <button type="button" class="em-btn em-btn-primary" id="submitImport">开始导入</button>
</div>

<script>
layui.use(['layer', 'jquery'], function(){
    var layer = layui.layer;
    var $ = layui.$;
    var apiBase = '<?= EM_URL ?>?plugin=goods_em';
    var isSubmitting = false;

    function updateSelectedCount() {
        if (!$('#goodsSelectBox').length) {
            return;
        }
        var selected = $('#goodsSelectBox input[name="goods_ids[]"]:checked').length;
        $('#selectedCount').text(selected);
        var total = $('#goodsSelectBox input[name="goods_ids[]"]:not(:disabled)').length;
        $('#selectAll').prop('checked', total > 0 && selected === total);
    }

    $('#raiseType').on('change', function(){
        $('#raiseUnit').text($(this).val() === 'percent' ? '%' : '元');
    });

    $(document).on('click', '.em-goods-item', function(e){
        if ($(e.target).is('input')) return;
        if ($(this).data('imported') == 1) return;
        var $cb = $(this).find('input[type="checkbox"]');
        $cb.prop('checked', !$cb.prop('checked'));
        $(this).toggleClass('selected', $cb.prop('checked'));
        updateSelectedCount();
    });

    $(document).on('change', '.em-goods-item input[type="checkbox"]', function(){
        $(this).closest('.em-goods-item').toggleClass('selected', this.checked);
        updateSelectedCount();
    });

    $('#selectAll').on('change', function(){
        var checked = this.checked;
        $('#goodsSelectBox input[name="goods_ids[]"]:not(:disabled)').each(function(){
            $(this).prop('checked', checked).closest('.em-goods-item').toggleClass('selected', checked);
        });
        updateSelectedCount();
    });

    $('#cancelImport').on('click', function(){
        if (typeof window.closeEmModal === 'function') {
            window.closeEmModal(false);
            return;
        }
        if (window.parent && typeof window.parent.closeEmModal === 'function') {
            window.parent.closeEmModal(false);
            return;
        }
        location.href = 'plugin.php?action=setting_page&plugin=goods_em&do=site';
    });

    $(document).off('click.emImport', '#submitImport').on('click.emImport', '#submitImport', function(){
        var checked = $('#goodsSelectBox input[name="goods_ids[]"]:checked');
        if (checked.length === 0) {
            layer.msg('请至少选择一个商品', {icon: 2});
            return;
        }

        var sortId = $('#emImportForm select[name="sort_id"]').val();
        if (!sortId) {
            layer.msg('请选择本站分类', {icon: 2});
            return;
        }

        if (isSubmitting) {
            return;
        }
        isSubmitting = true;

        var goodsIds = [];
        checked.each(function(){
            goodsIds.push($(this).val());
        });

        var data = {
            action: 'import_goods',
            site_id: $('#emImportForm input[name="site_id"]').val(),
            sort_id: sortId,
            raise_type: $('#raiseType').val(),
            raise_value: $('#emImportForm input[name="raise_value"]').val(),
            goods_ids: goodsIds
        };
        var loadIndex = layer.load(1, {content: '导入中，请稍候...'});

        $.post(apiBase, data, function(res){
            layer.close(loadIndex);
            isSubmitting = false;
            if (!res || res.code !== 0) {
                layer.msg(res.msg || '导入失败', {icon: 2});
                return;
            }

            var html = '<div>成功：<span class="em-result-success">' + (res.data?.success || 0) + '</span> 个</div>';
            html += '<div>失败：<span class="em-result-fail">' + (res.data?.fail || 0) + '</span> 个</div>';
            html += '<hr>';
            if (res.data?.messages) {
                res.data.messages.forEach(function(msg){
                    var cls = msg.indexOf('成功') > -1 ? 'em-result-success' : 'em-result-fail';
                    html += '<div class="' + cls + '">' + msg + '</div>';
                });
            }
            $('#emResultContent').html(html);
            $('#emResultBox').show();
            updateSelectedCount();
            if ((res.data?.success || 0) > 0) {
                layer.msg('导入完成', {icon: 1});
            }
        }, 'json').fail(function(){
            layer.close(loadIndex);
            isSubmitting = false;
            layer.msg('请求失败', {icon: 2});
        });
    });

    updateSelectedCount();
});
</script>

<?php
if (!$isAjax) {
    include View::getAdmView('open_foot');
}
?>
