<?php defined('EM_ROOT') || exit('access denied!'); ?>
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name=renderer content=webkit>
    <title><?= $page_title ?></title>
    <link rel="shortcut icon" href="<?= empty(Option::get('admin_icon')) ? EM_URL . 'favicon.ico' : Option::get('admin_icon'); ?>"/>

    <!-- 字体 -->
    <link rel="stylesheet" type="text/css" href="./views/font-awesome-4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="./views/layui-v2.11.6//layui/css/layui.css">
    <script src="./views/layui-v2.11.6/layui/layui.js"></script>

    <script src="./views/js/jquery.min.3.5.1.js"></script>


    <script src="./views/js/common.js?v=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>

    <link rel="stylesheet" href="../../content/static/css/em.css?v=<?= Option::EM_VERSION_TIMESTAMP ?>">

    <script src="../../content/static/js/em.js?v=<?= Option::EM_VERSION_TIMESTAMP ?>"></script>


    <?php doAction('login_head') ?>
</head>
<body class="bg-gradient-primary">
