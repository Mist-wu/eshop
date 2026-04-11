<?php
defined('EM_ROOT') || exit('access denied!');


function plugin_setting_view() {
    $plugin_storage = Storage::getInstance('tips');
    $demo = $plugin_storage->getValue('demo');
    ?>

    <form class="layui-form" id="form" method="post" action="./plugin.php?plugin=tips&action=setting">

        <div style="padding: 25px;" id="open-box">

            <blockquote class="layui-elem-quote">
                这是世界上第一个EMSHOP插件。此处配置作为演示，也可用于开发使用
            </blockquote>



            <!-- 基本信息设置 -->
            <div class="form-section">

                <div class="layui-form-item">
                    <label class="layui-form-label">演示配置</label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="demo" value="<?= $demo ?>" placeholder="">
                    </div>
                </div>



            </div>

        </div>

        <div style="width: 100%; height: 50px;"></div>
        <div class="" id="form-btn">
            <div class="layui-input-block" style="margin: 0 auto;">
                <button type="submit" class="layui-btn" lay-submit lay-filter="submit">保存配置</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
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
                        if(e.code == 400){
                            return layer.msg(e.msg);
                        }
                        parent.layer.close('edit')
                        parent.layer.msg('已保存配置');
                        // window.parent.table.reload();
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
<?php }

function plugin_setting() {

    $demo = Input::postStrVar('demo');

    $plugin_storage = Storage::getInstance('tips');
    $plugin_storage->setValue('demo', $demo);
    Output::ok();
}
