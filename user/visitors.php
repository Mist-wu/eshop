<?php



require_once '../init.php';

$action = Input::getStrVar('action');
$orderModel = new Order_Model();

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

    $order_id = Input::getIntVar('order_id');
    if(empty($order_id)){
        emMsg('订单ID不能为空');
    }

    $order = $orderModel->getOrderInfoId($order_id);
    if(empty($order)){
        emMsg('订单不存在');
    }

    // 构建订单列表数据（单个订单）
    $order['status_text'] = orderStatusText($order['status']);
    $order['amount'] /= 100;
    $order['pay_time_text'] = empty($order['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $order['pay_time']);

    $order_list = $orderModel->getOrderList($order_id);
    $prefix = DB_PREFIX;
    $db = Database::getInstance();

    foreach($order_list as $key => $row){
        $goods_sql = "SELECT title, type, cover FROM {$prefix}goods WHERE id = {$row['goods_id']}";
        $goods = $db->once_fetch_array($goods_sql);

        $order_list[$key]['title'] = $goods['title'] ?? '商品已删除';
        $order_list[$key]['type'] = $goods['type'] ?? '';
        $order_list[$key]['cover'] = $goods['cover'] ?? '';
        $order_list[$key]['goods_url'] = Url::goods($row['goods_id']);
        $order_list[$key]['url'] = Url::goods($row['goods_id']);
        $order_list[$key]['out_trade_no'] = $order['out_trade_no'];
        $order_list[$key]['up_no'] = $order['up_no'] ?? '';
        $order_list[$key]['create_time'] = $order['create_time'];
        $order_list[$key]['pay_time'] = $order['pay_time'] ?? '';
        $order_list[$key]['pay_time_text'] = $order['pay_time_text'];
        $order_list[$key]['status'] = $order['status'];
        $order_list[$key]['status_text'] = $order['status_text'];
        $order_list[$key]['amount'] = $order['amount'];
        $order_list[$key]['payment'] = $order['payment'] ?? '';

        $_text = empty($row['attach_user']) ? [] : json_decode($row['attach_user'], true);
        $order_list[$key]['attach_user_text'] = '';
        if(!empty($_text)){
            foreach($_text as $k => $v){
                $order_list[$key]['attach_user_text'] .= $k . "：" . $v . "；";
            }
        }

        // 处理规格信息
        if (in_array($goods['type'] ?? '', ['em_auto', 'em_manual']) && function_exists('emFormatSkuOptionIds')) {
            $order_list[$key]['attr_spec'] = emFormatSkuOptionIds($row['goods_id'], $row['sku'] ?? '');
        } else {
            $order_list[$key]['attr_spec'] = empty($row['attr_spec']) ? '默认规格' : $row['attr_spec'];
        }

        $order_list[$key]['unit_price'] /= 100;
    }

    $orders = $order_list;
    $order_count = 1;

    include View::getUserView('_header');
    require_once(View::getUserView('visitors_order'));
    include View::getUserView('_footer');
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
    if($guest_query_contact_switch == 'y' && empty($contact)){
        Ret::error('请输入联系方式');
    }

    if($guest_query_password_switch == 'y' && empty($password)){
        Ret::error('请输入订单密码');
    }

    // 查询订单列表
    $orders = $orderModel->getOrdersByVisitorInfo($contact, $password, $page, 10);
    $total = $orderModel->getOrdersCountByVisitorInfo($contact, $password);

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

    if(empty($order_no)){
        Ret::error('请输入订单编号');
    }

    // 直接根据订单号查询
    $order = $orderModel->getOrderByOrderNo($order_no);

    if(empty($order)){
        Ret::error('未找到匹配的订单，请检查订单编号是否正确');
    }

    // 获取订单详细信息
    $order_list = $orderModel->getOrderList($order['id']);
    $prefix = DB_PREFIX;
    $db = Database::getInstance();

    $orders = [];
    foreach($order_list as $key => $row){
        $goods_sql = "SELECT title, type, cover FROM {$prefix}goods WHERE id = {$row['goods_id']}";
        $goods = $db->once_fetch_array($goods_sql);

        $order_item = [
            'out_trade_no' => $order['out_trade_no'],
            'up_no' => $order['up_no'] ?? '',
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
        ];

        // 处理规格信息
        if (in_array($goods['type'] ?? '', ['em_auto', 'em_manual']) && function_exists('emFormatSkuOptionIds')) {
            $order_item['attr_spec'] = emFormatSkuOptionIds($row['goods_id'], $row['sku'] ?? '');
        } else {
            $order_item['attr_spec'] = empty($row['attr_spec']) ? '默认规格' : $row['attr_spec'];
        }

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

    $orders = $orderModel->getOrdersByVisitorInfo($contact, $password, $page, 10);
    $order_count = $orderModel->getOrdersCountByVisitorInfo($contact, $password);

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

    if(empty($local)){
        Ret::error('本地标识不能为空');
    }

    $orders = $orderModel->getOrdersByLocal($local, $page, 10);
    $total = $orderModel->getOrdersCountByLocal($local);

    Ret::success('查询成功', [
        'list' => $orders,
        'total' => $total,
        'page' => $page,
        'pageSize' => 10,
        'hasMore' => ($page * 10) < $total
    ]);
}


if($action == 'sdk'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no = Input::getStrVar('out_trade_no');
    $order = $db->once_fetch_array("select * from {$db_prefix}order where out_trade_no = '{$out_trade_no}'");
    $child_order = $db->once_fetch_array("select * from {$db_prefix}order_list where order_id = {$order['id']}");
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$child_order['goods_id']}");
    doAction('view_order_detail', $db, $db_prefix, $goods, $order, $child_order);
    die;
}
