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
        max-width: 380px;
    }

    /* Logo */
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

    /* 卡片 */
    .login-card {
        background: #fff;
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 12px 40px rgba(61, 79, 74, 0.08);
    }

    /* 表单 */
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

    /* 验证码 */
    .captcha-row {
        display: flex;
        gap: 10px;
    }

    .captcha-row .input-box {
        flex: 1;
    }

    .captcha-img-box {
        width: 100px;
        height: 44px;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        border: 1px solid #DCE5E2;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .captcha-img-box:hover {
        border-color: #7BA89D;
    }

    .captcha-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* 记住我 */
    .options-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .checkbox-item input {
        width: 16px;
        height: 16px;
        accent-color: #7BA89D;
        cursor: pointer;
        margin: 0;
    }

    .checkbox-item span {
        font-size: 14px;
        color: #5A6D67;
        user-select: none;
    }

    .forgot-link {
        font-size: 13px;
        color: #7BA89D;
        text-decoration: none;
        transition: color 0.2s;
    }

    .forgot-link:hover {
        color: #5D8C80;
    }

    /* 按钮 */
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

    /* 扩展 */
    .login-ext {
        margin-top: 18px;
        text-align: center;
    }

    /* 底部 */
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

    /* 响应式 */
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

        .captcha-row {
            flex-direction: column;
        }

        .captcha-img-box {
            width: 100%;
            height: 42px;
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
            <i class="fa fa-user"></i>
        </div>
        <h1><?= strcasecmp((string) Option::get('blogname'), 'eshop') === 0 ? 'ESHOP' : Option::get('blogname') ?></h1>
        <p>登录到管理后台</p>
    </div>

    <div class="login-card">
        <form method="post" class="layui-form" action="./account.php?action=dosignin&s=<?= $admin_path_code ?>">
            <div class="form-group">
                <label>账号</label>
                <div class="input-box">
                    <input type="text" class="form-control" id="user" name="user" placeholder="请输入账号" autofocus>
                    <i class="fa fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label>密码</label>
                <div class="input-box">
                    <input type="password" class="form-control" id="pw" name="pw" placeholder="请输入密码">
                    <i class="fa fa-lock"></i>
                </div>
            </div>

            <?php if ($login_code): ?>
            <div class="form-group">
                <label>验证码</label>
                <div class="captcha-row">
                    <div class="input-box">
                        <input type="text" name="login_code" class="form-control" id="login_code" placeholder="请输入验证码" required>
                        <i class="fa fa-shield"></i>
                    </div>
                    <div class="captcha-img-box" id="captcha-box" title="点击刷新">
                        <img src="../include/lib/checkcode.php" id="checkcode" alt="验证码">
                    </div>
                </div>
            </div>
            <?php endif ?>

            <?php doAction('admin_login_remember') ?>

            <div class="options-row">
                <label class="checkbox-item">
                    <input type="checkbox" id="persist" name="persist" value="1">
                    <span>记住登录</span>
                </label>
                <a class="forgot-link" href="./account.php?action=reset">忘记密码？</a>
            </div>

            <button type="button" lay-submit lay-filter="demo-submit" class="btn-login">
                <i class="fa fa-sign-in"></i>
                <span>登 录</span>
            </button>

            <?php if ($is_signup): ?>
            <?php endif ?>

            <div class="login-ext">
                <?php doAction('admin_login_ext') ?>
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

<script>
    layui.use(function(){
        var $ = layui.$;
        var form = layui.form;
        var layer = layui.layer;
        form.on('submit(demo-submit)', function(data){
            var field = data.field;
            var loader = layer.load();
            $.ajax({
                type: "POST",
                url: "./account.php?action=dosignin&s=<?= $admin_path_code ?>",
                data: field,
                dataType: "json",
                success: function (e) {
                    layer.close(loader);
                    if(e.code == 0){
                        em.msg('登录成功，正在跳转');
                        location.href="/admin";
                    }else{
                        <?php doAction('admin_login_error') ?>
                        em.msg(e.msg);
                    }
                },
                error: function (xhr) {
                    layer.close(loader);
                    em.warning(JSON.parse(xhr.responseText).msg);
                }
            });
            return false;
        });
    });
</script>

<script>
    $(function () {
        setTimeout(hideActived, 6000);
        $('#checkcode, #captcha-box').click(function () {
            var timestamp = new Date().getTime();
            $('#checkcode').attr("src", "../include/lib/checkcode.php?" + timestamp);
        });
    });
</script>

</body>
</html>
