<?php defined('EM_ROOT') || exit('access denied!'); ?>


<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li><a href="./setting.php">基础设置</a></li>
        <li><a href="./setting.php?action=shop">商城配置</a></li>
        <li class="layui-this"><a href="./setting.php?action=user">用户设置</a></li>
        <li><a href="./setting.php?action=seo">SEO设置</a></li>
        <li><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel">
    <div style="padding: 20px;">
        <form action="setting.php?action=user_save" method="post" name="setting_form" id="setting_form" class="layui-form">
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="login_switch" title="开启用户登录功能" <?= $login_switch == 'y' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="register_switch" title="开启用户注册功能" <?= $register_switch == 'y' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="register_email_switch" title="开启用户邮箱注册功能" <?= $register_email_switch == 'y' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="register_tel_switch" title="开启用户手机注册功能" <?= $register_tel_switch == 'y' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="login_email_switch" title="开启用户邮箱登录功能" <?= $login_email_switch == 'y' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="login_tel_switch" title="开启用户手机登录功能" <?= $login_tel_switch == 'y' ? 'checked' : '' ?>>
                </div>
            </div>


            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn" lay-submit lay-filter="demo1">保存设置</button>
                    <button type="reset" class="layui-btn layui-btn">重置</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    $(function () {
        $("#menu-system").attr('class', 'admin-menu-item has-list in');
        $("#menu-system .fa-angle-right").attr('class', 'admin-arrow fa fa-angle-right active');
        $("#menu-system > .submenu").css('display', 'block');
        $('#menu-setting > a').attr('class', 'menu-link active')

        // 提交表单
        $("#setting_form").submit(function (event) {
            event.preventDefault();
            submitForm("#setting_form");
        });
    });
</script>
