<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        padding: 20px;
    }

    .login-wrapper {
        width: 100%;
        max-width: 400px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 28px;
    }

    .login-logo .icon-box {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #7BA89D 0%, #9DBEB5 100%);
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
        box-shadow: 0 8px 24px rgba(123, 168, 157, 0.25);
    }

    .login-logo .icon-box i {
        font-size: 26px;
        color: #fff;
    }

    .login-logo h1 {
        color: #3D4F4A;
        font-size: 22px;
        font-weight: 600;
        margin: 0 0 6px;
    }

    .login-logo p {
        color: #7A8B86;
        font-size: 14px;
        margin: 0;
    }

    .login-card {
        background: #fff;
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 12px 40px rgba(61, 79, 74, 0.08);
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #4A5D57;
        margin-bottom: 6px;
    }

    .input-box {
        position: relative;
    }

    .input-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #A3B5B0;
        font-size: 15px;
        transition: color 0.2s;
    }

    .form-control {
        width: 100%;
        padding: 12px 14px 12px 42px;
        background: #F7FAF9;
        border: 1px solid #DCE5E2;
        border-radius: 10px;
        font-size: 15px;
        color: #3D4F4A;
        transition: all 0.2s;
        outline: none;
    }

    .form-control:focus {
        border-color: #7BA89D;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(123, 168, 157, 0.12);
    }

    .input-box:focus-within i {
        color: #7BA89D;
    }

    .form-control::placeholder {
        color: #A3B5B0;
    }

    .btn-login {
        width: 100%;
        padding: 13px 20px;
        background: linear-gradient(135deg, #7BA89D 0%, #9DBEB5 100%);
        border: none;
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        box-shadow: 0 8px 24px rgba(123, 168, 157, 0.35);
        transform: translateY(-1px);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .login-ext {
        margin-top: 16px;
        text-align: center;
    }

    .login-ext a {
        color: #7BA89D;
        text-decoration: none;
        font-size: 13px;
        transition: color 0.2s;
    }

    .login-ext a:hover {
        color: #5D8C80;
    }

    .login-footer {
        margin-top: 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .login-footer a {
        color: #7BA89D;
        text-decoration: none;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: color 0.2s;
    }

    .login-footer a:hover {
        color: #5D8C80;
    }

    .login-footer .copyright {
        color: #A3B5B0;
        font-size: 12px;
    }

    .alert {
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 13px;
        margin-bottom: 14px;
        border: 1px solid transparent;
    }

    .alert-success {
        background: #EAF4F1;
        border-color: #C7DED6;
        color: #3D4F4A;
    }

    .alert-danger {
        background: #FDEEEE;
        border-color: #F2B8B8;
        color: #7A3B3B;
    }

    @media (max-width: 480px) {
        body {
            padding: 16px;
            align-items: flex-start;
            padding-top: 50px;
        }

        .login-logo {
            margin-bottom: 24px;
        }

        .login-logo .icon-box {
            width: 54px;
            height: 54px;
        }

        .login-logo .icon-box i {
            font-size: 22px;
        }

        .login-logo h1 {
            font-size: 20px;
        }

        .login-card {
            padding: 26px 22px;
            border-radius: 16px;
        }

        .form-control {
            font-size: 16px;
        }

        .login-footer {
            flex-direction: column;
            gap: 10px;
            margin-top: 22px;
        }
    }

    @media (max-width: 360px) {
        body {
            padding: 12px;
            padding-top: 36px;
        }

        .login-card {
            padding: 22px 18px;
        }
    }

    @media (max-height: 700px) {
        body {
            align-items: flex-start;
            padding-top: 24px;
        }
    }
</style>

<div class="login-wrapper">
    <div class="login-logo">
        <div class="icon-box">
            <i class="fa fa-key"></i>
        </div>
        <h1><?= Option::get('blogname') ?></h1>
        <p>找回密码：重置密码</p>
    </div>

    <div class="login-card">
        <form method="post" class="layui-form" action="./account.php?action=doreset2">
            <?php if (isset($_GET['succ_mail'])): ?>
                <div class="alert alert-success">邮件验证码已发送到你的邮箱，请查收后填写</div><?php endif ?>
            <?php if (isset($_GET['err_mail_code'])): ?>
                <div class="alert alert-danger">邮件验证码错误</div><?php endif ?>

            <div class="form-group">
                <label>邮件验证码</label>
                <div class="input-box">
                    <input type="text" class="form-control" id="mail_code" name="mail_code" placeholder="邮件验证码(请查收邮件)" required>
                    <i class="fa fa-envelope-open"></i>
                </div>
            </div>

            <div class="form-group">
                <label>新密码</label>
                <div class="input-box">
                    <input type="password" class="form-control" minlength="6" id="passwd" autocomplete="new-password" name="passwd" placeholder="新的密码" required>
                    <i class="fa fa-lock"></i>
                </div>
            </div>

            <div class="form-group">
                <label>确认新密码</label>
                <div class="input-box">
                    <input type="password" class="form-control" minlength="6" id="repasswd" name="repasswd" placeholder="确认新密码" required>
                    <i class="fa fa-lock"></i>
                </div>
            </div>

            <button class="btn-login" type="submit">
                <i class="fa fa-paper-plane"></i>
                <span>提交</span>
            </button>

            <div class="login-ext">
                <a href="./">返回登录</a>
            </div>
        </form>
    </div>

    <div class="login-footer">
        <a href="../">
            <i class="fa fa-arrow-left"></i>
            <span>返回首页</span>
        </a>
        <span class="copyright">&copy; <?= date('Y') ?> All rights reserved</span>
    </div>
</div>
</body>
</html>
<script>
    $(function () {
        setTimeout(hideActived, 6000);
        $('#checkcode').click(function () {
            var timestamp = new Date().getTime();
            $(this).attr("src", "../include/lib/checkcode.php?" + timestamp);
        });
    });
</script>
