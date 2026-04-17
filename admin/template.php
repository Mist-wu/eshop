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
        $list[$key]['preview'] = '';
    }
    
    output::data($list, count($list));
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
