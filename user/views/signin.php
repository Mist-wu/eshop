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

    .login-container {
        width: 100%;
        max-width: 900px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-wrap: wrap;
    }

    .login-banner {
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

    .login-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('../content/static/img/login-bg.jpg') no-repeat center;
        background-size: cover;
        opacity: 0.2;
        mix-blend-mode: overlay;
    }

    .banner-content {
        position: relative;
        z-index: 1;
    }

    .login-form {
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

    .login-toggle {
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

    .form-options {
        display: flex;
        justify-content: space-between;
        margin: 10px 0 25px;
        font-size: 14px;
    }

    .form-options a {
        color: #3498db;
    }

    .register-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .register-link a {
        color: #3498db;
        margin-left: 5px;
    }

    .other-login {
        margin-top: 30px;
        text-align: center;
    }

    .other-login p {
        color: #999;
        font-size: 14px;
        margin-bottom: 15px;
        position: relative;
    }

    .other-login p::before,
    .other-login p::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 30%;
        height: 1px;
        background: #eee;
    }

    .other-login p::before {
        left: 0;
    }

    .other-login p::after {
        right: 0;
    }

    .login-icons {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .login-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .login-icon:hover {
        background: #3498db;
        color: #fff;
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
        }

        .login-banner {
            padding: 30px 20px;
            text-align: center;
        }

        .login-form {
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
<?php doAction('user_login') ?>
<div class="login-container">
    <!-- 左侧横幅 -->
    <div class="login-banner">
        <div class="banner-content">
            <h2 style="font-size: 28px; margin-bottom: 15px;">欢迎回来</h2>
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                请登录您的账号，继续享受我们提供的优质服务。安全、便捷，尽在掌握。
            </p>
            <div style="margin-top: 30px;">
                <i class="fa fa-bolt" style="font-size: 40px; margin-bottom: 15px;"></i>
                <p>快速登录，即刻体验核心功能</p>
            </div>
        </div>
    </div>

    <!-- 右侧表单 -->
    <div class="login-form">
        <h2 class="form-title">账号登录</h2>

        <!-- 登录方式切换 -->
        <?php if(Option::get('login_email_switch') == 'y' && Option::get('login_tel_switch') == 'y'): ?>
        <div class="login-toggle">
            <div class="toggle-item active" data-type="phone">手机号码登录</div>
            <div class="toggle-item" data-type="email">邮箱登录</div>
        </div>
        <?php endif; ?>

        <form class="layui-form" lay-filter="loginForm">
            <!-- 手机号码输入框 -->
            <?php if(Option::get('login_tel_switch') == 'y'): ?>
            <div class="form-group phone-field">
                <label class="layui-form-label">
                    <i class="fa fa-phone"></i>
                </label>
                <div class="layui-input-block">
                    <input type="tel" name="tel" placeholder="请输入手机号码" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <!-- 邮箱输入框（默认隐藏） -->
            <?php if(Option::get('login_email_switch') == 'y'): ?>
            <div class="form-group email-field <?= Option::get('login_tel_switch') == 'y' ? 'display-hide' : '' ?>">
                <label class="layui-form-label">
                    <i class="fa fa-envelope"></i>
                </label>
                <div class="layui-input-block">
                    <input type="text" name="email" placeholder="请输入邮箱地址" class="layui-input">
                </div>
            </div>
            <?php endif; ?>

            <!-- 密码 -->
            <div class="form-group">
                <label class="layui-form-label">
                    <i class="fa fa-lock"></i>
                </label>
                <div class="layui-input-block">
                    <input type="password" name="password" placeholder="请输入密码" class="layui-input">
                </div>
            </div>

            <?php doAction('user_login_remember') ?>

            <!-- 选项：记住密码和忘记密码 -->
            <div class="form-options">
                <div class="layui-form-item">
                    <input type="checkbox" name="persist" value="1" lay-skin="primary" title="记住我">
                </div>
                <a href="account.php?action=reset">忘记密码？</a>
            </div>

            <!-- 登录按钮 -->
            <input type="hidden" name="type" id="type" value="<?= $default_type ?>" />
            <button class="layui-btn" lay-submit lay-filter="login">立即登录</button>

            <!-- 其他登录方式 -->
            <!--<div class="other-login">
                <p>其他登录方式</p>
                <div class="login-icons">
                    <div class="login-icon" title="微信登录">
                        <i class="fa fa-weixin"></i>
                    </div>
                    <div class="login-icon" title="QQ登录">
                        <i class="fa fa-qq"></i>
                    </div>
                    <div class="login-icon" title="微博登录">
                        <i class="fa fa-weibo"></i>
                    </div>
                </div>
            </div>-->

            <!-- 没有账号 -->
            <div class="register-link">
                还没有账号？<a href="account.php?action=signup">立即注册</a>
            </div>
        </form>
    </div>
</div>
<script>
    layui.use(['form', 'layer'], function() {
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.$;

        // 登录方式切换
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




        // 监听登录提交
        form.on('submit(login)', function(data) {
            // 模拟登录请求
            layer.load(2);

            var url = "./account.php?action=dosignin&s=<?= $admin_path_code ?>";

            var field = data.field; // 获取表单字段值

            $.ajax({
                type: "POST",
                url: url,
                data: field,
                dataType: "json",
                success: function (e) {
                    if(e.code == 0){
                        layer.msg('登录成功，正在跳转');
                        location.href="/";
                    }else{
                        <?php doAction('admin_login_error') ?>
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

        // 第三方登录点击事件
        $('.login-icon').click(function() {
            var title = $(this).attr('title');
            layer.msg('即将跳转到' + title, {icon: 1, time: 1500});
        });


    });
</script>
</body>
</html>

