<?php
/**
 * EMSHOP 同系统对接 - 站点表单
 */

defined('EM_ROOT') || exit('access denied!');

$isEdit = !empty($site);
$pageTitle = $isEdit ? '编辑站点' : '添加站点';
?>
<style>
    .em-form-item {
        margin-bottom: 20px;
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
    .em-form-item input[type="text"] {
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
    .em-form-item input[type="text"]:focus {
        outline: none;
        border-color: #4C7D71;
        box-shadow: 0 0 0 3px rgba(76, 125, 113, 0.15);
    }
    .em-form-item .hint {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
        padding-left: 2px;
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

<form id="siteForm">
    <input type="hidden" name="id" value="<?php echo $site['id'] ?? 0; ?>">

    <div class="em-form-item">
        <label><em>*</em>站点域名</label>
        <input type="text" name="domain" value="<?php echo htmlspecialchars($site['domain'] ?? ''); ?>" placeholder="https://example.com" required>
        <div class="hint">对方 EMSHOP 系统的完整域名</div>
    </div>

    <div class="em-form-item">
        <label><em>*</em>用户ID</label>
        <input type="text" name="app_id" value="<?php echo htmlspecialchars($site['app_id'] ?? ''); ?>" placeholder="对方系统的用户ID" required>
    </div>

    <div class="em-form-item">
        <label><em>*</em>用户Token</label>
        <input type="text" name="app_key" value="<?php echo htmlspecialchars($site['app_key'] ?? ''); ?>" placeholder="对方系统的用户Token" required>
        <div class="hint">在对方系统的个人中心获取 Token</div>
    </div>
</form>

<div class="em-form-actions">
    <button type="button" class="em-btn em-btn-secondary" id="cancelBtn">取消</button>
    <button type="button" class="em-btn em-btn-primary" id="saveBtn">保存</button>
</div>

<script>
layui.use([], function(){
    var layer = layui.layer;
    var $ = layui.$;
    var apiBase = '<?= EM_URL ?>?plugin=goods_em';
    var isSaving = false;

    function closeLayer(reload) {
        if (typeof window.closeEmModal === 'function') {
            window.closeEmModal(reload);
            return;
        }
        if (window.parent && typeof window.parent.closeEmModal === 'function') {
            window.parent.closeEmModal(reload);
            return;
        }
        location.href = 'plugin.php?action=setting_page&plugin=goods_em&do=site';
    }

    $('#cancelBtn').off('click.em').on('click.em', function(){
        closeLayer(false);
    });

    $('#saveBtn').off('click.em').on('click.em', function(){
        if (isSaving) {
            return;
        }

        var form = document.getElementById('siteForm');
        var domain = form.querySelector('input[name="domain"]').value;
        var appId = form.querySelector('input[name="app_id"]').value;
        var appKey = form.querySelector('input[name="app_key"]').value;

        if (!domain || !appId || !appKey) {
            layer.msg('请填写完整信息', {icon: 2});
            return;
        }

        var data = $('#siteForm').serialize() + '&action=site_save';
        var loadIndex = layer.load(1);
        isSaving = true;
        $('#saveBtn').prop('disabled', true).css('opacity', 0.7);

        $.post(apiBase, data, function(res){
            if(res.code === 0){
                layer.msg('保存成功', {icon: 1});
                setTimeout(function(){
                    closeLayer(true);
                }, 1000);
            } else {
                layer.msg(res.msg || '保存失败', {icon: 2});
            }
        }, 'json').always(function(){
            layer.close(loadIndex);
            isSaving = false;
            $('#saveBtn').prop('disabled', false).css('opacity', '');
        });
    });
});
</script>
