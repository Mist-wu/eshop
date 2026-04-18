<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>个人中心 - <?= Option::get('blogname') ?></title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link href="<?= empty(Option::get('home_icon')) ? EM_URL . 'favicon.ico' : Option::get('home_icon'); ?>" rel="shortcut icon">


    <script src="<?= EM_URL ?>/admin/views/js/jquery.min.3.5.1.js"></script>
    <link rel="stylesheet" href="<?= EM_URL ?>/admin/views/layui-v2.11.6/layui/css/layui.css">
    <script src="<?= EM_URL ?>/admin/views/layui-v2.11.6/layui/layui.js"></script>
    <script src="<?= EM_URL ?>/admin/views/components/clipboard.min.js?t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

    <link rel="stylesheet" type="text/css" href="<?= EM_URL ?>/admin/views/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= EM_URL ?>/content/static/css/em.css">


    <script type="text/javascript" src="<?= EM_URL ?>/user/views/test/js/xadmin.js"></script>

    <style>
        :root {
            --bg: #f7f4ee;
            --bg-soft: #f0f6f2;
            --panel: #ffffff;
            --panel-soft: #f6f7f5;
            --text: #1f2937;
            --muted: #6b7280;
            --primary: #0f766e;
            --primary-strong: #0b5f59;
            --accent: #f59e0b;
            --border: rgba(15, 118, 110, 0.16);
            --border-soft: rgba(15, 118, 110, 0.08);
            --shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            --sidebar-width: 240px;
            --topbar-height: 64px;
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 12px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            margin: 0;
            color: var(--text);
            font-family: "Noto Sans SC", "Space Grotesk", "PingFang SC", "Microsoft YaHei", sans-serif;
            background:
                radial-gradient(900px 480px at 85% -10%, rgba(15, 118, 110, 0.18), transparent 55%),
                radial-gradient(600px 320px at 0% 15%, rgba(245, 158, 11, 0.16), transparent 50%),
                linear-gradient(135deg, #f9f6f1 0%, #eef5f2 55%, #f2f6fb 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        ul,
        ol {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .body-page {
            position: relative;
            min-height: calc(100vh - 24px);
            margin: 12px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            overflow: hidden;
        }

        .container {
            position: absolute;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px 0 18px;
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid var(--border-soft);
            z-index: 100;
            backdrop-filter: blur(14px);
        }

        .left_open {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .left_open i {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary);
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .left_open i:hover {
            transform: translateY(-1px);
            background: rgba(15, 118, 110, 0.18);
            color: var(--primary-strong);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .top-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            background: rgba(15, 118, 110, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.16);
            transition: all 0.2s ease;
        }

        .top-link:hover {
            background: rgba(15, 118, 110, 0.16);
            color: var(--primary-strong);
        }

        .top-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown summary {
            list-style: none;
        }

        .user-dropdown summary::-webkit-details-marker {
            display: none;
        }

        .user-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(15, 118, 110, 0.16);
            background: #ffffff;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            transition: all 0.2s ease;
        }

        .user-trigger:hover {
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary-strong);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            object-fit: cover;
        }

        .user-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            min-width: 140px;
            padding: 8px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(15, 118, 110, 0.12);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
            display: none;
        }

        .user-menu a {
            display: block;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            color: var(--text);
            transition: background 0.2s ease;
        }

        .user-menu a:hover {
            background: rgba(15, 118, 110, 0.08);
            color: var(--primary-strong);
        }

        .user-dropdown[open] .user-menu {
            display: block;
        }

        .left-nav {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            padding: 16px 14px 20px;
            background: rgba(255, 255, 255, 0.94);
            border-right: 1px solid var(--border-soft);
            z-index: 120;
            overflow: hidden;
        }

        .logo-text {
            display: block;
            text-align: center;
            font-family: "Space Grotesk", "Noto Sans SC", sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-strong);
            padding: 10px 0 16px;
            margin: 0 6px 12px;
            border-bottom: 1px solid var(--border-soft);
        }

        #side-nav {
            padding: 6px 6px 12px;
        }

        .left-nav #nav > li {
            border-radius: 14px;
            margin-bottom: 8px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .left-nav #nav > li:hover {
            background: rgba(15, 118, 110, 0.08);
        }

        .left-nav #nav > li.open {
            background: rgba(15, 118, 110, 0.12);
        }

        .left-nav #nav li a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px 10px 14px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            gap: 10px;
        }

        .left-nav #nav li a i {
            width: 18px;
            text-align: center;
            color: var(--primary);
        }

        .left-nav #nav li a cite {
            flex: 1;
        }

        .left-nav #nav li .nav_right {
            color: rgba(31, 41, 55, 0.5);
        }

        .left-nav #nav li .sub-menu {
            display: none;
            margin: 6px 0 2px;
            padding: 6px;
            background: rgba(15, 118, 110, 0.04);
            border-radius: 12px;
        }

        .left-nav #nav li .sub-menu li {
            border-radius: 10px;
        }

        .left-nav #nav li .sub-menu li a {
            padding: 8px 10px 8px 28px;
            font-size: 13px;
            color: var(--muted);
            justify-content: flex-start;
            gap: 8px;
        }

        .left-nav #nav li .sub-menu li a i {
            color: var(--primary-strong);
        }

        .left-nav #nav > li.menu-current,
        .left-nav #nav > li.layui-this {
            background: rgba(15, 118, 110, 0.16);
        }

        .left-nav #nav > li.menu-current > a,
        .left-nav #nav > li.layui-this > a {
            color: var(--primary-strong);
            font-weight: 600;
        }

        .left-nav #nav li .sub-menu li.menu-current {
            background: transparent;
        }

        .left-nav #nav li .sub-menu li.menu-current > a {
            color: var(--primary-strong);
            font-weight: 600;
        }

        .page-content {
            position: absolute;
            top: var(--topbar-height);
            left: var(--sidebar-width);
            right: 0;
            bottom: 0;
            padding: 18px 20px 24px;
            overflow: hidden;
        }

        .page-content .tab {
            height: 100%;
            width: 100%;
            background: transparent;
            margin: 0;
        }

        .page-content .layui-tab-content {
            position: relative;
            height: 100%;
            padding: 0;
            overflow: auto;
        }

        .page-content .layui-tab-content .layui-tab-item {
            width: 100%;
            min-height: 100%;
            background: transparent;
        }

        .page-content-bg {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(15, 23, 42, 0.32);
            backdrop-filter: blur(2px);
            z-index: 90;
            display: none;
        }

        .main-content {
            padding: 15px;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content {
            animation: fadeUp 0.4s ease;
        }

        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 220px;
            }
        }

        @media (max-width: 768px) {
            .body-page {
                margin: 0;
                border-radius: 0;
            }

            .container {
                left: 0;
                padding: 0 16px;
            }

            .left-nav {
                left: calc(-1 * var(--sidebar-width));
            }

            .page-content {
                left: 0;
                padding: 12px;
            }

            .main-content {
                padding: 20px 14px 28px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>

</head>
<body>

<div class="body-page">


<!-- 顶部开始 -->
<div class="container">
    <div class="left_open">
        <i title="展开左侧栏" class="fa fa-bars"></i>
    </div>

    <div class="top-actions">
        <a class="top-link" href="<?= EM_URL ?>">
            <span>前台首页</span>
            <span class="top-dot"></span>
        </a>
        <?php if(Option::get('login_switch') == 'y' && Option::get('register_switch') == 'y'): ?>
            <details class="user-dropdown">
                <summary class="user-trigger">
                    <img src="<?= $userData['photo'] ?>" onerror="this.src='/admin/views/images/avatar.svg'" class="user-avatar" alt="avatar">
                    <span class="user-label">账户</span>
                    <i class="fa fa-angle-down"></i>
                </summary>
                <div class="user-menu">
                    <a href="/user/profile.php">个人信息</a>
                    <a href="/user/account.php?action=logout">退出登录</a>
                </div>
            </details>
        <?php endif; ?>
    </div>

</div>
<!-- 顶部结束 -->
<!-- 中部开始 -->
<!-- 左侧菜单开始 -->
<div class="left-nav">
    <a href="<?= EM_URL ?>" class="logo-text"><?= Option::get('blogname') ?></a>
    <div id="side-nav">
        <ul id="nav">
            <li id="menu-index">
                <a href="/user"><i class="fa fa-user"></i><cite>个人中心</cite></a>
            </li>

            <li id="menu-order-user">
                <a href="/user/order.php"><i class="fa fa-list-ul"></i><cite>订单列表</cite></a>
            </li>
            <li id="menu-promotion">
                <a href="/user/promotion.php"><i class="fa fa-bullhorn"></i><cite>推广中心</cite></a>
            </li>
            <li id="menu-order-visitors">
                <a href="/user/visitors.php"><i class="fa fa-search"></i><cite>游客查单</cite></a>
            </li>
        </ul>
    </div>
</div>
<!-- <div class="x-slide_left"></div> -->
<!-- 左侧菜单结束 -->
<!-- 右侧主体开始 -->
<div class="page-content">
    <div class="layui-tab tab" lay-filter="xbs_tab" lay-allowclose="false">

        <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
