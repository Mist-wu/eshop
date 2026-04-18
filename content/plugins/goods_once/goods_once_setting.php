<?php
defined('EM_ROOT') || exit('access denied!');

function plugin_setting_view() {
    $plugin_storage = Storage::getInstance('goods_once');
    $deliver_order = $plugin_storage->getValue('deliver_order');
    $deliver_order = empty($deliver_order) ? 'old' : $deliver_order;
    ?>

    <form class="layui-form" id="form" method="post" action="./plugin.php?plugin=goods_once&action=setting">
        <div style="padding: 25px;" id="open-box">

            <blockquote class="layui-elem-quote">
                <span>发货顺序设置</span><hr />
                请选择独立卡密发货时的库存发放顺序。
            </blockquote>

            <div class="form-section">
                <div class="layui-form-item">
                    <label class="layui-form-label">发货顺序</label>
                    <div class="layui-input-block">
                        <input type="radio" name="deliver_order" value="new" title="新卡优先" <?= $deliver_order === 'new' ? 'checked' : '' ?>>
                        <input type="radio" name="deliver_order" value="old" title="旧卡优先" <?= $deliver_order === 'old' ? 'checked' : '' ?>>
                        <input type="radio" name="deliver_order" value="rand" title="随机发卡" <?= $deliver_order === 'rand' ? 'checked' : '' ?>>
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
                        parent.layer.close('edit');
                        parent.layer.msg('已保存配置');
                        // window.parent.table.reload();
                    },
                    error: function (xhr) {
                        layer.msg(JSON.parse(xhr.responseText).msg);
                    }
                });
                return false; // 阻止默认 form 跳转
            });
        });

        var maxHeight = $(window.parent).innerHeight() * 0.75;
        $("#open-box").css({
            "max-height": maxHeight + "px",
            "overflow-y": "auto"
        });
    </script>

<?php }

function plugin_setting() {
    $deliver_order = Input::postStrVar('deliver_order');
    $allowed = ['new', 'old', 'rand'];
    if (!in_array($deliver_order, $allowed, true)) {
        $deliver_order = 'old';
    }

    $plugin_storage = Storage::getInstance('goods_once');
    $plugin_storage->setValue('deliver_order', $deliver_order);
    Output::ok();
}
