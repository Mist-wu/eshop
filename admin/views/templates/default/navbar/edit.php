<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
</style>

<form class="layui-form " action="?action=update" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">导航名称</label>
            <div class="layui-input-block">
                <input type="text" name="naviname" class="layui-input" value="<?= $naviname ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">导航网址</label>
            <div class="layui-input-block">
                <input type="text" name="url" class="layui-input" value="<?= $url ?>" <?= $conf_isdefault ?>>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="checkbox" name="newtab" value="y" <?= $conf_newtab ?>>
                <div lay-checkbox>新窗口打开</div>
            </div>
        </div>

        <?php if ($type == Navi_Model::navitype_custom && $pid != 0): ?>
            <div class="form-group">
                <label>父导航</label>
                <select name="pid" id="pid">
                    <option value="0">无</option>
                    <?php
                    foreach ($navis as $key => $value):
                        if ($value['type'] != Navi_Model::navitype_custom || $value['pid'] != 0) {
                            continue;
                        }
                        $flg = $value['id'] == $pid ? 'selected' : '';
                        ?>
                        <option value="<?= $value['id'] ?>" <?= $flg ?>><?= $value['naviname'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        <?php endif ?>

        <div class="layui-form-item">
            <label class="layui-form-label">排序</label>
            <div class="layui-input-block">
                <input type="number" name="taxis" class="layui-input" value="<?= $taxis ?>">
            </div>
        </div>

        <input type="hidden" value="<?= $naviId ?>" name="navid"/>
        <input type="hidden" value="<?= $isdefault ?>" name="isdefault"/>

    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">保存</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>

<script>
    layui.use(['table'], function(){
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;
        form.on('submit(submit)', function(data){
            var field = data.field; // 获取表单全部字段值
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    parent.layer.close('edit')
                    parent.layer.msg('编辑成功');
                    window.parent.table.reload();
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });




    })






    var maxHeight = $(window.parent).innerHeight() * 0.75;
    // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>