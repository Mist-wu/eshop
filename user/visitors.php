<?php



require_once '../init.php';

$action = Input::getStrVar('action');
$orderModel = new Order_Model();

function emVisitorEnsureSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

function emVisitorAuthorizedOrderSessionKey() {
    return 'eshop_visitor_authorized_orders';
}

function emVisitorLegacyAuthorizedOrderSessionKey() {
    return substr_replace(emVisitorAuthorizedOrderSessionKey(), 'm', 1, 0);
}

function emVisitorGetAuthorizedOrders() {
    emVisitorEnsureSession();

    $key = emVisitorAuthorizedOrderSessionKey();
    $legacyKey = emVisitorLegacyAuthorizedOrderSessionKey();
    $current = isset($_SESSION[$key]) && is_array($_SESSION[$key]) ? $_SESSION[$key] : [];
    if (empty($current) && isset($_SESSION[$legacyKey]) && is_array($_SESSION[$legacyKey])) {
        $current = $_SESSION[$legacyKey];
    }
    $now = time();
    $authorized = [];

    foreach ($current as $orderId => $row) {
        $orderId = (int)$orderId;
        $expiresAt = (int)($row['expires_at'] ?? 0);
        $outTradeNo = (string)($row['out_trade_no'] ?? '');
        if ($orderId <= 0 || $expiresAt <= $now || $outTradeNo === '') {
            continue;
        }
        $authorized[$orderId] = [
            'out_trade_no' => $outTradeNo,
            'expires_at' => $expiresAt,
        ];
    }

    $_SESSION[$key] = $authorized;
    unset($_SESSION[$legacyKey]);
    return $authorized;
}

function emVisitorAuthorizeOrder($order) {
    $orderId = (int)($order['id'] ?? 0);
    $outTradeNo = (string)($order['out_trade_no'] ?? '');
    if ($orderId <= 0 || $outTradeNo === '') {
        return;
    }

    $authorized = emVisitorGetAuthorizedOrders();
    $authorized[$orderId] = [
        'out_trade_no' => $outTradeNo,
        'expires_at' => time() + 3600,
    ];

    $_SESSION[emVisitorAuthorizedOrderSessionKey()] = $authorized;
}

function emVisitorAuthorizeOrders($orders) {
    if (empty($orders) || !is_array($orders)) {
        return;
    }

    foreach ($orders as $order) {
        if (!is_array($order)) {
            continue;
        }
        emVisitorAuthorizeOrder($order);
    }
}

function emVisitorLocalToken() {
    return trim((string)($_COOKIE['EM_LOCAL'] ?? ''));
}

function emVisitorHasLocalAccess($order) {
    $local = emVisitorLocalToken();
    return $local !== ''
        && !empty($order['em_local'])
        && hash_equals((string)$order['em_local'], $local);
}

function emVisitorHasSessionAccess($order) {
    $orderId = (int)($order['id'] ?? 0);
    $outTradeNo = (string)($order['out_trade_no'] ?? '');
    if ($orderId <= 0 || $outTradeNo === '') {
        return false;
    }

    $authorized = emVisitorGetAuthorizedOrders();
    if (empty($authorized[$orderId]['out_trade_no'])) {
        return false;
    }

    return hash_equals((string)$authorized[$orderId]['out_trade_no'], $outTradeNo);
}

function emVisitorAuthRequirement() {
    $contactEnabled = Option::get('guest_query_contact_switch') != 'n';
    $passwordEnabled = Option::get('guest_query_password_switch') == 'y';

    return [
        'contact' => $contactEnabled,
        'password' => $passwordEnabled,
    ];
}

function emVisitorValidateSearchCredentials($contact, $password) {
    $requirement = emVisitorAuthRequirement();

    if ($requirement['contact'] && $contact === '') {
        return ['ok' => false, 'msg' => '请输入联系方式'];
    }
    if ($requirement['password'] && $password === '') {
        return ['ok' => false, 'msg' => '请输入订单密码'];
    }

    return ['ok' => true, 'msg' => 'ok'];
}

function emVisitorCredentialsMatchOrder($order, $contact, $password) {
    $requirement = emVisitorAuthRequirement();
    if (!$requirement['contact'] && !$requirement['password']) {
        return true;
    }

    if ($requirement['contact']) {
        $expected = (string)($order['contact'] ?? '');
        if ($contact === '' || !hash_equals($expected, $contact)) {
            return false;
        }
    }

    if ($requirement['password']) {
        $expected = (string)($order['pwd'] ?? '');
        if ($password === '' || !hash_equals($expected, $password)) {
            return false;
        }
    }

    return true;
}

function emVisitorCanAccessOrder($order) {
    return emVisitorHasLocalAccess($order) || emVisitorHasSessionAccess($order);
}

if (empty($action)) {
    // 获取游客查单配置
    $visitor_required = [];

    $guest_query_contact_switch = Option::get('guest_query_contact_switch');
    $guest_query_contact_switch = $guest_query_contact_switch != 'n' ? 'y' : 'n';

    $guest_query_password_switch = Option::get('guest_query_password_switch');
    $guest_query_password_switch = $guest_query_password_switch == 'y' ? 'y' : 'n';

    if($guest_query_contact_switch == 'y'){
        $contact_type = Option::get('guest_query_contact_type') ?: 'any';
        $title_map = [
            'any' => '联系方式',
            'qq' => 'QQ号码',
            'email' => '邮箱地址',
            'phone' => '手机号码'
        ];
        $visitor_required['contact'] = [
            'type' => 'contact',
            'title' => $title_map[$contact_type] ?? '联系方式',
            'contact_type' => $contact_type,
            'placeholder_query' => Option::get('guest_query_contact_placeholder_query') ?: '请输入您下单时填写的联系方式'
        ];
    }

    if($guest_query_password_switch == 'y'){
        $visitor_required['password'] = [
            'type' => 'password',
            'title' => '订单密码',
            'placeholder_query' => Option::get('guest_query_password_placeholder_query') ?: '请输入您设置的订单密码'
        ];
    }

    include View::getUserView('_header');
    require_once(View::getUserView('visitors'));
    include View::getUserView('_footer');
    View::output();
}

if($action == 'visitors_order'){
    $db = Database::getInstance();
    $prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');
    $order_id = Input::getIntVar('order_id');

    if (!empty($out_trade_no)) {
        $order = $orderModel->getOrderByOrderNo($out_trade_no, true);
    } elseif (!empty($order_id)) {
        $order = $db->once_fetch_array("SELECT * FROM {$prefix}order WHERE id = {$order_id} LIMIT 1");
    } else {
        emMsg('订单号不能为空', EM_URL . 'user/visitors.php');
    }

    if(empty($order)){
        emMsg('订单不存在', EM_URL . 'user/visitors.php');
    }

    if (!emVisitorCanAccessOrder($order)) {
        emMsg('订单不存在或无权查看', EM_URL . 'user/visitors.php');
    }

    emVisitorAuthorizeOrder($order);

    $child_order = $db->once_fetch_array("SELECT * FROM {$prefix}order_list WHERE order_id = {$order['id']} LIMIT 1");
    if (empty($child_order)) {
        emMsg('订单详情不存在', EM_URL . 'user/visitors.php');
    }

    $goods = $db->once_fetch_array("SELECT * FROM {$prefix}goods WHERE id = {$child_order['goods_id']} LIMIT 1");
    if (empty($goods)) {
        emMsg('商品不存在或已删除', EM_URL . 'user/visitors.php');
    }

    $func = "orderDetail" . ucfirst($goods['type']);
    if (!function_exists($func)) {
        emMsg('当前商品类型暂不支持查看详情', EM_URL . 'user/visitors.php');
    }

    $GLOBALS['EM_VISITOR_ORDER_VIEW'] = true;
    include View::getUserView('_header');
    $func($goods, $order, $child_order);
    include View::getUserView('_footer');
    unset($GLOBALS['EM_VISITOR_ORDER_VIEW']);
    View::output();
}

// 根据下单信息查询订单列表
if($action == 'visitors_search_by_info'){
    $contact = Input::postStrVar('contact');
    $password = Input::postStrVar('password');
    $page = Input::postIntVar('page', 1);

    // 获取配置
    $guest_query_contact_switch = Option::get('guest_query_contact_switch');
    $guest_query_contact_switch = $guest_query_contact_switch != 'n' ? 'y' : 'n';

    $guest_query_password_switch = Option::get('guest_query_password_switch');
    $guest_query_password_switch = $guest_query_password_switch == 'y' ? 'y' : 'n';

    // 验证必填字段
    $validation = emVisitorValidateSearchCredentials($contact, $password);
    if (!$validation['ok']) {
        Ret::error($validation['msg']);
    }

    // 查询订单列表
    $orders = $orderModel->getOrdersByVisitorInfo($contact, $password, $page, 10);
    $total = $orderModel->getOrdersCountByVisitorInfo($contact, $password);
    emVisitorAuthorizeOrders($orders);

    if(empty($orders) && $page == 1){
        Ret::error('未找到匹配的订单，请检查输入信息是否正确');
    }

    Ret::success('查询成功', [
        'list' => $orders,
        'total' => $total,
        'page' => $page,
        'pageSize' => 10,
        'hasMore' => ($page * 10) < $total
    ]);
}

// 根据订单号和游客信息查询订单
if($action == 'visitors_search_order'){
    $order_no = Input::postStrVar('order_no');
    $contact = Input::postStrVar('contact');
    $password = Input::postStrVar('password');

    if(empty($order_no)){
        Ret::error('请输入订单编号');
    }

    // 根据站内订单号或支付单号查询，精确查询时允许命中已自动取消的订单
    $order = $orderModel->getOrderByOrderNo($order_no, true);

    if(empty($order)){
        Ret::error('未找到匹配的订单，请检查站内订单号或支付订单号是否正确');
    }

    $hasLocalAccess = emVisitorHasLocalAccess($order);
    $hasCredentialAccess = emVisitorCredentialsMatchOrder($order, $contact, $password);
    if (!$hasLocalAccess && !$hasCredentialAccess) {
        $validation = emVisitorValidateSearchCredentials($contact, $password);
        if (!$validation['ok']) {
            Ret::error($validation['msg']);
        }
        Ret::error('未找到匹配的订单，请检查订单编号与下单信息是否正确');
    }

    emVisitorAuthorizeOrder($order);

    // 获取订单详细信息
    $order_list = $orderModel->getOrderList($order['id']);
    $prefix = DB_PREFIX;
    $db = Database::getInstance();

    $orders = [];
    foreach($order_list as $key => $row){
        $goods_sql = "SELECT title, type, cover FROM {$prefix}goods WHERE id = {$row['goods_id']}";
        $goods = $db->once_fetch_array($goods_sql);

        $order_item = [
            'id' => (int)$order['id'],
            'out_trade_no' => $order['out_trade_no'],
            'up_no' => $order['up_no'] ?? '',
            'trade_no' => $order['trade_no'] ?? '',
            'api_trade_no' => $order['api_trade_no'] ?? '',
            'status' => $order['status'],
            'status_text' => orderStatusText($order['status']),
            'amount' => number_format($order['amount'] / 100, 2),
            'create_time' => $order['create_time'],
            'pay_time' => $order['pay_time'] ?? '',
            'pay_time_text' => empty($order['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $order['pay_time']),
            'payment' => $order['payment'] ?? '',
            'goods_id' => $row['goods_id'],
            'title' => $goods['title'] ?? '商品已删除',
            'type' => $goods['type'] ?? '',
            'cover' => $goods['cover'] ?? '',
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price'] / 100,
            'goods_url' => Url::goods($row['goods_id']),
            'url' => Url::goods($row['goods_id']),
            'detail_url' => EM_URL . 'user/visitors.php?action=visitors_order&out_trade_no=' . rawurlencode($order['out_trade_no']),
        ];

        // 处理规格信息
        $order_item['attr_spec'] = empty($row['attr_spec']) ? '默认规格' : $row['attr_spec'];

        // 处理附加选项
        $_text = empty($row['attach_user']) ? [] : json_decode($row['attach_user'], true);
        $order_item['attach_user_text'] = '';
        if(!empty($_text)){
            foreach($_text as $k => $v){
                $order_item['attach_user_text'] .= $k . "：" . $v . "；";
            }
        }

        $orders[] = $order_item;
    }

    Ret::success('查询成功', [
        'list' => $orders,
        'total' => count($orders),
        'hasMore' => false
    ]);
}

// 显示订单列表
if($action == 'visitors_order_list'){
    $contact = Input::getStrVar('contact');
    $password = Input::getStrVar('password');
    $page = Input::getIntVar('page', 1);

    if(empty($contact) && empty($password)){
        emMsg('查询信息不能为空');
    }

    $validation = emVisitorValidateSearchCredentials($contact, $password);
    if (!$validation['ok']) {
        emMsg($validation['msg']);
    }

    $orders = $orderModel->getOrdersByVisitorInfo($contact, $password, $page, 10);
    $order_count = $orderModel->getOrdersCountByVisitorInfo($contact, $password);
    emVisitorAuthorizeOrders($orders);

    include View::getUserView('_header');
    require_once(View::getUserView('visitors_order'));
    include View::getUserView('_footer');
    View::output();
}

// 根据关键词查询订单数量
if($action == 'visitors_search_order_count'){
    $keyword = Input::postStrVar('keyword');
    if(empty($keyword)){
        Ret::error('请输入查询信息');
    }
    $count = $orderModel->getYoukeOrderCount($keyword);
    Ret::success('查询成功', $count);
}

// 根据浏览器缓存查询订单列表
if($action == 'get_local_orders'){
    $local = Input::postStrVar('local');
    $page = Input::postIntVar('page', 1);

    $cookieLocal = emVisitorLocalToken();
    if(empty($local) || $cookieLocal === '' || !hash_equals($cookieLocal, $local)){
        Ret::error('本地标识无效');
    }

    $orders = $orderModel->getOrdersByLocal($cookieLocal, $page, 10);
    $total = $orderModel->getOrdersCountByLocal($cookieLocal);
    emVisitorAuthorizeOrders($orders);

    Ret::success('查询成功', [
        'list' => $orders,
        'total' => $total,
        'page' => $page,
        'pageSize' => 10,
        'hasMore' => ($page * 10) < $total
    ]);
}

if($action == 'cancel'){
    $out_trade_no = Input::postStrVar('out_trade_no');
    if (empty($out_trade_no)) {
        Ret::error('订单号不能为空');
    }

    $order = $orderModel->getOrderByOrderNo($out_trade_no);
    if (empty($order)) {
        Ret::error('订单不存在或已失效');
    }

    $authorized = emVisitorCanAccessOrder($order);

    if (!$authorized) {
        $contact = Input::postStrVar('contact');
        $password = Input::postStrVar('password');
        if (emVisitorCredentialsMatchOrder($order, $contact, $password)) {
            $authorized = true;
            emVisitorAuthorizeOrder($order);
        }
    }

    if (!$authorized) {
        Ret::error('无权取消该订单');
    }

    if (!empty($order['pay_time']) || (int)($order['status'] ?? 0) !== 0 || (int)($order['pay_status'] ?? 0) !== 0) {
        Ret::error('当前订单无法取消');
    }

    if (!$orderModel->cancelPendingOrder($order['id'])) {
        Ret::error('订单状态已变更，请刷新后重试');
    }

    Ret::success('订单已取消');
}


if($action == 'sdk'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');
    $out_trade_no_sql = $db->escape_string($out_trade_no);
    $order = $db->once_fetch_array("select * from {$db_prefix}order where out_trade_no = '{$out_trade_no_sql}'");
    if (empty($order) || !emVisitorCanAccessOrder($order)) {
        emMsg('订单不存在或无权查看', EM_URL . 'user/visitors.php');
    }
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order['id']}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");
    doAction('view_order_detail', $db, $db_prefix, $goods, $order, $child_order);
    die;
}
