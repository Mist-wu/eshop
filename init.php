<?php

ob_start();
header('Content-Type: text/html; charset=UTF-8');

const EM_ROOT = __DIR__;

require_once EM_ROOT . '/config.php';
require_once EM_ROOT . '/include/lib/env.php';
emLoadEnv();
require_once EM_ROOT . '/base.php';
require_once EM_ROOT . '/include/lib/common.php';



if (getenv('EM_ENV') === 'develop' || (defined('ENVIRONMENT') && ENVIRONMENT === 'develop')) {
    // 显示所有错误（包括警告、通知等）
    error_reporting(E_ALL);
} else {
    error_reporting(1);
}



if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
}

spl_autoload_register("emAutoload");

$CACHE = Cache::getInstance();
$userData = [];

define('ISLOGIN', LoginAuth::isLogin());

date_default_timezone_set(Option::get('timezone'));

const ROLE_ADMIN = 'admin';
const ROLE_EDITOR = 'editor';
const ROLE_WRITER = 'writer';
const ROLE_VISITOR = 'visitor';


define('ROLE', ISLOGIN === true ? $userData['role'] : User::ROLE_VISITOR);
define('UID', ISLOGIN === true ? (int)$userData['uid'] : 0);
define('LEVEL', ISLOGIN === true ? $userData['level'] : -1); // 用户等级

define('EM_URL', realUrl()); // 当前网址
define('EM_DOMAIN', getDomain()); // 当前域名
define('TIMESTAMP', time()); // 当前时间戳





// 保存本地身份标识
if(isset($_COOKIE['EM_LOCAL'])){
    define('EM_LOCAL', strip_tags($_COOKIE['EM_LOCAL']));
}else{
    define('EM_LOCAL', generateUUIDv4());
}
// 每次访问都更新cookie过期时间
setcookie('EM_LOCAL', EM_LOCAL, time() + 3600*24*365, '/');




const TPLS_PATH = EM_ROOT . '/content/templates/';
const TPLS_STATION_PATH = TPLS_PATH;
const PLUGIN_URL = EM_URL . 'content/plugins/';
const PLUGIN_PATH = EM_ROOT . '/content/plugins/';
const LOG_PATH = EM_ROOT . '/content/log/';

$nonce_templet = isMobile() ? Option::get('nonce_templet_tel') : Option::get('nonce_templet');
define("TPLS_URL", EM_URL . 'content/templates/');
define('TEMPLATE_PATH', TPLS_PATH . $nonce_templet . '/');



//站点URL
define('DYNAMIC_BLOGURL', Option::get('blogurl'));
//当前模板的URL
define('TEMPLATE_URL', TPLS_URL . $nonce_templet . '/');
define('BLOG_TEMPLATE_URL', EM_URL . 'content/blog/default/');
//后台模板的绝对路径
define('ADMIN_TEMPLATE_PATH', EM_ROOT . '/admin/views/');
define('USER_TEMPLATE_PATH', EM_ROOT . '/user/views/');
//前台模板的绝对路径

define('BLOG_TEMPLATE_PATH', EM_ROOT . '/content/blog/default/');
define('COMMON_TEMPLATE_PATH', EM_ROOT . '/content/common/');

$active_plugins = Option::get('active_plugins');
$emHooks = [];
require_once EM_ROOT . '/include/lib/yifut.php';
if ($active_plugins && is_array($active_plugins)) {
    foreach ($active_plugins as $plugin) {
        if (true === checkPlugin($plugin)) {
            include_once(EM_ROOT . '/content/plugins/' . $plugin);
        }
    }
}


// 加载模板的系统调用文件
define('TEMPLATE_HOOK_PATH', TEMPLATE_PATH . 'plugins.php');
if (file_exists(TEMPLATE_HOOK_PATH)) {
    include_once(TEMPLATE_HOOK_PATH);
}


if (defined('DEMO') && DEMO === true) {

    $method = $_SERVER['REQUEST_METHOD'];
    $action = Input::getStrVar('action');


    if($action == 'delete'){
        output::error('演示站点无法进行该操作！');
    }
    if($action == 'upload'){
        header("HTTP/1.0 400 Bad Request");
        exit('演示站点无法进行该操作！');
    }
    if($method == 'POST'){
        output::error('演示站点无法进行该操作！');
    }

}
if (!class_exists('TplOptions', false)) {
    include __DIR__ . '/include/lib/tpl_options.php';

}
TplOptions::getInstance()->init();

if(defined('DEMO_MODE') && DEMO_MODE === true && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = Input::getStrVar('action');
    $allow = [
        'dosignin', 'goods_price_stock'
    ];
    if(!in_array($action, $allow)){
        Ret::error('当前演示站禁止该操作');
    }

}

if(defined('DEMO_MODE') && DEMO_MODE === true && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $action = Input::getStrVar('action');
    $noAllow = [
        'update'
    ];
    if(in_array($action, $noAllow)){
        Ret::error('当前演示站禁止该操作');
    }
    $filename = Input::getStrVar('filename');
    if(!empty($filename) || $action == 'download'){
        emMsg('当前演示站禁止该操作', 'javascript:window.close();');
    }

}

