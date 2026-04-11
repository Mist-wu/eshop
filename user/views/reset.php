<?php defined('EM_ROOT') || exit('access denied!'); ?>
<style>
    :root {
        --primary: #2563eb;
        --primary-strong: #1d4ed8;
        --accent: #f59e0b;
        --bg: #eef2ff;
        --card: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --border: rgba(15, 23, 42, 0.12);
        --shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background:
            radial-gradient(900px 520px at 90% -10%, rgba(37, 99, 235, 0.2), transparent 55%),
            radial-gradient(600px 360px at 0% 20%, rgba(245, 158, 11, 0.16), transparent 50%),
            linear-gradient(135deg, #f7f8ff 0%, #eef3ff 55%, #f5f7fb 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px;
        color: var(--text);
        font-family: "Noto Sans SC", "Space Grotesk", "PingFang SC", "Microsoft YaHei", sans-serif;
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    .reset-page {
        width: min(560px, 100%);
    }

    .reset-card {
        background: var(--card);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        padding: 30px;
        animation: rise 0.5s ease;
    }

    .reset-banner {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #ffffff;
        border-radius: 18px;
        padding: 18px 20px;
        display: flex;
        gap: 14px;
        align-items: center;
        margin-bottom: 22px;
    }

    .banner-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .banner-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        font-size: 11px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .reset-banner h2 {
        margin: 0 0 4px;
        font-size: 22px;
    }

    .reset-banner p {
        margin: 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.85);
    }

    .field {
        margin-bottom: 18px;
    }

    .field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .input-group {
        display: flex;
        align-items: stretch;
    }

    .input-group-prepend {
        display: flex;
    }

    .input-group-text {
        width: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        color: #475569;
        border: 1px solid var(--border);
        border-right: 0;
        border-radius: 12px 0 0 12px;
    }

    .form-control {
        flex: 1;
        height: 46px;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 0 12px 12px 0;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background: #ffffff;
    }

    .form-control:focus {
        border-color: rgba(37, 99, 235, 0.7);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    .checkcode-container {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .checkcode-container img {
        height: 46px;
        width: 120px;
        border-radius: 12px;
        border: 1px solid var(--border);
        cursor: pointer;
        object-fit: cover;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 16px;
        border-radius: 12px;
        border: none;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.24);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #1d4ed8, #4338ca);
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(37, 99, 235, 0.28);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .alert {
        border-radius: var(--radius-md);
        padding: 12px 14px;
        border: 1px solid var(--border);
        margin-bottom: 16px;
        background: #ffffff;
    }

    .alert-danger {
        background: #fff7ed;
        border-color: rgba(245, 158, 11, 0.3);
        color: #92400e;
    }

    .alert-success {
        background: #ecfdf3;
        border-color: rgba(34, 197, 94, 0.28);
        color: #166534;
    }

    .alert .close {
        opacity: 0.6;
        text-shadow: none;
    }

    .reset-divider {
        position: relative;
        text-align: center;
        margin: 22px 0 16px;
        color: var(--muted);
        font-size: 12px;
    }

    .reset-divider::before,
    .reset-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 38%;
        height: 1px;
        background: rgba(15, 23, 42, 0.12);
    }

    .reset-divider::before {
        left: 0;
    }

    .reset-divider::after {
        right: 0;
    }

    .reset-links {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 18px;
        justify-content: center;
        font-size: 14px;
    }

    .reset-links a {
        color: var(--primary-strong);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .reset-links a.link-muted {
        color: var(--muted);
        font-weight: 500;
    }

    @keyframes rise {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 600px) {
        body {
            padding: 16px;
        }

        .reset-card {
            padding: 24px;
        }

        .reset-divider::before,
        .reset-divider::after {
            width: 28%;
        }
    }
</style>
<div class="reset-page">
    <div class="reset-card">
        <div class="reset-banner">
            <div class="banner-icon">
                <i class="fa fa-envelope-o"></i>
            </div>
            <div>
                <div class="banner-tag">STEP 1 / 2</div>
                <h2>验证注册邮箱</h2>
                <p>输入注册邮箱，我们会发送验证码用于重置密码。</p>
            </div>
        </div>

        <?php if (isset($_GET['error_mail'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-circle mr-2"></i>错误的注册邮箱
            </div>
        <?php endif ?>

        <?php if (isset($_GET['error_sendmail'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-circle mr-2"></i>邮件验证码发送失败，请检查邮件通知设置
            </div>
        <?php endif ?>

        <?php if (isset($_GET['err_ckcode'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-circle mr-2"></i>图形验证码错误
            </div>
        <?php endif ?>

        <form method="post" class="user" action="./account.php?action=doreset">
            <div class="field">
                <label for="mail">注册邮箱</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-envelope-o fa-fw"></i></span>
                    </div>
                    <input type="email" class="form-control" id="mail" name="mail" placeholder="输入注册邮箱" required autofocus>
                </div>
            </div>

            <?php if ($login_code): ?>
                <div class="field">
                    <label for="login_code">图形验证码</label>
                    <div class="checkcode-container">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-shield"></i></span>
                            </div>
                            <input type="text" name="login_code" class="form-control" id="login_code" placeholder="请输入验证码" required>
                        </div>
                        <img src="../include/lib/checkcode.php" id="checkcode" onclick="this.src='../include/lib/checkcode.php?'+Math.random()">
                    </div>
                </div>
            <?php endif ?>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-paper-plane mr-2"></i>提交验证
            </button>

            <div class="reset-divider">或</div>

            <div class="reset-links">
                <a href="./"><i class="fa fa-sign-in"></i>返回登录</a>
                <a href="../" class="link-muted"><i class="fa fa-home"></i>返回首页</a>
                <a href="./account.php?action=signup" class="link-muted"><i class="fa fa-user-plus"></i>注册新账号</a>
            </div>
        </form>
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
