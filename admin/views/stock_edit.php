<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    #form-btn{
        background: #eee;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px;
        line-height: 50px;
        margin: 0 auto;
        text-align: center;
    }
    body{
        overflow: hidden;
    }
</style>


<form class="layui-form " action="stock.php?action=edit_ajax" id="form">
    <div style="padding: 25px;" id="open-box">



        <?php if($goods['type'] == 'duli' || $goods['type'] == 'guding'): ?>
        <div class="layui-form-item">
            <label class="layui-form-label">卡密内容</label>
            <div class="layui-input-block">
<!--                <input type="text" name="content" class="layui-input" value="--><?php //= $stock['content'] ?><!--" />-->
                <textarea rows="10" class="layui-textarea" name="content"><?= $stock['content'] ?></textarea>
            </div>
        </div>
        <?php endif; ?>
        <?php if($goods['type'] == 'guding' || $goods['type'] == 'xuni'): ?>
        <div class="layui-form-item">
            <label class="layui-form-label">可用数量</label>
            <div class="layui-input-block">
                <input type="text" name="quantity" class="layui-input" value="<?= $skus['stock'] ?>" />
            </div>
        </div>
        <?php endif; ?>
        <?php if($goods['type'] == 'duli'): ?>
        <input type="hidden" value="<?= $skus['stock'] ?>" name="quantity"/>
        <?php endif; ?>
        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input type="hidden" value="<?= $stock_id ?>" name="stock_id"/>

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
        form.on('submit(submit)', function(data){
            var field = data.field; // 获取表单全部字段值
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    parent.layer.close('edit')
                    parent.layer.msg('操作成功');
                    window.parent.ws_table.reload();
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                }
            });
            return false; // 阻止默认 form 跳转
        });



    })
    var maxHeight = $(window.parent).innerHeight() * 0.75;
    var minHeight = $(window.parent).innerHeight() * 0.5;



    // 2. 为 #open-box 设置 max-height，同时添加溢出滚动
    $("#open-box").css({
        "max-height": maxHeight + "px", // 单位必须加 px
        "min-height": minHeight + "px", // 单位必须加 px
        "overflow-y": "auto" // 内容超过 max-height 时显示垂直滚动条
    });
</script>
