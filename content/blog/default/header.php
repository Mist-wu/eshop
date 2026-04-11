<?php
/*
Template Name:默认模板
Version:1.2.1
Template Url:https://www.emlog.net/template/detail/1167
Description:EMLOG的系统默认模板
Author:emlog
Author Url:https://www.emlog.net/author/index/577
*/

defined('EM_ROOT') || exit('access denied!');
require_once View::getBlogView('module');
$v = '1720327727';
$home_icon = Option::get('home_icon');


?>
<!doctype html>
<html lang="zh-cn" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $site_title ?></title>
    <meta name="keywords" content="<?= $site_key ?>" />
    <meta name="description" content="<?= $site_description ?>" />
    <link href="<?= empty($home_icon) ? (empty(_g('favicon')) ? EM_URL . 'favicon.ico' : _g('favicon')) : $home_icon; ?>" rel="icon">
    <link rel="alternate" title="RSS" href="<?= EM_URL ?>rss.php" type="application/rss+xml" />
    <link href="<?= BLOG_TEMPLATE_URL ?>css/style.css?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>" rel="stylesheet" />
    <link href="<?= BLOG_TEMPLATE_URL ?>css/icon/iconfont.css?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>" rel="stylesheet" />
    <link href="<?= BLOG_TEMPLATE_URL ?>css/markdown.css?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>" rel="stylesheet" />
    <script src="../../../admin/views/js/jquery.min.3.5.1.js?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
    <script src="<?= BLOG_TEMPLATE_URL ?>js/common_tpl.js?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
    <script src="<?= BLOG_TEMPLATE_URL ?>js/zoom.js?v=<?= $v ?>&t=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>
    <?php doAction('index_head') ?>
    <script>
        // 日历生成和翻页
        function sendinfo(url) {
            $("#calendar").load(url)
        }

        // 切换夜间模式主题
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    </script>
</head>

<body>
    <nav class="blog-header">
        <div class="blog-header-c container">
            <?php if (!empty(Option::get('logo'))): ?>
                <a class="blog-header-title" href="<?= EM_URL ?>" title="<?= $bloginfo ?>">
                    <img id="light-logo" src="<?= Option::get('logo') ?>" alt="<?= $blogname ?>" title="<?= $blogname ?>">
                </a>
                <?php if (_em('logotype') == 1): ?>
                    <div class="blog-header-subtitle subtitle-overflow" title="<?= $bloginfo ?>"><?= $bloginfo ?></div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (_em('logotype') == 1): ?>
                    <a class="blog-header-title" href="<?= EM_URL ?>"><?= $blogname ?></a>
                    <div class="blog-header-subtitle subtitle-overflow" title="<?= $bloginfo ?>"><?= $bloginfo ?></div>
                <?php else: ?>
                    <a href="<?= EM_URL; ?>" title="<?= $bloginfo; ?>" style="color: #007bff; font-size: 1.5rem; font-weight: bold;"><?= $blogname ?></a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="blog-header-toggle">
                <svg class="blogtoggle-icon">
                    <rect x="1" y="1" fill="#5F5F5F" width="26" height="1.6" />
                    <rect x="1" y="8" fill="#5F5F5F" width="26" height="1.6" />
                    <rect x="1" y="15" fill="#5F5F5F" width="26" height="1.6" />
                </svg>
            </div>
            <?php blog_navi() ?>
            <?php doAction('index_navi_ext') ?>
        </div>
    </nav>
