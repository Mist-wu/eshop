<?php
/**
 * links
 * @package ESHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Link_Model = new Link_Model();

if (empty($action)) {
    $links = $Link_Model->getLinks();
    $br = '<a href="./">控制台</a><a href="./article.php">博客管理</a><a><cite>友情链接</cite></a>';
    include View::getAdmView('header');
    require_once(View::getAdmView('templates/default/link/index'));
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $list = $Link_Model->getLinks();
    output::data($list, count($list));
}

if($action == 'add'){
    include View::getAdmView('open_head');
    require_once(View::getAdmView('templates/default/link/add'));
    include View::getAdmView('open_foot');
    View::output();
}
if($action == 'edit'){
    $id = Input::getIntVar('id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "SELECT * FROM {$db_prefix}link where id = {$id}";
    $info = $db->once_fetch_array($sql);
    include View::getAdmView('open_head');
    require_once(View::getAdmView('templates/default/link/edit'));
    include View::getAdmView('open_foot');
    View::output();
}
if($action == 'add_ajax'){
    $siteName = Input::postStrVar('sitename');
    $siteUrl = Input::postStrVar('siteurl');
    $icon = Input::postStrVar('icon');
    $description = Input::postStrVar('description');

    if ($siteName == '' || $siteUrl == '') {
        output::error('名称和链接不能为空');
    }

    if (!preg_match("/^http|ftp.+$/i", $siteUrl)) {
        $siteUrl = 'https://' . $siteUrl;
    }

    $data = [
        'sitename'    => $siteName,
        'siteurl'     => $siteUrl,
        'icon'        => $icon,
        'description' => $description
    ];

    $Link_Model->addLink($data);

    $CACHE->updateCache('link');
    output::ok();
}


if ($action == 'link_taxis') {
    $link = isset($_POST['link']) ? $_POST['link'] : '';

    if (empty($link)) {
        Output::error('没有可排序的链接');
    }

    foreach ($link as $key => $value) {
        $value = (int)$value;
        $key = (int)$key;
        $Link_Model->updateLink(array('taxis' => $key), $value);
    }
    $CACHE->updateCache('link');
    Output::ok();
}

if ($action == 'edit_ajax') {
    $siteName = Input::postStrVar('sitename');
    $siteUrl = Input::postStrVar('siteurl');
    $icon = Input::postStrVar('icon');
    $description = Input::postStrVar('description');
    $linkId = Input::postIntVar('id');

    if ($siteName == '' || $siteUrl == '') {
        output::error('名称和链接不能为空');
    }

    if (!preg_match("/^http|ftp.+$/i", $siteUrl)) {
        $siteUrl = 'https://' . $siteUrl;
    }

    $data = [
        'sitename'    => $siteName,
        'siteurl'     => $siteUrl,
        'icon'        => $icon,
        'description' => $description
    ];

    $Link_Model->updateLink($data, $linkId);

    $CACHE->updateCache('link');
    output::ok();
}

if ($action == 'del') {
    LoginAuth::checkToken();
    $ids = Input::postStrVar('ids');

    $ids = explode(',', $ids);
    foreach($ids as $val){
        $Link_Model->deleteLink($val);
    }


    $CACHE->updateCache('link');
    output::ok();
}

if ($action == 'hide') {
    $linkId = Input::getIntVar('linkid');

    $Link_Model->updateLink(['hide' => 'y'], $linkId);

    $CACHE->updateCache('link');
    emDirect('./link.php');
}

if ($action == 'show') {
    $linkId = Input::getIntVar('linkid');

    $Link_Model->updateLink(['hide' => 'n'], $linkId);

    $CACHE->updateCache('link');
    emDirect('./link.php');
}
