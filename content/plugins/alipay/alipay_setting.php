<?php
defined('EM_ROOT') || exit('access denied!');



function plugin_setting_view() {
    $plugin_storage = Storage::getInstance('alipay');
    $appid = $plugin_storage->getValue('appid');
    $public_key = $plugin_storage->getValue('public_key');
    $private_key = $plugin_storage->getValue('private_key');
    $dm = $plugin_storage->getValue('dm');
    $mb = $plugin_storage->getValue('mb');
    $pc = $plugin_storage->getValue('pc');


    $appid = empty($appid) ? '' : $appid;
    $public_key = empty($public_key) ? '' : $public_key;
    $private_key = empty($private_key) ? '' : $private_key;
    $dm = empty($dm) ? null : $dm;
    $mb = empty($mb) ? null : $mb;
    $pc = empty($pc) ? null : $pc;

    ?>

    <form class="layui-form" id="form" method="post" action="./plugin.php?plugin=alipay&action=setting">
        <div style="padding: 25px;" id="open-box">

            <blockquote class="layui-elem-quote">
                <span style="">注意事项</span><hr />
                ① 请根据实际情况开启3种支付方式。<br />
                ② 请勿开启您未在支付宝官方签约的支付方式。<br />
                ③ 手机端访问您的网站，优先调用手机网站支付，如未开启手机网站支付则调用当面付。电脑网站支付在手机端无法调用！<br />
                ④ 电脑端访问您的网站，优先调用电脑网站支付，如未开启电脑网站支付则调用当面付。手机网站支付在电脑端无法调用！
            </blockquote>

            <!-- 基本信息设置 -->
            <div class="form-section">

                <div class="layui-form-item">
                    <label class="layui-form-label">签约类型</label>
                    <div class="layui-input-block">
                        <input type="checkbox" value="1" name="dm" title="当面付" <?= $dm ? 'checked' : '' ?>>
                        <input type="checkbox" value="1" name="mb" title="手机网站支付" <?= $mb ? 'checked' : '' ?>>
                        <input type="checkbox" value="1" name="pc" title="电脑网站支付" <?= $pc ? 'checked' : '' ?>>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">APPID</label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="appid" value="<?= $appid ?>" placeholder="">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">支付宝公钥</label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="public_key" value="<?= $public_key ?>" placeholder="">
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">应用私钥</label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="private_key" value="<?= $private_key ?>" placeholder="">
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
                            return layer.msg(e.msg)
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
    $appid = Input::postStrVar('appid');
    $public_key = Input::postStrVar('public_key');
    $private_key = Input::postStrVar('private_key');
    $dm = Input::postStrVar('dm');
    $mb = Input::postStrVar('mb');
    $pc = Input::postStrVar('pc');

    $plugin_storage = Storage::getInstance('alipay');
    $plugin_storage->setValue('appid', $appid);
    $plugin_storage->setValue('public_key', $public_key);
    $plugin_storage->setValue('private_key', $private_key);
    $plugin_storage->setValue('dm', $dm);
    $plugin_storage->setValue('mb', $mb);
    $plugin_storage->setValue('pc', $pc);
    Output::ok();
}
