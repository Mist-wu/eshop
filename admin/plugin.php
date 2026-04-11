<?php
/**
 * plugin management
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$plugin = Input::getStrVar("plugin");
$filter = Input::getStrVar('filter'); // on or off

if (empty($action) && empty($plugin)) {

    $br = '<a href="./">控制台</a><a><cite>插件管理</cite></a>';

    include View::getAdmView('header');
    require_once(View::getAdmView('plugin'));
    include View::getAdmView('footer');
    View::output();
}

if ($action == 'index') {
    $Plugin_Model = new Plugin_Model();
    $plugins = $Plugin_Model->getPlugins($filter);

    // 只返回基本插件数据，不进行更新检测
    foreach($plugins as $key => $val){
        $plugins[$key]['update'] = 0; // 默认无更新
        $plugins[$key]['id'] = 0;     // 默认ID为0
    }
    
    output::data($plugins, count($plugins));
}

// 检测插件更新
if ($action == 'checkUpdates') {
    $Plugin_Model = new Plugin_Model();
    $plugins = $Plugin_Model->getPlugins($filter);

    $check = [];
    foreach($plugins as $val){
        $check[] = [
            'name' => $val['Plugin'],
            'version' => $val['Version']
        ];
    }

    $emkey = getMyEmkey();
    $post_data = [
        'emkey' => $emkey,
        'apps'  => json_encode($check),
    ];
    $res = emCurl(
        EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=is_plugin_upgrade', 
        http_build_query($post_data), 1, [], 10
    );
    $res = json_decode($res, 1);
    if($res['code'] == 200){
        $update_data = $res['data'];
        $result = [];
        foreach($plugins as $key => $val){
            $hasUpdate = 0;
            $pluginId = 0;
            foreach($update_data as $v){
                if($val['Plugin'] == $v['name']){
                    $hasUpdate = 1;
                    $pluginId = $v['id'];
                    break;
                }
            }
            $result[] = [
                'plugin' => $val['Plugin'],
                'update' => $hasUpdate,
                'id' => $pluginId
            ];
        }
        output::data($result, count($result));
    } else {
        output::data([], 0);
    }
}

if($action == 'switch'){
    LoginAuth::checkToken();
    $Plugin_Model = new Plugin_Model();
    $alias = Input::postStrVar('plugin');
    $status = Input::postIntVar('status');
    if($status == 1){
        $res = $Plugin_Model->activePlugin($alias);
    }else{
        if (strpos($alias, 'tpl_options') !== false) {
            output::error('禁止操作该插件');
        }
        $Plugin_Model->inactivePlugin($alias);
        $res = true;
    }
    if($res){
        $CACHE->updateCache('options');
        output::ok('操作成功');
    }else{
        output::error('操作失败');
    }
}


// Load plug-in configuration page
if (empty($action) && $plugin) {
    require_once "../content/plugins/$plugin/{$plugin}_setting.php";
    include View::getAdmView('header');
    plugin_setting_view();
    include View::getAdmView('footer');
}
if($action == 'setting_page'){
    $type = Input::getStrVar('type');
    if($type == 'admin'){
        $br = '<a href="./">控制台</a><a><cite>插件扩展功能</cite></a>';
    }
    require_once "../content/plugins/$plugin/{$plugin}_setting.php";
    include View::getAdmView($type == 'admin' ? 'header' : 'open_head');
    plugin_setting_view();
    include View::getAdmView($type == 'admin' ? 'footer' : 'open_foot');
}

// Save plug-in settings
if ($action == 'setting') {
    if (!empty($_POST)) {
        require_once "../content/plugins/$plugin/{$plugin}_setting.php";
        if (false === plugin_setting()) {
            emDirect("./plugin.php?plugin={$plugin}&error=1");
        } else {
            emDirect("./plugin.php?plugin={$plugin}&setting=1");
        }
    } else {
        emDirect("./plugin.php?plugin={$plugin}&error=1");
    }
}



if ($action == 'del') {
    LoginAuth::checkToken();
    $plugin = Input::postStrVar('plugin');
    $Plugin_Model = new Plugin_Model();
    $Plugin_Model->inactivePlugin($plugin);
    $Plugin_Model->rmCallback($plugin);
    $path = preg_replace("/^([\w-]+)\/[\w-]+\.php$/i", "$1", $plugin);

    if ($path && true === emDeleteFile('../content/plugins/' . $path)) {
        $CACHE->updateCache('options');
        output::ok('删除成功');
    } else {
        output::ok('删除成功');
    }
}


if ($action === 'upgrade') {
    $plugin_id = Input::postStrVar('plugin_id');
    $alias = Input::postStrVar('alias');
    $url = EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=downloadPlugin&host=' . getTopHost() . '&plugin_id=' . $plugin_id;
    // echo $url;die;
    $temp_file = emFetchFile($url);
    // var_dump($temp_file);die;
    if (!$temp_file) {
        output::error('未购买该插件或更新失败！');
    }
    $unzip_path = '../content/plugins/';
    $ret = emUnZip($temp_file, $unzip_path, 'plugin');
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            $Plugin_Model = new Plugin_Model();
            $Plugin_Model->upCallback($alias);
            output::ok();
            break;
        case 1:
        case 2:
            output::error('更新失败');
            break;
        case 3:
            output::error('更新失败');
            break;
        default:
            output::error('更新失败');
    }
}
