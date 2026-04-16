<?php defined('EM_ROOT') || exit('access denied!'); ?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: #f0f2f5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .register-container {
        width: 100%;
        max-width: 900px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-wrap: wrap;
    }

    .register-banner {
        flex: 1;
        min-width: 300px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        padding: 40px;
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .register-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('../content/static/img/reg-bg.jpg') no-repeat center;
        background-size: cover;
        opacity: 0.2;
        mix-blend-mode: overlay;
    }

    .banner-content {
        position: relative;
        z-index: 1;
    }

    .register-form {
        flex: 1;
        min-width: 300px;
        padding: 40px;
    }

    .form-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 30px;
        color: #333;
        position: relative;
        padding-bottom: 15px;
    }

    .form-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: #3498db;
        border-radius: 3px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .layui-form-label {
        width: 45px;
        padding: 9px 0;
        text-align: center;
        color: #666;
    }

    .layui-input-block {
        margin-left: 45px;
    }

    .layui-input, .layui-btn {
        border-radius: 6px;
        height: 44px;
    }

    .layui-btn {
        width: 100%;
        font-size: 16px;
        background: #3498db;
        border-color: #3498db;
        transition: all 0.3s;
    }

    .layui-btn:hover {
        background: #2980b9;
        border-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    .register-toggle {
        display: flex;
        margin-bottom: 25px;
        border: 1px solid #e6e6e6;
        border-radius: 6px;
        overflow: hidden;
    }

    .toggle-item {
        flex: 1;
        padding: 10px 0;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
    }

    .toggle-item.active {
        background: #3498db;
        color: #fff;
    }

    .agreement {
        margin: 20px 0;
        font-size: 12px;
        color: #666;
        text-align: center;
    }

    .agreement a {
        color: #3498db;
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .login-link a {
        color: #3498db;
        margin-left: 5px;
    }

    .captcha-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .captcha-group .layui-input {
        flex: 1;
    }

    .code-btn {
        width: 148px;
        flex: 0 0 148px;
        font-size: 14px;
    }

    .code-btn:hover {
        transform: none;
    }

    .send-btn-resp {
        display: none;
        margin-top: 8px;
        font-size: 12px;
        color: #ff5722;
    }

    @media (max-width: 768px) {
        .register-container {
            flex-direction: column;
        }

        .register-banner {
            padding: 30px 20px;
            text-align: center;
        }

        .register-form {
            padding: 30px 20px;
        }

        .form-title {
            font-size: 20px;
        }
    }
    .display-hide{
        display: none;
    }
</style>
<?php doAction('user_register') ?>
<div class="register-container">
    <!-- 左侧横幅 -->
    <div class="register-banner">
        <div class="banner-content">
            <h2 style="font-size: 28px; margin-bottom: 15px;">欢迎加入我们</h2>
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                创建账号，开启全新体验。我们提供安全、便捷的服务，让您的使用更加舒心。
            </p>
            <div style="margin-top: 30px;">
                <i class="fa fa-shield" style="font-size: 40px; margin-bottom: 15px;"></i>
                <p>安全加密技术保障您的信息安全</p>
            </div>
        </div>
    </div>

    <!-- 右侧表单 -->
    <div class="register-form">
        <h2 class="form-title">账号注册</h2>

        <!-- 注册方式切换 -->
        <?php if(Option::get('register_email_switch') == 'y' && Option::get('register_tel_switch') == 'y'): ?>
        <div class="register-toggle">
            <div class="toggle-item <?= $default_type === 'tel' ? 'active' : '' ?>" data-type="tel">手机号码注册</div>
            <div class="toggle-item <?= $default_type === 'email' ? 'active' : '' ?>" data-type="email">邮箱注册</div>
        </div>
        <?php endif; ?>

        <form class="layui-form" lay-filter="registerForm">
            <!-- 手机号码输入框 -->
            <?php if(Option::get('register_tel_switch') == 'y'): ?>
            <div class="form-group phone-field <?= $default_type !== 'tel' ? 'display-hide' : '' ?>">
                <label class="layui-form-label">
                    <i class="fa fa-phone"></i>
                </label>
                <div class="layui-input-block">
                    <input type="tel" name="tel" id="register-tel" placeholder="请输入手机号码" autocomplete="off" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <!-- 邮箱输入框（默认隐藏） -->
            <?php if(Option::get('register_email_switch') == 'y'): ?>
            <div class="form-group email-field <?= $default_type !== 'email' ? 'display-hide' : '' ?>">
                <label class="layui-form-label">
                    <i class="fa fa-envelope"></i>
                </label>
                <div class="layui-input-block">
                    <input type="text" name="email" id="register-mail" placeholder="请输入邮箱地址" autocomplete="off" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <?php if ($email_code && Option::get('register_email_switch') == 'y'): ?>
            <div class="form-group email-code-field <?= $default_type !== 'email' ? 'display-hide' : '' ?>">
                <div class="layui-input-block">
                    <div class="captcha-group">
                        <input type="text" name="mail_code" id="mail_code" placeholder="请输入邮件验证码" autocomplete="off" class="layui-input">
                        <button type="button" class="layui-btn code-btn" id="send-btn">发送验证码</button>
                    </div>
                    <div id="send-btn-resp" class="send-btn-resp"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 密码 -->
            <div class="form-group">
                <label class="layui-form-label">
                    <i class="fa fa-lock"></i>
                </label>
                <div class="layui-input-block">
                    <input type="password" name="password" placeholder="请设置密码（6-16位）" autocomplete="off" class="layui-input">
                </div>
            </div>

            <!-- 确认密码 -->
            <div class="form-group">
                <label class="layui-form-label">
                    <i class="fa fa-repeat"></i>
                </label>
                <div class="layui-input-block">
                    <input type="password" name="repassword" placeholder="请再次输入密码" autocomplete="off" class="layui-input">
                </div>
            </div>

            <?php doAction('user_register_remember') ?>

            <!-- 同意协议 -->
            <div class="agreement">
                <input type="checkbox" name="agreement" lay-skin="primary" checked>
                <label>我已阅读并同意<a href="javascript:;">《用户服务协议》</a>和<a href="javascript:;">《隐私政策》</a></label>
            </div>

            <input type="hidden" name="type" value="<?= $default_type ?>" id="type" />

            <!-- 注册按钮 -->
            <button class="layui-btn" lay-submit lay-filter="register">立即注册</button>

            <!-- 已有账号 -->
            <div class="login-link">
                已有账号？<a href="account.php?action=signin">立即登录</a>
            </div>
        </form>
    </div>
</div>

</div>

<script>
    layui.use(['form', 'layer'], function() {
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.$;
        var emailCodeEnabled = <?= $email_code ? 'true' : 'false' ?>;

        function syncRegisterType() {
            var type = $('#type').val();
            var isEmail = type === 'email';

            $('.phone-field').toggle(!isEmail);
            $('.email-field').toggle(isEmail);
            $('.email-code-field').toggle(isEmail && emailCodeEnabled);

            $('input[name="tel"]').prop('required', !isEmail);
            $('input[name="email"]').prop('required', isEmail);
            $('input[name="mail_code"]').prop('required', isEmail && emailCodeEnabled);

            if (!isEmail) {
                $('#send-btn-resp').hide().text('');
                $('#mail_code').val('');
            }
        }

        // 监听注册提交
        form.on('submit(register)', function(data) {
            if (data.field.type === 'email' && emailCodeEnabled && !$.trim(data.field.mail_code || '')) {
                layer.msg('请输入邮件验证码');
                return false;
            }

            layer.load(2);
            var url = "./account.php?action=dosignup";

            var field = data.field; // 获取表单字段值

            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 0){
                        layer.msg('注册成功，正在跳转到登录页');
                        location.href = "account.php?action=signin";
                    }else{
                        <?php doAction('user_register_error') ?>
                        layer.msg(e.msg);
                    }
                },
                error: function (xhr) {
                    layer.msg(JSON.parse(xhr.responseText).msg);
                },
                complete: function(xhr, textStatus) {
                    layer.closeAll('loading');
                }
            });


            return false; // 阻止表单跳转
        });

        // 注册方式切换
        $('.toggle-item').click(function() {
            var type = $(this).data('type');
            $(this).addClass('active').siblings().removeClass('active');
            $('#type').val(type);
            syncRegisterType();
        });

        $('#send-btn').click(function() {
            var sendBtn = $(this);
            var sendBtnResp = $('#send-btn-resp');
            var email = $.trim($('#register-mail').val());

            if (!email) {
                layer.tips('请输入邮箱地址', '#register-mail', {
                    tips: [2, '#ff5722'],
                    time: 2000
                });
                return;
            }

            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
                layer.tips('请输入正确的邮箱地址', '#register-mail', {
                    tips: [2, '#ff5722'],
                    time: 2000
                });
                return;
            }

            sendBtnResp.hide().text('');
            sendBtn.prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: './account.php?action=send_email_code',
                data: {
                    mail: email
                },
                dataType: 'json',
                success: function () {
                    var seconds = 60;
                    layer.msg('验证码已发送，请注意查收', {icon: 1, time: 2000});
                    sendBtn.text('已发送 ' + seconds + 's');
                    var countdownInterval = setInterval(function() {
                        seconds--;
                        if (seconds <= 0) {
                            clearInterval(countdownInterval);
                            sendBtn.text('发送验证码');
                            sendBtn.prop('disabled', false);
                        } else {
                            sendBtn.text('已发送 ' + seconds + 's');
                        }
                    }, 1000);
                },
                error: function (xhr) {
                    var msg = '发送邮件失败';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }
                    sendBtnResp.text(msg).show();
                    sendBtn.prop('disabled', false);
                }
            });
        });

        syncRegisterType();
    });
</script>
</body>
</html>
