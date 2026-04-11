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
    }

    .captcha-group .layui-input-block {
        flex: 2;
        margin-right: 10px;
    }

    .captcha-img {
        flex: 1;
        height: 44px;
        background: #f2f2f2;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 12px;
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
            <div class="toggle-item active" data-type="phone">手机号码注册</div>
            <div class="toggle-item" data-type="email">邮箱注册</div>
        </div>
        <?php endif; ?>

        <form class="layui-form" lay-filter="registerForm">
            <!-- 手机号码输入框 -->
            <?php if(Option::get('register_tel_switch') == 'y'): ?>
            <div class="form-group phone-field">
                <label class="layui-form-label">
                    <i class="fa fa-phone"></i>
                </label>
                <div class="layui-input-block">
                    <input type="tel" name="tel" placeholder="请输入手机号码" autocomplete="off" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <!-- 邮箱输入框（默认隐藏） -->
            <?php if(Option::get('register_email_switch') == 'y'): ?>
            <div class="form-group email-field <?= Option::get('register_tel_switch') == 'y' ? 'display-hide' : '' ?>">
                <label class="layui-form-label">
                    <i class="fa fa-envelope"></i>
                </label>
                <div class="layui-input-block">
                    <input type="text" name="email" placeholder="请输入邮箱地址" autocomplete="off" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <!-- 验证码 -->
            <div class="form-group captcha-group" style="display: none;">
                <div class="layui-input-block">
                    <input type="text" name="code" placeholder="请输入验证码" autocomplete="off" class="layui-input">
                </div>
                <div class="captcha-img" id="getCode">获取验证码</div>
            </div>

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

        // 监听注册提交
        form.on('submit(register)', function(data) {
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

            if (type === 'phone') {
                $('#type').val('tel');
                $('.phone-field').show();
                $('.email-field').hide();
            } else {
                $('#type').val('email');
                $('.phone-field').hide();
                $('.email-field').show();
            }
        });


        // 验证码倒计时
        var countdown = 60;
        $('#getCode').click(function() {
            var $this = $(this);
            var isPhone = $('.toggle-item[data-type="phone"]').hasClass('active');
            var account = isPhone ? $('input[name="phone"]').val() : $('input[name="email"]').val();

            // 验证账号
            if (!account) {
                layer.tips(isPhone ? '请输入手机号码' : '请输入邮箱地址', isPhone ? 'input[name="phone"]' : 'input[name="email"]', {
                    tips: [2, '#ff5722'],
                    time: 2000
                });
                return;
            }

            // 验证格式
            if (isPhone && !/^1[3-9]\d{9}$/.test(account)) {
                layer.tips('请输入正确的手机号码', 'input[name="phone"]', {
                    tips: [2, '#ff5722'],
                    time: 2000
                });
                return;
            }

            if (!isPhone && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(account)) {
                layer.tips('请输入正确的邮箱地址', 'input[name="email"]', {
                    tips: [2, '#ff5722'],
                    time: 2000
                });
                return;
            }

            // 倒计时逻辑
            var timer = setInterval(function() {
                if (countdown <= 0) {
                    clearInterval(timer);
                    $this.text('重新获取');
                    $this.css({
                        'background': '#f2f2f2',
                        'color': '#999'
                    });
                    countdown = 60;
                } else {
                    $this.text('重新发送(' + countdown + ')');
                    $this.css({
                        'background': '#e6e6e6',
                        'color': '#666'
                    });
                    countdown--;
                }
            }, 1000);

            // 模拟发送验证码
            layer.msg('验证码已发送，请注意查收', {icon: 1, time: 2000});
        });


    });
</script>

</body>
</html>
<script>
    // send mail code
    $(function () {
        $('#checkcode').click(function () {
            var timestamp = new Date().getTime();
            $(this).attr("src", "../include/lib/checkcode.php?" + timestamp);
        });

        $('#send-btn').click(function () {
            const email = $('#mail').val();
            const sendBtn = $(this);
            const sendBtnResp = $('#send-btn-resp');
            sendBtnResp.html('')
            sendBtn.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: './account.php?action=send_email_code',
                data: {
                    mail: email
                },
                success: function (response) {
                    // 发送邮件成功后，启动倒计时
                    let seconds = 60;
                    // 启动倒计时
                    const countdownInterval = setInterval(() => {
                        seconds--;
                        if (seconds <= 0) {
                            clearInterval(countdownInterval);
                            sendBtn.html('发送邮件验证码');
                            sendBtn.prop('disabled', false);
                        } else {
                            sendBtn.html('已发送,请查收邮件 ' + seconds + '秒');
                        }
                    }, 1000);
                },
                error: function (data) {
                    sendBtnResp.html(data.responseJSON.msg).addClass('text-danger').show()
                    sendBtn.prop('disabled', false);
                }
            });
        });
    });
</script>
