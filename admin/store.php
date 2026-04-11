<?php
/**
 * store
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Store_Model = new Store_Model();


$plugin_type_arr = [
    ['id' => 0, 'title' => '全部插件'],
    ['id' => 1, 'title' => '支付方式'],
    ['id' => 7, 'title' => '商品类型扩展'],
    ['id' => 2, 'title' => '系统通知'],
    ['id' => 3, 'title' => '页面美化'],
    ['id' => 4, 'title' => '系统扩展'],
    ['id' => 6, 'title' => '客服组件'],
    ['id' => 5, 'title' => '未归类'],
];



if (empty($action)) {
    Register::isRegServer();
    $emkey = getMyEmKey();
    $br = '<a href="./">控制台</a><a href="./store.php">应用商店</a><a><cite>全部应用</cite></a>';
    include View::getAdmView('header');
    require_once(View::getAdmView('store'));
    include View::getAdmView('footer');
    View::output();
}
if ($action === 'plu') {
    Register::isRegServer();
    $emkey = getMyEmKey();
    $br = '<a href="./">控制台</a><a href="./store.php">应用商店</a><a><cite>扩展插件</cite></a>';

    $plugin_type = Input::getStrVar('plugin_type', 0);
    $title = Input::getStrVar('title');
    include View::getAdmView('header');
    require_once(View::getAdmView('templates/default/store/store_plu'));
    include View::getAdmView('footer');
    View::output();
}
if ($action === 'tpl') {
    Register::isRegServer();
    $emkey = getMyEmKey();
    $br = '<a href="./">控制台</a><a href="./store.php">应用商店</a><a><cite>模板主题</cite></a>';

    include View::getAdmView('header');
    require_once(View::getAdmView('store_tpl'));
    include View::getAdmView('footer');
    View::output();
}

/**
 * 获取全部应用
 */
if($action == 'index'){
    $type = 'all';
    $page = Input::getIntVar('page', 1);
    $sid = Input::getStrVar('sid');
    $keyword = Input::getStrVar('keyword');
    $pageNum = Input::getIntVar('limit');
    $store = $Store_Model->getList($type, $page, $pageNum, $keyword, $sid);
    $apps = storeHandleData($store['list']);
    $count = $store['count'];
    output::data($apps, $count);
}

if($action == 'tpl_ajax'){
    $type = 'template';
    $page = Input::getIntVar('page', 1);
    $sid = Input::getStrVar('sid');
    $keyword = Input::getStrVar('keyword');
    $pageNum = Input::getIntVar('limit');
    $store = $Store_Model->getList($type, $page, $pageNum, $keyword, $sid);
    $apps = storeHandleData($store['list']);
    $count = $store['count'];
    output::data($apps, $count);
}



if($action == 'plu_ajax'){
    $type = 'plugin';
    $page = Input::getIntVar('page', 1);
    $sid = Input::getStrVar('plugin_type');
    $keyword = Input::getStrVar('keyword');
    $pageNum = Input::getIntVar('limit');
    $store = $Store_Model->getList($type, $page, $pageNum, $keyword, $sid);
    $apps = storeHandleData($store['list']);
    $count = $store['count'];
    output::data($apps, $count);
}



if ($action === 'mine') {
    $addons = $Store_Model->getMyAddon();
    $sub_title = '我的已购';

    include View::getAdmView('header');
    require_once(View::getAdmView('store_mine'));
    include View::getAdmView('footer');
    View::output();
}

if ($action === 'svip') {
    $addons = $Store_Model->getSvipAddon();
    $sub_title = '铁杆专属';

    include View::getAdmView('header');
    require_once(View::getAdmView('store_svip'));
    include View::getAdmView('footer');
    View::output();
}

if ($action === 'top') {
    $addons = $Store_Model->getTopAddon();
    output::ok($addons);
}

if ($action === 'error') {
    $keyword = '';
    $sub_title = '';
    $sid = '';

    $br = '<ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="./">控制台</a></li>
        <li class="breadcrumb-item"><a href="./store.php">应用商店</a></li>
        <li class="breadcrumb-item active" aria-current="page">全部应用</li>
    </ol>';

    include View::getAdmView('header');
    require_once(View::getAdmView('store'));
    include View::getAdmView('footer');
    View::output();
}

if ($action === 'install') {
    $type = Input::postStrVar('type');
    $plugin_id = Input::postStrVar('plugin_id');
    $source_type = Input::postStrVar('type');

    $r = Register::verifyDownload($plugin_id);

    
    if($r == -1){
        Ret::error('官方验证接口请求失败，请重试或更换其他线路');
    }

    if($r == 2){
        Ret::error('您当前未购买过此插件，禁止安装');
    }

    $url = EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=downloadPlugin&host=' . getTopHost() . '&plugin_id=' . $plugin_id;
    // echo $url;die;
    $temp_file = emFetchFile($url);

    
    if (!$temp_file) {
        output::error('安装失败，下载超时或没有权限');
    }

    if ($source_type == 'tpl') {
        $unzip_path = '../content/templates/';
        $suc_url = 'template.php';
    } else {
        $unzip_path = '../content/plugins/';
        $suc_url = 'plugin.php';
    }

    $ret = emUnZip($temp_file, $unzip_path, $source_type);
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            output::ok('安装成功 <a href="' . $suc_url . '">去启用</a>');
        case 1:
        case 2:
        output::error('安装失败，请检查content下目录是否可写');
        case 3:
            output::error('安装失败，请安装php的Zip扩展');
        default:
            output::error('安装失败，不是有效的安装包');
    }
}

function storeHandleData($apps){
    $Plugin_Model = new Plugin_Model();
    $p = $Plugin_Model->getPlugins();
    $install_plugin = [];
    foreach($p as $val){
        $install_plugin[] = $val['Plugin'];
    }

    $Template_Model = new Template_Model();
    $p = $Template_Model->getTemplates();
    foreach($p as $val){
        $install_plugin[] = $val['tplfile'];
    }

    $reg_type = Register::getRegType();

    foreach($apps as $key => $val){
        $apps[$key]['reg_type'] = $reg_type;
        if(in_array($val['english_name'], $install_plugin)){
            $apps[$key]['is_install'] = 'y';
        }else{
            $apps[$key]['is_install'] = 'n';
        }
    }
    return $apps;

}