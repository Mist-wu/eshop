<?php
/**
 * @package EMSHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

//require_once 'globals.php';
require_once '../init.php';

$orderModel = new Order_Model();
$Sort_Model = new Sort_Model();
$User_Model = new User_Model();
$MediaSort_Model = new MediaSort_Model();
$Template_Model = new Template_Model();

$sta_cache = $CACHE->readCache('sta');
$action = Input::getStrVar('action');

// 订单列表
if (empty($action)) {
    loginAuth::checkLogin(NULL, 'user');

    // 获取分页参数
    $page = Input::getIntVar('page', 1);
    
    // 获取订单数据
    $list = $orderModel->getOrderForHome(UID, $page, 10, null, null);
    $orders_count = $orderModel->getOrderCountForHome(UID, null, null);
    
    // 计算分页
    $perpage_num = Option::get('admin_article_perpage_num') ?: 10;
    $pageurl = pagination($orders_count, $perpage_num, $page, "order.php?page=");
    
    include View::getUserView('_header');
    require_once View::getUserView('order');
    include View::getUserView('_footer');
    View::output();
}
if($action == 'index'){
    $page = Input::getIntVar('page', 1);
    $status = Input::getStrVar('status'); // 允许为空值
    $search = Input::getStrVar('search'); // 允许为空值
    
    $orders = $orderModel->getOrderForHome(UID, $page, 10, $status, $search);
    $orders_count = $orderModel->getOrderCountForHome(UID, $status, $search);
    Ret::success('', [
        'orders' => $orders,
        'orders_count' => $orders_count
    ]);
}
// 订单列表
if ($action == 'search') {
    $tab = 'search';
    $pwd = Input::getStrVar('pwd');
    if(empty($pwd)){
        include View::getUserView('header');
        require_once View::getUserView('order');
        include View::getUserView('footer');
        View::output();
    }
    $page = Input::getIntVar('page', 1);
    $orderNum = $orderModel->getYoukeOrderNum($pwd);
    $order = $orderModel->getYoukeOrderForHome($page, $pwd);
    $subPage = '';
    foreach ($_GET as $key => $val) {
        $subPage .= $key != 'page' ? "&$key=$val" : '';
    }
    $pageurl = pagination($orderNum, Option::get('admin_article_perpage_num'), $page, "order.php?{$subPage}&page=");
    $GLOBALS['mode_payment'] = [];
    doAction('mode_payment');
    if(isset($GLOBALS['mode_payment'][0])){
        $GLOBALS['mode_payment'][0]['active'] = true;
    }
    $mode_payment = $GLOBALS['mode_payment'];
    include View::getUserView('header');
    require_once View::getUserView('order');
    include View::getUserView('footer');
    View::output();
}

if($action == 'download'){
    $db = Database::getInstance();
    $id = Input::getIntVar('order_list_id'); // 子订单ID
    $sql = "SELECT * from " . DB_PREFIX . "deliver WHERE order_list_id={$id} order by id asc";
    $res = $db->query($sql);
    $content = "";
    while ($row = $db->fetch_array($res)) {
        $content .= $row['content'] . "\r\n";
    }
    $date = date('YmdHis');
    $filename = '卡密-' . $date . '.txt';
    // 设置HTTP头
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    // 输出内容
    echo $content;
    exit;
}

// 订单详情页
if($action == 'detail'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');
    $order = $db->once_fetch_array("select * from {$db_prefix}order where out_trade_no = '{$out_trade_no}'");
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order['id']}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");
    $func = "orderDetail" . ucfirst($goods['type']);

    include View::getUserView('_header');
    $func($order, $child_order, $goods);
    include View::getUserView('_footer');
    View::output();
}


