<?php
/**
 * The productf management
 *
 * @package EMLOG
 * @link https://www.emlog.net
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$orderModel = new Order_Model();
$Sort_Model = new Sort_Model();
$User_Model = new User_Model();
$MediaSort_Model = new MediaSort_Model();
$Template_Model = new Template_Model();

// 订单列表
if (empty($action)) {
    $page = Input::getIntVar('page', 1);



    $br = '<a href="./">控制台</a><a href="./order.php">订单管理</a><a><cite>商品订单</cite></a>';

    include View::getAdmView(User::haveEditPermission() ? 'header' : 'uc_header');
    require_once View::getAdmView(User::haveEditPermission() ? 'order' : 'uc_order');
    include View::getAdmView(User::haveEditPermission() ? 'footer' : 'uc_footer');
    View::output();
}



if ($action == 'index') {

    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);
    $start = ($page - 1) * $limit;
    $sort1 = Input::getStrVar('field', 'uid');
    $sort2 = Input::getStrVar('order', 'desc');
    $order_by = "order by {$sort1} {$sort2}";


    $where  = [];
    $where['email_username'] = Input::getStrVar('email_username');
    $where['out_trade_no'] = Input::getStrVar('out_trade_no');
    $where['goods_title'] = Input::getStrVar('goods_title');
    $where['client_ip'] = Input::getStrVar('client_ip');
    $where['pay_status'] = Input::getStrVar('pay_status');
    $where['create_time'] = Input::getStrVar('create_time');

    $orderNum = $orderModel->getOrderNum($where);
    $orderTotalAmount = $orderModel->getOrderTotalAmount($where);
    $order = $orderModel->getOrderForAdmin($start, $limit, $where);
    foreach($order as $key => $val){
        $order[$key]['create_time'] = empty($val['create_time']) ? '' : date('Y-m-d H:i:s', $val['create_time']);
        $order[$key]['pay_time'] = empty($val['pay_time']) ? '' : date('Y-m-d H:i:s', $val['pay_time']);
        $order[$key]['amount'] = number_format($val['amount'], 2);
    }

    $result = [
        'code' => 0,
        'msg' => '',
        'count' => $orderNum,
        'data' => $order,
        'stats' => [
            'total_count' => $orderNum,
            'total_amount' => number_format($orderTotalAmount, 2)
        ]
    ];
    echo json_encode($result);
    exit;
}

if($action == 'get_deliver'){
    $order_id = Input::getIntVar('order_id');
    $order_list_id = Input::getIntVar('order_list_id');
    $db = Database::getInstance();
    $sql = "select * from " . DB_PREFIX . "order where id={$order_id}";
    $order = $db->once_fetch_array($sql);
    $sql = "select * from " . DB_PREFIX . "deliver where order_list_id={$order_list_id} limit 5";
    $deliver = $db->fetch_all($sql);

    $order_deliver = "";
    if(empty($order['device'])){
        foreach($deliver as $val){
            if(!empty($order_deliver)){
                $order_deliver .= "<hr>";
            }
            $order_deliver .= $val['content'];
        }
    }else{
        $order_deliver = $order['device'];
    }
    $order_deliver = empty($order_deliver) ? '无' : $order_deliver;

    $data = [
        'order_deliver' => $order_deliver
    ];

    output::ok($data);

}

if($action == 'download'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $goods_type = Input::getStrVar('goods_type');
    $order_list_id = Input::getIntVar('order_list_id');
    $func = "adm_download_deliver_content_{$goods_type}";


    if (function_exists($func)) {
        $func($db, $db_prefix, $order_list_id);
    }
}

// 补单
if($action == 'repay'){
    LoginAuth::checkToken();
    $out_trade_no = Input::postStrVar('out_trade_no');
    $payController = new Pay_Controller();
    $payController->repay($out_trade_no);
    output::ok();
}

if ($action == 'reconcile_order') {
    LoginAuth::checkToken();

    $reference = Input::postStrVar('out_trade_no');
    $result = $orderModel->reconcileOrderPayment($reference);
    if (empty($result['ok'])) {
        Output::error($result['msg'] ?? '查单失败');
    }

    Output::ok([
        'msg' => '查单成功，订单状态已同步',
        'result' => $result,
    ]);
}

if ($action == 'reconcile_recent_paid') {
    LoginAuth::checkToken();

    $limit = Input::postIntVar('limit', 50);
    $result = $orderModel->reconcileRecentYifutPaidOrders($limit, 0);
    if (empty($result['ok'])) {
        Output::error($result['msg'] ?? '最近支付对账失败');
    }

    $message = "检查 {$result['checked']} 笔，修复 {$result['fixed']} 笔，跳过 {$result['skipped']} 笔，失败 {$result['failed']} 笔";
    if (!empty($result['messages'])) {
        $message .= '。' . implode('；', array_slice($result['messages'], 0, 5));
    }

    Output::ok([
        'msg' => $message,
        'result' => $result,
    ]);
}

// 删除订单
if ($action == 'del') {

    $ids = Input::postStrVar('ids');

    $timestamp = time();
    $sql = "UPDATE " . DB_PREFIX . "order set delete_time={$timestamp} where id IN ({$ids})";

    $db = Database::getInstance();
    $db->query($sql);
    output::ok();

}

// 一键删除未支付订单
if ($action == 'del_unpaid') {
    LoginAuth::checkToken();

    $db = Database::getInstance();
    $timestamp = time();

    // 查询未支付订单（pay_time为空或0）
    $sql = "SELECT id, amount FROM " . DB_PREFIX . "order WHERE (pay_time IS NULL OR pay_time = 0 OR pay_time = '') AND delete_time is NULL";
    $unpaid_orders = $db->query($sql);

    $count = 0;
    $total_amount = 0;
    $order_ids = [];

    while ($row = $db->fetch_array($unpaid_orders)) {
        $order_ids[] = $row['id'];
        $total_amount += $row['amount'];
        $count++;
    }

    if ($count > 0) {
        $ids = implode(',', $order_ids);
        $sql = "UPDATE " . DB_PREFIX . "order SET delete_time={$timestamp} WHERE id IN ({$ids})";
        $db->query($sql);
    }

    $data = [
        'count' => $count,
        'total_amount' => number_format($total_amount / 100, 2),
        'message' => "成功删除 {$count} 个未支付订单，总金额 ¥" . number_format($total_amount / 100, 2)
    ];

    output::ok($data);
}



if ($action == 'deliver') {
    $order_id = Input::getIntVar('order_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $order = $db->once_fetch_array("select * from {$db_prefix}order where id = {$order_id}");
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order_id}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");
    doAction('adm_deliver_view', $db, $db_prefix, $goods, $order, $child_order);
}

if ($action == 'detail') {
    $order_id = Input::getIntVar('order_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $order = $db->once_fetch_array("select * from {$db_prefix}order where id = {$order_id}");
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order_id}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");


    $user = [];
    if (!empty($order['user_id'])) {
        $user = $db->once_fetch_array("select * from {$db_prefix}user where uid = {$order['user_id']}");
    }
    $func = "adminOrderDetail" . ucfirst($goods['type']);
    include View::getAdmView('open_head');
    $func($goods, $order, $child_order, $user);
    include View::getAdmView('open_foot');
    View::output();
}
