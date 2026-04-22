<?php
/*
Template Name:默认模板
Version:1.5.9
Template Url:
Description:系统默认的自适应模板
Author:驳手
Author Url:

*/

?>
<?php

defined('EM_ROOT') || exit('access denied!');
require_once View::getView('module');

$version = '1720327727';

$home_icon = Option::get('home_icon');
global $userData;

$normalizeAssetUrl = static function ($path) {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('{^(https?|ftp)://}i', $path)) {
        return $path;
    }
    if (strpos($path, '../') === 0) {
        return getFileUrl($path);
    }
    if (strpos($path, './') === 0) {
        return EM_URL . substr($path, 2);
    }
    if (strpos($path, '/') === 0) {
        return rtrim(EM_URL, '/') . $path;
    }
    return EM_URL . ltrim($path, '/');
};

$favicon = empty($home_icon) ? (empty(_g('favicon')) ? EM_URL . 'favicon.ico' : $normalizeAssetUrl(_g('favicon'))) : $normalizeAssetUrl($home_icon);
$logo = $normalizeAssetUrl(Option::get('logo'));
$brand_name = isset($blogname) ? (string) $blogname : '';
$header_brand_name = strcasecmp($brand_name, 'eshop') === 0 ? strtoupper($brand_name) : $brand_name;

$can_login = Option::get('login_switch') == 'y';
$can_register = Option::get('register_switch') == 'y';
$is_user_login = ISLOGIN === true;
$show_user_entry = $is_user_login || $can_login || $can_register;
$common_style_version = @filemtime(EM_ROOT . '/content/common/common.css') ?: Option::EM_VERSION_TIMESTAMP;
$template_style_version = @filemtime(TEMPLATE_PATH . 'css/style.css') ?: Option::EM_VERSION_TIMESTAMP;
$em_script_version = @filemtime(EM_ROOT . '/content/static/js/em.js') ?: Option::EM_VERSION_TIMESTAMP;

$user_avatar = '';
$user_name = '';
if ($is_user_login) {
    $user_avatar = User::getAvatar($userData['photo'] ?? '');
    $user_name = $userData['nickname'] ?? ($userData['username'] ?? '用户');
}

?>
<!doctype html>
<html lang="zh-cn" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $site_title ?></title>
    <meta name="keywords" content="<?= $site_key ?>"/>
    <meta name="description" content="<?= $site_description ?>"/>
    <link href="<?= $favicon ?>" rel="icon">
    <link rel="alternate" title="RSS" href="<?= EM_URL ?>rss.php" type="application/rss+xml"/>


    <script src="<?= EM_URL ?>content/static/vendor/jquery/jquery.min.3.5.1.js"></script>
    <!-- 字体 -->
    <link rel="stylesheet" type="text/css" href="<?= EM_URL ?>content/static/vendor/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= EM_URL ?>content/static/vendor/layui-v2.11.6/layui/css/layui.css">
    <script src="<?= EM_URL ?>content/static/vendor/layui-v2.11.6/layui/layui.js"></script>





    <link rel="stylesheet" href="<?= EM_URL ?>content/static/css/em.css?v=<?= Option::EM_VERSION_TIMESTAMP ?>">
    <link rel="stylesheet" href="<?= EM_URL ?>content/common/common.css?v=<?= $common_style_version ?>">
    <link href="<?= TEMPLATE_URL ?>css/style.css?v=<?= $template_style_version ?>" rel="stylesheet"/>

    <script src="<?= EM_URL ?>content/static/js/em.js?v=<?= $em_script_version ?>"></script>
    <script src="<?= TEMPLATE_URL ?>js/main.js?v=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

    <?php doAction('index_head') ?>
</head>
<body>

<div id="mask"></div>
<header class="header">
    <div class="h-fix" id="h-fix">
        <div class="container">
            <div class="header-bar">
                <?php if (empty($logo)): ?>
                <h1 class="logo-text">
                    <a href="<?= EM_URL ?>" title="<?= $blogname ?>">
                        <span id="light-logo"><?= $header_brand_name ?></span>
                    </a>
                </h1>
                <?php else: ?>
                <h1 class="logo">
                    <a href="<?= EM_URL ?>" title="<?= $blogname ?>">
                        <img id="light-logo" src="<?= $logo ?>" alt="<?= $blogname ?>" title="<?= $blogname ?>">
                    </a>
                </h1>
                <?php endif; ?>

                <?php
                ob_start();
                blog_navi();
                $nav_content = ob_get_clean();
                if (!empty(trim($nav_content))):
                ?>
                <div class="nav-container">
                    <nav class="nav-bar" id="nav-box" data-type="index" data-infoid="">
                        <ul class="nav"><?php echo $nav_content; ?></ul>
                    </nav>
                </div>
                <?php endif; ?>

                <div class="header-right">
                    <div class="header-right-btn">
                        <a href="<?= EM_URL ?>user/visitors.php" class="header-order-link">
                            <i class="fa fa-search"></i>
                            <span>查询订单</span>
                        </a>

                        <?php if ($show_user_entry): ?>
                            <?php if ($is_user_login): ?>
                                <a href="<?= EM_URL ?>user" class="header-user-card">
                                    <img class="header-user-avatar" src="<?= $user_avatar ?>" alt="<?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="header-user-meta">
                                        <span class="header-user-name"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="header-user-balance">订单中心</span>
                                    </span>
                                </a>
                            <?php else: ?>
                                <div class="header-auth-links">
                                    <?php if ($can_login): ?>
                                        <a class="header-auth-btn is-ghost" href="<?= EM_URL ?>user/account.php?action=signin">登录</a>
                                    <?php endif; ?>
                                    <?php if ($can_register): ?>
                                        <a class="header-auth-btn is-solid" href="<?= EM_URL ?>user/account.php?action=signup">注册</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div id="m-btn" class="m-btn"><i class="fa fa-bars"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<div id="zhanwei"></div>
<script>
function setPlaceholderHeight() {
    var headerHeight = $('.h-fix').outerHeight() || 0;
    $('#zhanwei').css('height', headerHeight + 'px');
}
setPlaceholderHeight();
$(window).on('resize', setPlaceholderHeight);
</script>
