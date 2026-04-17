<?php
/**
 * 会员等级管理
 * @package ESHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$userTierModel = new User_Tier_Model();

if (empty($action)) {
    $page = Input::getIntVar('page', 1);

    $members = $userTierModel->getTiers($page);
    $dataCount = $userTierModel->getTierCount();
    $pageurl = pagination($dataCount, Option::get('admin_article_perpage_num'), $page, "./user.php?page=");


    $br = '<a href="./">控制台</a><a href="./user.php">用户管理</a><a><cite>会员等级</cite></a>';
    include View::getAdmView('header');
    require_once View::getAdmView('templates/default/member/index');
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "SELECT * FROM {$db_prefix}user_tier order by id asc";
    $list = $db->fetch_all($sql);
    output::data($list, count($list));
}

if ($action == 'add_ajax') {
    LoginAuth::checkToken();
    $tier_name = Input::postStrVar('name');
    $discount = Input::postIntVar('discount', 0);
    $userTierModel->add($tier_name, $discount);
    output::ok();
}
if ($action == 'add') {
    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/member/add');
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'edit_ajax') {
    LoginAuth::checkToken();
    $id = Input::postIntVar('id');
    $tier_name = Input::postStrVar('name');
    $discount = Input::postIntVar('discount', 0);
    $userTierModel->edit($id, $tier_name, $discount);
    output::ok();
}

if ($action == 'edit') {
    $id = Input::getIntVar('id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "select * from {$db_prefix}user_tier where id = {$id}";
    $info = $db->once_fetch_array($sql);
    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/member/edit');
    include View::getAdmView('open_foot');
    View::output();
}



if ($action == 'del') {
    LoginAuth::checkToken();
    $ids = Input::postStrVar('ids');
    $ids = explode(',', $ids);
    foreach($ids as $val){
        $userTierModel->del($val);
    }
    output::ok();
}
