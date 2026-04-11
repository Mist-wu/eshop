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
    .sort-item { margin-bottom: 10px; }
    .sort-child { padding-left: 30px; }
</style>

<div class="navi-form-shell">
    <form class="layui-form navi-form" lay-filter="sort-form">
        <div class="navi-form-body">
            <div class="layui-form-item">
                <label class="layui-form-label">选择分类</label>
                <div class="layui-input-block">
                    <?php if(empty($sorts)): ?>
                        <div class="layui-text">暂无商品分类</div>
                    <?php else: ?>
                        <?php foreach ($sorts as $key => $value):
                            if ($value['pid'] != 0) {
                                continue;
                            }
                        ?>
                            <div class="sort-item">
                                <input type="checkbox" name="sort_ids[]" value="<?= $value['sid'] ?>" title="<?= $value['sortname'] ?>">
                            </div>
                            <?php
                            $children = $value['children'];
                            foreach ($children as $key):
                                $value = $sorts[$key];
                                ?>
                                <div class="sort-item sort-child">
                                    <input type="checkbox" name="sort_ids[]" value="<?= $value['sid'] ?>" title="<?= $value['sortname'] ?>">
                                </div>
                            <?php
                            endforeach;
                        endforeach;
                        ?>
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

        form.on('submit(submit)', function(data){
            var field = data.field;
            console.log('field:', field);
            // 检查是否有选中的分类
            var hasSelected = false;
            for(var key in field){
                if(key.indexOf('sort_ids[') === 0){
                    hasSelected = true;
                    break;
                }
            }
            if(!hasSelected){
                layer.msg('请至少选择一个分类');
                return false;
            }
            $.ajax({
                type: "POST",
                url: '?action=add_sort',
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
