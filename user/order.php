<?php
/**
 * @package ESHOP
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
    emMsg('请使用游客查单页查询订单', EM_URL . 'user/visitors.php');
}

if($action == 'download'){
    loginAuth::checkLogin(NULL, 'user');

    $db = Database::getInstance();
    $id = Input::getIntVar('order_list_id'); // 子订单ID
    $orderList = $db->once_fetch_array(
        "SELECT ol.id
         FROM " . DB_PREFIX . "order_list ol
         INNER JOIN " . DB_PREFIX . "order o ON ol.order_id = o.id
         WHERE ol.id = {$id} AND o.user_id = " . UID . " AND o.delete_time IS NULL
         LIMIT 1"
    );
    if (empty($orderList)) {
        emMsg('订单不存在或无权下载', EM_URL . 'user/order.php');
    }
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
    loginAuth::checkLogin(NULL, 'user');

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');
    $out_trade_no_sql = $db->escape_string($out_trade_no);
    $order = $db->once_fetch_array("select * from {$db_prefix}order where out_trade_no = '{$out_trade_no_sql}' and user_id = " . UID . " and delete_time is null");
    if (empty($order)) {
        emMsg('订单不存在或无权查看', EM_URL . 'user/order.php');
    }
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order['id']}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");
    $func = "orderDetail" . ucfirst($goods['type']);

    include View::getUserView('_header');
    $func($goods, $order, $child_order);
    include View::getUserView('_footer');
    View::output();
}

if ($action == 'cancel') {
    loginAuth::checkLogin(NULL, 'user');

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');

    if (empty($out_trade_no)) {
        emMsg('订单号不能为空', EM_URL . 'user/order.php');
    }

    $out_trade_no_sql = $db->escape_string($out_trade_no);
    $order = $db->once_fetch_array("select * from {$db_prefix}order where out_trade_no = '{$out_trade_no_sql}' and user_id = " . UID . " and delete_time is null limit 1");
    if (empty($order)) {
        emMsg('订单不存在或无权操作', EM_URL . 'user/order.php');
    }

    if (!empty($order['pay_time']) || (int)($order['status'] ?? 0) !== 0 || (int)($order['pay_status'] ?? 0) !== 0) {
        emMsg('当前订单无法取消', EM_URL . 'user/order.php?action=detail&out_trade_no=' . rawurlencode($out_trade_no));
    }

    if (!$orderModel->cancelPendingOrder($order['id'])) {
        emMsg('订单状态已变更，请刷新后重试', EM_URL . 'user/order.php?action=detail&out_trade_no=' . rawurlencode($out_trade_no));
    }

    emMsg('订单已取消', EM_URL . 'user/order.php');
}


