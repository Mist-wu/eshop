<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    .setting-shop-container {
        background: #f8fafb;
        padding: 20px;
        min-height: calc(100vh - 100px);
    }

    .setting-shop-container .layui-form-item {
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: box-shadow 0.3s ease;
        margin-bottom: 0px;
    }

    .setting-shop-container .layui-form-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .setting-shop-container .layui-form-label {
        font-weight: 500;
        color: #374151;
    }

    .setting-shop-container .layui-input,
    .setting-shop-container .layui-textarea {
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .setting-shop-container .layui-input:focus,
    .setting-shop-container .layui-textarea:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .setting-shop-container .layui-elem-field {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: none;
        margin-bottom: 12px;
    }

    .setting-shop-container .layui-elem-field .layui-field-box {
        padding: 0;
    }

    .setting-shop-container .layui-elem-field legend {
        font-weight: 600;
        color: #374151;
        font-size: 15px;
    }

    .setting-shop-container .layui-elem-field .layui-form-item {
        box-shadow: none;
        background: transparent;
        padding: 12px 0 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .setting-shop-container .layui-elem-field .layui-form-item:last-child {
        border-bottom: none;
    }

    .setting-shop-container .layui-elem-field .layui-form-item:hover {
        box-shadow: none;
        background: transparent;
    }

    /* 游客查单模式区块样式 */
    .guest-query-section {
        background: #f9fafb;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .guest-query-section .layui-form-item {
        background: transparent;
    }

    .guest-query-section .layui-form-item:hover {
        background: transparent;
    }
</style>

<div class="layui-tabs" style="margin-bottom: 12px;" lay-options="{trigger: false}">
    <ul class="layui-tabs-header">
        <li><a href="./setting.php">基础设置</a></li>
        <li class="layui-this"><a href="./setting.php?action=shop">商城配置</a></li>
        <li><a href="./setting.php?action=user">用户设置</a></li>
        <li><a href="./setting.php?action=seo">SEO设置</a></li>
        <li><a href="./setting.php?action=mail">邮箱配置</a></li>
        <li><a href="./blogger.php">个人信息</a></li>
    </ul>
</div>
<div class="layui-panel setting-shop-container">
    <div>
        <form action="setting.php?action=shop_save" method="post" name="setting_form" id="setting_form" class="layui-form">

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="checkbox" value="y" name="coupon_switch" title="优惠券功能" <?= $coupon_switch == 'y' ? 'checked' : '' ?>>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">支付成功后跳转</label>
                <div class="layui-input-block">
                    <input type="radio" name="pay_redirect" value="kami" title="订单详情页" <?= $pay_redirect == 'kami' ? 'checked' : '' ?>>
                </div>
                <div class="layui-input-block">
                    <input type="radio" name="pay_redirect" value="list" title="订单列表" <?= $pay_redirect == 'list' ? 'checked' : '' ?>>
                </div>
            </div>

            <?php
                // 游客查单模式配置
                $guestQueryContactSwitch = $guest_query_contact_switch ?? 'n';
                $guestQueryContactType = $guest_query_contact_type ?? 'any';
                $guestQueryContactPlaceholderOrder = $guest_query_contact_placeholder_order ?? '请输入联系方式(用于订单查询)';
                $guestQueryContactPlaceholderQuery = $guest_query_contact_placeholder_query ?? '请输入您下单时填写的联系方式';
                $guestQueryPasswordSwitch = $guest_query_password_switch ?? 'n';
                $guestQueryPasswordPlaceholderOrder = $guest_query_password_placeholder_order ?? '请设置订单密码';
                $guestQueryPasswordPlaceholderQuery = $guest_query_password_placeholder_query ?? '请输入您设置的订单密码';
            ?>
            <fieldset class="layui-elem-field" style="margin: 12px 0 20px; border-radius: 6px;">
                <div class="layui-field-box">
                    <div style="padding: 12px 16px; background: #f0f9ff; border-left: 3px solid #0ea5e9; margin-bottom: 16px; border-radius: 4px;">
                        <div style="color: #0369a1; font-weight: 500; margin-bottom: 4px;">游客查单模式</div>
                        <div style="color: #64748b; font-size: 13px;">配置游客下单时需要输入的信息，用于后续查询订单</div>
                    </div>

                    <div class="guest-query-section">
                        <div class="layui-form-item">
                            <label class="layui-form-label">联系方式</label>
                            <div class="layui-input-block">
                                <input type="checkbox" value="y" name="config[guest_query_contact_switch]" lay-skin="switch" lay-text="开启|关闭" lay-filter="guest_query_contact_switch" <?= $guestQueryContactSwitch === 'y' ? 'checked' : '' ?>>
                            </div>
                            <div class="layui-form-mid layui-word-aux" style="display:block; margin-top:6px;">
                                开启后，游客下单时需要输入联系方式用于查询订单。
                            </div>
                        </div>

                        <div id="guest_query_contact_detail" style="display: <?= $guestQueryContactSwitch === 'y' ? 'block' : 'none' ?>;">
                            <div class="layui-form-item">
                                <label class="layui-form-label">联系方式类型</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="config[guest_query_contact_type]" value="any" title="任意" <?= $guestQueryContactType === 'any' ? 'checked' : '' ?>>
                                    <input type="radio" name="config[guest_query_contact_type]" value="qq" title="QQ" <?= $guestQueryContactType === 'qq' ? 'checked' : '' ?>>
                                    <input type="radio" name="config[guest_query_contact_type]" value="email" title="邮箱" <?= $guestQueryContactType === 'email' ? 'checked' : '' ?>>
                                    <input type="radio" name="config[guest_query_contact_type]" value="phone" title="手机号码" <?= $guestQueryContactType === 'phone' ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">下单页面提示</label>
                                <div class="layui-input-block">
                                    <input type="text" name="config[guest_query_contact_placeholder_order]" value="<?= $guestQueryContactPlaceholderOrder ?>" placeholder="请输入联系方式(用于订单查询)" class="layui-input">
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">查单页面提示</label>
                                <div class="layui-input-block">
                                    <input type="text" name="config[guest_query_contact_placeholder_query]" value="<?= $guestQueryContactPlaceholderQuery ?>" placeholder="请输入您下单时填写的联系方式" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guest-query-section">
                        <div class="layui-form-item">
                            <label class="layui-form-label">订单密码</label>
                        <div class="layui-input-block">
                            <input type="checkbox" value="y" name="config[guest_query_password_switch]" lay-skin="switch" lay-text="开启|关闭" lay-filter="guest_query_password_switch" <?= $guestQueryPasswordSwitch === 'y' ? 'checked' : '' ?>>
                        </div>
                        <div class="layui-form-mid layui-word-aux" style="display:block; margin-top:6px;">
                            开启后，游客下单时需要设置订单密码用于查询订单。
                        </div>
                    </div>

                    <div id="guest_query_password_detail" style="display: <?= $guestQueryPasswordSwitch === 'y' ? 'block' : 'none' ?>;">
                        <div class="layui-form-item">
                            <label class="layui-form-label">下单页面提示</label>
                            <div class="layui-input-block">
                                <input type="text" name="config[guest_query_password_placeholder_order]" value="<?= $guestQueryPasswordPlaceholderOrder ?>" placeholder="请设置订单密码" class="layui-input">
                            </div>
                        </div>

                        <div class="layui-form-item">
                            <label class="layui-form-label">查单页面提示</label>
                            <div class="layui-input-block">
                                <input type="text" name="config[guest_query_password_placeholder_query]" value="<?= $guestQueryPasswordPlaceholderQuery ?>" placeholder="请输入您设置的订单密码" class="layui-input">
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>



            <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="layui-btn layui-btn-green" lay-submit lay-filter="demo1">保存设置</button>
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

        // 游客查单模式联动显示
        layui.use('form', function(){
            var form = layui.form;

            // 联系方式开关联动
            form.on('switch(guest_query_contact_switch)', function(data){
                if(data.elem.checked){
                    $('#guest_query_contact_detail').slideDown(300);
                } else {
                    $('#guest_query_contact_detail').slideUp(300);
                }
            });

            // 订单密码开关联动
            form.on('switch(guest_query_password_switch)', function(data){
                if(data.elem.checked){
                    $('#guest_query_password_detail').slideDown(300);
                } else {
                    $('#guest_query_password_detail').slideUp(300);
                }
            });
        });
    });


</script>
