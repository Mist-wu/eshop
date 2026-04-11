<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
</style>


<form class="layui-form " action="?action=add_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">等级名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" class="layui-input" value="">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">折扣率(%)</label>
            <div class="layui-input-block">
                <input type="number" name="discount" class="layui-input" value="" placeholder="例如：90 表示9折">
            </div>
            <div class="layui-form-mid layui-word-aux" style="margin-left: 110px;">输入折扣百分比，如90表示按普通用户价格的90%计算</div>
        </div>
        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">立即提交</button>
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
                    parent.layer.close('add')
                    parent.layer.msg('添加成功');
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
