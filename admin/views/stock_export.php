<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    body{
        overflow: hidden;
    }
    #export_num_input, #export_time_input{
        display: none;
    }
</style>


<form class="layui-form " action="stock.php?action=export_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <div class="layui-form-item">
            <label class="layui-form-label">导出范围</label>
            <div class="layui-input-block">
                <input lay-filter="export_range" type="radio" name="export_range" value="" title="全部导出" checked>
            </div>
            <div class="layui-input-block">
                <input lay-filter="export_range" type="radio" name="export_range" value="num" title="导出指定数量">
            </div>
            <div class="layui-input-block">
                <input lay-filter="export_range" type="radio" name="export_range" value="time" title="按添加时间导出">
            </div>
        </div>
        <div class="layui-form-item" id="export_num_input">
            <label class="layui-form-label">导出卡密数量</label>
            <div class="layui-input-block">
                <input type="number" name="export_num" class="layui-input" value="">
            </div>
        </div>
        <div class="layui-form-item" id="export_time_input">
            <label class="layui-form-label">卡密添加时间</label>
            <div class="layui-input-inline">
                <input name="start_time" type="text" class="layui-input" id="start-laydate-type-datetime" placeholder="开始时间">
            </div>
            <div class="layui-input-inline">
                <input name="end_time" type="text" class="layui-input" id="end-laydate-type-datetime" placeholder="截止时间">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">导出后操作</label>
            <div class="layui-input-block">
                <input type="radio" name="is_delete" value="0" title="仅导出" checked>
            </div>
            <div class="layui-input-block">
                <input type="radio" name="is_delete" value="1" title="导出并删除">
            </div>
        </div>


        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input type="hidden" value="<?= $goods_id ?>" name="goods_id"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">执行</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>



<script>
    layui.use(['table'], function(){
        var $ = layui.$;
        var laydate = layui.laydate;
        var form = layui.form;
        form.on('submit(submit)', function(data){
            var field = data.field; // 获取表单全部字段值
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    parent.layer.close('export')
                    parent.layer.msg('操作成功');
                    window.open(e.data)
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });

        form.on('radio(export_range)', function(data){
            var elem = data.elem; // 获得 radio 原始 DOM 对象
            var value = elem.value; // 获得 radio 值
            var othis = data.othis; // 获得 radio 元素被替换后的 jQuery 对象
            if(value == ''){
                $('#export_num_input').hide();
                $('#export_time_input').hide();
            }
            if(value == 'num'){
                $('#export_num_input').show();
                $('#export_time_input').hide();
            }
            if(value == 'time'){
                $('#export_num_input').hide();
                $('#export_time_input').show();
            }
        });

        // 日期时间选择器
        laydate.render({
            elem: '#start-laydate-type-datetime',
            type: 'date'
        });
        laydate.render({
            elem: '#end-laydate-type-datetime',
            type: 'date'
        });

    })
    var maxHeight = $(window.parent).innerHeight() * 0.75;



    // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>
