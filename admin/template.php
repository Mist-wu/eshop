<?php

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Template_Model = new Template_Model();

if ($action === '') {

    $br = '<a href="./">控制台</a><a href="./template.php">外观设置</a><a><cite>模板主题</cite></a>';

    include View::getAdmView('header');
    require_once View::getAdmView('templates/default/template/index');
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $list = $Template_Model->getTemplates();
    $nonce_template = Option::get('nonce_templet');
    $nonce_template_tel = Option::get('nonce_templet_tel');
    foreach($list as $key => $val){
        if($nonce_template == $val['tplfile']){
            $list[$key]['switch'] = 'y';
        }else{
            $list[$key]['switch'] = 'n';
        }
        if($nonce_template_tel == $val['tplfile']){
            $list[$key]['tel_switch'] = 'y';
        }else{
            $list[$key]['tel_switch'] = 'n';
        }
        // Initialize update status as 'n' (no update) - will be checked asynchronously
        $list[$key]['update'] = 'n';
        $list[$key]['id'] = '';
        $list[$key]['preview'] = '';
    }
    
    output::data($list, count($list));
}

if($action == 'checkUpdates'){
    $list = $Template_Model->getTemplates();
    $emkey = getMyEmkey();
    $post_data = [
        'emkey' => $emkey,
        'apps'  => [],
    ];
    foreach($list as $key => $val){
        $post_data['apps'][] = [
            'name' => $val['tplfile'],
            'version' => $val['version']
        ];
    }
    $res = emCurl(EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=is_plugin_upgrade',
     http_build_query($post_data), 1, [], 10);
    $res = json_decode($res, 1);
    if($res['code'] == 200){
        $update_data = $res['data'];
    }
    
    $result = [];
    foreach($list as $key => $val){
        $template_info = [
            'tplfile' => $val['tplfile'],
            'update' => 'n',
        ];
        
        if(isset($update_data)){
            foreach($update_data as $k => $v){
                if($v['name'] == $val['tplfile']){
                    $template_info['update'] = 'y';
                    $template_info['id'] = $v['id'];
                    break;
                }
            }
        }
        $result[] = $template_info;
    }
    
    output::data($result, count($result));
}

if ($action === 'use') {
    LoginAuth::checkToken();
    $tplName = Input::postStrVar('tpl');
    Option::updateOption('nonce_templet', $tplName);
    $CACHE->updateCache('options');
    $Template_Model->initCallback($tplName);
    output::ok();
}

if ($action === 'use_tel') {
    LoginAuth::checkToken();
    $tplName = Input::postStrVar('tpl');
    Option::updateOption('nonce_templet_tel', $tplName);
    $CACHE->updateCache('options');
    $Template_Model->initCallback($tplName);
    output::ok();
}

if ($action === 'del') {
    LoginAuth::checkToken();
    $tpls = Input::postStrVar('ids');
    $tpls = explode(',', $tpls);
    foreach($tpls as $val){
        $Template_Model->rmCallback($val);
        $path = preg_replace("/^([\w-]+)$/i", "$1", $val);
        emDeleteFile(TPLS_PATH . $path);
    }
    output::ok();
}

if($action == 'setting_page'){
    $tpl = Input::getStrVar('tpl');
    include View::getAdmView('open_head');
    require_once "../content/templates/$tpl/setting.php";
    plugin_setting_view();
    include View::getAdmView('open_foot');
}
if($action == 'setting_ajax'){
    $tpl = Input::getStrVar('tpl');
    require_once "../content/templates/$tpl/setting.php";
    plugin_setting($tpl);
}



if ($action === 'upgrade') {
    $plugin_id = Input::postStrVar('plugin_id');
    $alias = Input::postStrVar('alias');
    $url = EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=downloadPlugin&host=' . getTopHost() . '&plugin_id=' . $plugin_id;
    $temp_file = emFetchFile($url);
    if (!$temp_file) {
        Ret::error('更新包下载失败');
    }
    $unzip_path = '../content/templates/';
    $ret = emUnZip($temp_file, $unzip_path, 'tpl');
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            $Template_Model->upCallback($alias);
            Ret::success();
            break;
        case 1:
        case 2:
        Ret::error('更新失败');
            break;
        case 3:
            Ret::error('更新失败');
            break;
        default:
            Ret::error('更新失败');
    }
}
