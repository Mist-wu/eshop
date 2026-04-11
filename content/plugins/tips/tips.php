<?php
/*
Plugin Name: 小贴士
Version: 1.0.0
Plugin URL:
Description: 在后台首页展示一句使用小提示，也可作为插件开发的demo。
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

$array_tips = [
    '为防数据丢失，请每日备份您的数据库',
    '多逛逛应用商店吧，总会有惊喜',
    'EMSHOP 支持自建页面，为您的网站建一个专属页面吧',
    '检查你的站点目录下是否存在安装文件：install.php，有的话请删除它',
    '推荐使用Edge、Chrome浏览器，更好的体验EMSHOP',
    '在未来的每一秒，你都将是全新的自己',
    '使用过程中发现问题，可以联系群主或管理员解决'
];

function tips_init() {
    global $array_tips;
    $i = mt_rand(0, count($array_tips) - 1);
    $tip = $array_tips[$i];
    echo "<div id=\"tip\"> $tip</div>";
}

addAction('adm_main_top', 'tips_init');

function tips_css() {
    echo "<style>
    #tip{
        background:url(../content/plugins/tips/icon_tips.gif) no-repeat left 3px;
        padding:0px 18px;
        font-size:14px;
        color:#999999;
        margin-bottom: 12px;
    }
    </style>\n";
}
// EP EM ET
addAction('adm_head', 'tips_css');
