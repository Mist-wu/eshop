<?php defined('EM_ROOT') || exit('access denied!'); ?>
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name=renderer content=webkit>
    <link rel="stylesheet" href="<?= EM_URL ?>admin/views/layui-v2.11.6//layui/css/layui.css">
    <script src="<?= EM_URL ?>admin/views/layui-v2.11.6//layui/layui.js"></script>
    <link rel="stylesheet" type="text/css" href="<?= EM_URL ?>admin/views/font-awesome-4.7.0/css/font-awesome.min.css">

    <!-- jquery v3.5.1 -->
    <script src="<?= EM_URL ?>admin/views/js/jquery.min.3.5.1.js"></script>


    <?php doAction('open_head') ?>

<style>
    .layui-badge-rim, .layui-border, .layui-colla-content, .layui-colla-item, .layui-collapse, .layui-elem-field, .layui-form-pane .layui-form-item[pane], .layui-form-pane .layui-form-label, .layui-input, .layui-input-split, .layui-panel, .layui-quote-nm, .layui-select, .layui-tab-bar, .layui-tab-card, .layui-tab-title, .layui-tab-title .layui-this:after, .layui-textarea{
        border-color: #c5c5c5;
    }
</style>


</head>
<body>


