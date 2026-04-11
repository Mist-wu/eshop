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
</style>

<div class="navi-form-shell">
    <form class="layui-form navi-form" lay-filter="custom-form">
        <div class="navi-form-body">
            <div class="layui-form-item">
                <label class="layui-form-label">导航名称</label>
                <div class="layui-input-block">
                    <input type="text" placeholder="请输入导航名称" name="naviname" class="layui-input" required lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">导航网址</label>
                <div class="layui-input-block">
                    <input type="text" placeholder="请输入导航网址" name="url" class="layui-input" required lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">打开方式</label>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="newtab" title="新窗口打开" lay-skin="tag">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">上级导航</label>
                <div class="layui-input-block">
                    <select name="pid">
                        <option value="">无上级导航</option>
                        <?php
                        foreach ($navis as $key => $value):
                            if ($value['type'] != Navi_Model::navitype_custom || $value['pid'] != 0) {
                                continue;
                            }
                            ?>
                            <option value="<?= $value['id'] ?>"><?= $value['naviname'] ?></option>
                        <?php endforeach ?>
                    </select>
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
            $.ajax({
                type: "POST",
                url: '?action=add_ajax',
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code != 0){
                        return layer.msg(e.msg);
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
