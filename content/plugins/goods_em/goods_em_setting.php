<?php
/**
 * EMSHOP 同系统对接插件 - 设置入口
 */

defined('EM_ROOT') || exit('access denied!');

if (!function_exists('goodsEmPlugin')) {
    emMsg('请先启用商品对接插件');
}

function plugin_setting_view()
{
    require_once EM_ROOT . '/content/plugins/goods_em/goods_em_show.php';
}

function plugin_setting()
{
    return true;
}
