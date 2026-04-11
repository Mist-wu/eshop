<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    a{
        color: #16baaa;
    }
    a:hover{
        text-decoration: underline;
    }
</style>


<form class="layui-form " action="order.php?action=deliver_ajax" id="form">
    <div style="padding: 25px;" id="open-box">
        <p style="padding-bottom: 10px;">确认发货后，虚拟服务订单流程结束</p>
        <textarea id="remark-text" name="remark" placeholder="请填写内容..." class="layui-textarea">服务已完成</textarea>
        <div class="quick-remarks mt-3" style="margin-top: 5px;">
            <span>快速填写：</span>
            <a href="javascript:;" class="quick-remark" data-text="服务已完成">服务已完成</a> |
            <a href="javascript:;" class="quick-remark" data-text="问题已解决">问题已解决</a>
        </div>


        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input type="hidden" value="<?= $order_id ?>" name="id"/>
    </div>
    <div style="width: 100%; height: 50px;"></div>
    <div class="" id="form-btn">
        <div class="layui-input-block" style="margin: 0 auto;">
            <button type="submit" class="layui-btn" lay-submit lay-filter="submit">确认发货</button>
            <button type="reset" class="layui-btn layui-btn">重置</button>
        </div>
    </div>
</form>



<script>

    // 快速填写点击事件
    $(document).on('click', '.quick-remark', function() {
        const text = $(this).data('text');
        $('#remark-text').val(text).focus();
    });


    layui.use(['table'], function(){
        var $ = layui.$;
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

                    if(e.code == 400){
                        return layer.msg(e.msg)
                    }
                    parent.layer.close('deliver')
                    parent.layer.msg('操作成功');
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
