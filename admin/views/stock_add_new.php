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


<form class="layui-form " action="stock.php?action=add_ajax" id="form">
    <div style="padding: 25px;" id="open-box">

        <?php if($goods['is_sku'] == 'y'): ?>
        <div class="layui-form-item">
            <label class="layui-form-label">请选择商品规格</label>
            <div class="layui-input-block">
                <select class="layui-input" name="sku">
                    <option value="">商品规格</option>
                    <?php foreach($sku_list as $val): ?>
                        <option value="<?= $val['sku'] ?>"><?= $val['sku_name'] ?> (<?= $val['stock_count'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <?php if($goods['type'] == 'xuni'): ?>
            <div class="layui-form-item">
                <label class="layui-form-label">可用数量</label>
                <div class="layui-input-block">
                    <input type="number" class="layui-input" name="quantity" />
                </div>
            </div>
        <?php endif; ?>

        <?php if($goods['type'] == 'guding'): ?>
            <div class="layui-form-item">
                <label class="layui-form-label">卡密内容</label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="content" />
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">可用数量</label>
                <div class="layui-input-block">
                    <input type="number" class="layui-input" name="quantity" />
                </div>
            </div>
        <?php endif; ?>
        <?php if($goods['type'] == 'duli'): ?>
        <div class="layui-form-item">
            <label class="layui-form-label">卡密内容 (添加多个请使用回车键换行)</label>
            <div class="layui-input-block">
                <textarea rows="8" class="layui-textarea" name="content"></textarea>
            </div>
        </div>
        <?php endif; ?>

        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
        <input type="hidden" value="<?= $goods_id ?>" name="goods_id"/>
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
        form.on('submit(submit)', function(data){
            var field = data.field; // 获取表单全部字段值
            var url = $('#form').attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    parent.layer.close('add_stock')
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
