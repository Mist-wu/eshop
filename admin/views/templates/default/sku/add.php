<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
</style>


<form class="layui-form " action="?action=add_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">规格模板名称</label>
            <div class="layui-input-block">
                <input type="text" name="title" class="layui-input" value="">
            </div>
        </div>

        <blockquote class="layui-elem-quote layui-quote-nm" id="sku-div-0">
            <div class="layui-form-item">
                <label class="layui-form-label">规格名称与规格值</label>
                <div class="layui-input-group">
                    <input type="text" name="name[0]" placeholder="如：颜色、尺码" class="layui-input">
                    <div class="layui-input-suffix">
                        <span class="layui-btn layui-btn-blue add-value-btn" data-id="0">添加规格值</span>
                        <span class="layui-btn layui-btn-red del-attr-btn" data-id="0">删除规格</span>
                    </div>
                </div>
            </div>
        </blockquote>

        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">立即提交</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
            <span class="layui-btn layui-bg-purple" id="add-new-sku">添加新规格</span>
        </div>
    </div>
</form>


<script>
    layui.use(['table'], function() {
        var $ = layui.$;
        var form = layui.form;
        var upload = layui.upload;
        var element = layui.element;
        form.on('submit(submit)', function (data) {
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

    var elem_id = 1;

    $("body").on("click", ".add-value-btn",function(){
        var id = $(this).data('id');
        var html = `<div class="layui-inline" style="margin-top: 8px;">
                <div class="layui-input-inline">
                    <div class="layui-input-group">
                        <input type="text" name="value[${id}][]" placeholder="如：白色、黑色" class="layui-input">
                        <div class="layui-input-suffix">
                            <span class="layui-btn layui-btn-red del-sku-btn">删除</span>
                        </div>
                    </div>
                </div>
            </div>`;
        $('#sku-div-' + id).append(html);
    })

    $('#add-new-sku').click(function(){
        var html = `<blockquote class="layui-elem-quote layui-quote-nm" id="sku-div-${elem_id}">
            <div class="layui-form-item">
                <label class="layui-form-label">规格名称与规格值</label>
                <div class="layui-input-group">
                    <input type="text" name="name[${elem_id}]" placeholder="如：颜色、尺码" class="layui-input">
                        <div class="layui-input-suffix">
                            <span class="layui-btn layui-btn-blue add-value-btn" data-id="${elem_id}">添加规格值</span>
                            <span class="layui-btn layui-btn-red del-attr-btn" data-id="0">删除规格</span>
                        </div>
                </div>
            </div>
        </blockquote>`;
        elem_id++;
        $('#open-box').append(html)
    })

    $("body").on("click", ".del-sku-btn",function(){
        // 找到当前按钮的父级的父级的父级的父级节点并删除
        $(this).parent().parent().parent().parent().remove();
    });

    $("body").on("click", ".del-attr-btn",function(){
        // 找到当前按钮的父级的父级的父级的父级节点并删除
        $(this).parent().parent().parent().parent().remove();
    });



    var maxHeight = $(window.parent).innerHeight() * 0.75;
    // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>
