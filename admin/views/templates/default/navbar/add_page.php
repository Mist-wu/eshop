<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    html, body { height: 100%; }
    body { margin: 0; }
    .navi-form-shell { height: 100%; background: #f8fafb; }
    .navi-form { height: 100%; display: flex; flex-direction: column; }
    .navi-form-body { flex: 1; overflow-y: auto; padding: 16px; }
    .navi-form-footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e6e6e6;
        padding: 12px 16px;
        text-align: center;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }
    .page-item { margin-bottom: 10px; }
</style>

<div class="navi-form-shell">
    <form class="layui-form navi-form" lay-filter="page-form">
        <div class="navi-form-body">
            <div class="layui-form-item">
                <label class="layui-form-label">选择页面</label>
                <div class="layui-input-block">
                    <?php if(empty($pages)): ?>
                        <div class="layui-text">还没页面，<a href="javascript:;" id="go-add-page">新建页面</a></div>
                    <?php else: ?>
                        <?php foreach ($pages as $key => $value): ?>
                            <div class="page-item">
                                <input type="checkbox" name="pages[<?= $value['gid'] ?>]" value="<?= $value['title'] ?>" title="<?= $value['title'] ?>">
                            </div>
                        <?php endforeach ?>
                    <?php endif; ?>
                </div>
            </div>
            <input name="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        </div>
        <div class="navi-form-footer">
            <button type="submit" class="layui-btn layui-btn-green" lay-submit lay-filter="submit">保存</button>
            <button type="button" class="layui-btn" id="btn-cancel">取消</button>
        </div>
    </form>
</div>

<script>
    layui.use(['form', 'layer'], function(){
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.$;

        // 渲染表单
        form.render();

        $('#btn-cancel').on('click', function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        });

        $('#go-add-page').on('click', function(){
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
            parent.window.location.href = 'page.php';
        });

        form.on('submit(submit)', function(data){
            var field = data.field;
            var hasSelected = false;
            for(var key in field){
                if(key.indexOf('pages[') === 0){
                    hasSelected = true;
                    break;
                }
            }
            if(!hasSelected){
                layer.msg('请至少选择一个页面');
                return false;
            }
            $.ajax({
                type: "POST",
                url: '?action=add_page',
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code != 0){
                        layer.msg(e.msg);
                        return;
                    }
                    parent.layer.msg('添加成功');
                    parent.window.table.reload();
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                },
                error: function (xhr) {
                    var msg = '操作失败';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp && resp.msg) msg = resp.msg;
                    } catch (e) {}
                    layer.msg(msg);
                }
            });
            return false;
        });
    });
</script>
