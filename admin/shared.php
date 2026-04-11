<?php
/**
 * Shared API Management - Provider
 * Manage shared apps and goods for inter-system integration
 *
 * @package EMSHOP
 */

require_once 'globals.php';

$db = Database::getInstance();
$db_prefix = DB_PREFIX;

// Check if tables exist, create if not
$tableCheck = $db->query("SHOW TABLES LIKE '{$db_prefix}shared_app'");
if ($db->num_rows($tableCheck) == 0) {
    // Create tables
    $db->query("
        CREATE TABLE `{$db_prefix}shared_app` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `app_name` varchar(100) NOT NULL COMMENT '应用名称',
            `app_id` varchar(32) NOT NULL COMMENT '应用ID',
            `app_key` varchar(64) NOT NULL COMMENT '应用密钥',
            `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1启用，0禁用',
            `balance` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '余额',
            `remark` varchar(255) DEFAULT NULL COMMENT '备注',
            `create_time` bigint(16) NOT NULL,
            `update_time` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_app_id` (`app_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='对接应用授权表'
    ");

    $db->query("
        CREATE TABLE `{$db_prefix}shared_goods` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `goods_id` int(10) NOT NULL COMMENT '商品ID',
            `shared_code` varchar(50) NOT NULL COMMENT '共享代码',
            `factory_price` bigint(16) NOT NULL DEFAULT 0 COMMENT '出厂价/成本价（分）',
            `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1上架，0下架',
            `create_time` bigint(16) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_shared_code` (`shared_code`),
            KEY `idx_goods_id` (`goods_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='共享商品表'
    ");

    $db->query("
        CREATE TABLE `{$db_prefix}shared_transaction` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `app_id` varchar(32) NOT NULL COMMENT '应用ID',
            `trade_no` varchar(50) NOT NULL COMMENT '本站订单号',
            `request_no` varchar(50) NOT NULL COMMENT '对方请求订单号',
            `shared_code` varchar(50) NOT NULL COMMENT '共享代码',
            `goods_id` int(10) NOT NULL COMMENT '商品ID',
            `sku_option` varchar(100) DEFAULT NULL COMMENT '规格',
            `quantity` int(10) NOT NULL DEFAULT 1 COMMENT '数量',
            `amount` bigint(16) NOT NULL COMMENT '交易金额（分）',
            `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0待发货，1已发货，2已发货',
            `secret` text COMMENT '卡密内容',
            `widget` text COMMENT '表单数据JSON',
            `create_time` bigint(16) NOT NULL,
            `deliver_time` bigint(16) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_trade_no` (`trade_no`),
            UNIQUE KEY `idx_request_no` (`app_id`, `request_no`),
            KEY `idx_app_id` (`app_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='对接交易记录表'
    ");
}

// Default: show apps list
if (empty($action)) {
    $br = '<a href="./">控制台</a><a href="./shared.php">对接管理</a><a><cite>应用授权</cite></a>';
    include View::getAdmView('header');
    include __DIR__ . '/views/shared/app_index.php';
    include View::getAdmView('footer');
    View::output();
}

// ==================== App Management ====================

if ($action == 'app_list') {
    $sql = "SELECT * FROM {$db_prefix}shared_app ORDER BY id DESC";
    $list = $db->fetch_all($sql);
    output::data($list ?: [], count($list ?: []));
}

if ($action == 'app_add') {
    include View::getAdmView('open_head');
    include __DIR__ . '/views/shared/app_form.php';
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'app_edit') {
    $id = Input::getIntVar('id');
    $info = $db->once_fetch_array("SELECT * FROM {$db_prefix}shared_app WHERE id = {$id}");
    if (!$info) {
        exit('应用不存在');
    }
    include View::getAdmView('open_head');
    include __DIR__ . '/views/shared/app_form.php';
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'app_save') {
    LoginAuth::checkToken();

    $id = Input::postIntVar('id');
    $appName = Input::postStrVar('app_name');
    $appId = Input::postStrVar('app_id');
    $appKey = Input::postStrVar('app_key');
    $status = Input::postIntVar('status', 1);
    $balance = floatval(Input::postStrVar('balance', '0'));
    $remark = Input::postStrVar('remark');

    if (empty($appName) || empty($appId) || empty($appKey)) {
        output::error('请填写完整信息');
    }

    // Check duplicate app_id
    $existing = $db->once_fetch_array("SELECT id FROM {$db_prefix}shared_app WHERE app_id = '{$db->escape_string($appId)}'" . ($id > 0 ? " AND id != {$id}" : ""));
    if ($existing) {
        output::error('应用ID已存在');
    }

    $timestamp = time();

    if ($id > 0) {
        $db->query("UPDATE {$db_prefix}shared_app SET
            app_name = '{$db->escape_string($appName)}',
            app_id = '{$db->escape_string($appId)}',
            app_key = '{$db->escape_string($appKey)}',
            status = {$status},
            balance = {$balance},
            remark = '{$db->escape_string($remark)}',
            update_time = {$timestamp}
            WHERE id = {$id}");
    } else {
        $db->query("INSERT INTO {$db_prefix}shared_app
            (app_name, app_id, app_key, status, balance, remark, create_time)
            VALUES (
                '{$db->escape_string($appName)}',
                '{$db->escape_string($appId)}',
                '{$db->escape_string($appKey)}',
                {$status},
                {$balance},
                '{$db->escape_string($remark)}',
                {$timestamp}
            )");
    }

    output::ok();
}

if ($action == 'app_del') {
    LoginAuth::checkToken();
    $ids = Input::postStrVar('ids');

    if (empty($ids)) {
        output::error('请选择要删除的应用');
    }

    $idArr = array_map('intval', explode(',', $ids));
    $db->query("DELETE FROM {$db_prefix}shared_app WHERE id IN (" . implode(',', $idArr) . ")");

    output::ok();
}

if ($action == 'app_recharge') {
    LoginAuth::checkToken();

    $id = Input::postIntVar('id');
    $amount = floatval(Input::postStrVar('amount', '0'));
    $type = Input::postStrVar('type', 'add'); // add or set

    if ($id <= 0) {
        output::error('应用不存在');
    }

    if ($type === 'set') {
        $db->query("UPDATE {$db_prefix}shared_app SET balance = {$amount}, update_time = " . time() . " WHERE id = {$id}");
    } else {
        $db->query("UPDATE {$db_prefix}shared_app SET balance = balance + {$amount}, update_time = " . time() . " WHERE id = {$id}");
    }

    output::ok();
}

if ($action == 'gen_key') {
    $appId = 'APP' . strtoupper(substr(md5(uniqid() . mt_rand()), 0, 12));
    $appKey = strtoupper(md5(uniqid() . mt_rand() . time()));

    output::ok(['app_id' => $appId, 'app_key' => $appKey]);
}

// ==================== Goods Management ====================

if ($action == 'goods') {
    $br = '<a href="./">控制台</a><a href="./shared.php">对接管理</a><a><cite>共享商品</cite></a>';
    include View::getAdmView('header');
    include __DIR__ . '/views/shared/goods_index.php';
    include View::getAdmView('footer');
    View::output();
}

if ($action == 'goods_list') {
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 20);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT sg.*, g.title, g.type, g.cover
            FROM {$db_prefix}shared_goods sg
            LEFT JOIN {$db_prefix}goods g ON sg.goods_id = g.id
            ORDER BY sg.id DESC
            LIMIT {$offset}, {$limit}";

    $list = $db->fetch_all($sql);

    // Format prices
    foreach ($list as &$row) {
        $row['factory_price_yuan'] = number_format($row['factory_price'] / 100, 2);
    }

    $countRes = $db->once_fetch_array("SELECT COUNT(*) as cnt FROM {$db_prefix}shared_goods");
    $count = (int)$countRes['cnt'];

    output::data($list ?: [], $count);
}

if ($action == 'goods_add') {
    // Get goods list (not yet shared)
    $sharedGoodsIds = [];
    $existingShared = $db->fetch_all("SELECT goods_id FROM {$db_prefix}shared_goods");
    if ($existingShared) {
        $sharedGoodsIds = array_column($existingShared, 'goods_id');
    }

    $goodsWhere = empty($sharedGoodsIds) ? "WHERE 1=1" : "WHERE g.id NOT IN (" . implode(',', $sharedGoodsIds) . ")";
    $goodsList = $db->fetch_all("SELECT g.id, g.title, g.type FROM {$db_prefix}goods g {$goodsWhere} AND g.hide = 'n' ORDER BY g.id DESC LIMIT 100");

    include View::getAdmView('open_head');
    include __DIR__ . '/views/shared/goods_form.php';
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'goods_edit') {
    $id = Input::getIntVar('id');
    $info = $db->once_fetch_array("SELECT sg.*, g.title FROM {$db_prefix}shared_goods sg LEFT JOIN {$db_prefix}goods g ON sg.goods_id = g.id WHERE sg.id = {$id}");
    if (!$info) {
        exit('共享商品不存在');
    }
    $info['factory_price_yuan'] = number_format($info['factory_price'] / 100, 2);

    include View::getAdmView('open_head');
    include __DIR__ . '/views/shared/goods_form.php';
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'goods_save') {
    LoginAuth::checkToken();

    $id = Input::postIntVar('id');
    $goodsId = Input::postIntVar('goods_id');
    $sharedCode = Input::postStrVar('shared_code');
    $factoryPrice = floatval(Input::postStrVar('factory_price', '0'));
    $status = Input::postIntVar('status', 1);

    if ($id <= 0 && $goodsId <= 0) {
        output::error('请选择商品');
    }

    if (empty($sharedCode)) {
        // Auto generate
        $sharedCode = 'G' . strtoupper(substr(md5($goodsId . time()), 0, 8));
    }

    // Check duplicate shared_code
    $existing = $db->once_fetch_array("SELECT id FROM {$db_prefix}shared_goods WHERE shared_code = '{$db->escape_string($sharedCode)}'" . ($id > 0 ? " AND id != {$id}" : ""));
    if ($existing) {
        output::error('共享代码已存在');
    }

    $factoryPriceFen = (int)($factoryPrice * 100);

    if ($id > 0) {
        $db->query("UPDATE {$db_prefix}shared_goods SET
            shared_code = '{$db->escape_string($sharedCode)}',
            factory_price = {$factoryPriceFen},
            status = {$status}
            WHERE id = {$id}");
    } else {
        // Check duplicate goods_id
        $existingGoods = $db->once_fetch_array("SELECT id FROM {$db_prefix}shared_goods WHERE goods_id = {$goodsId}");
        if ($existingGoods) {
            output::error('该商品已添加到共享列表');
        }

        $db->query("INSERT INTO {$db_prefix}shared_goods
            (goods_id, shared_code, factory_price, status, create_time)
            VALUES ({$goodsId}, '{$db->escape_string($sharedCode)}', {$factoryPriceFen}, {$status}, " . time() . ")");
    }

    output::ok();
}

if ($action == 'goods_del') {
    LoginAuth::checkToken();
    $ids = Input::postStrVar('ids');

    if (empty($ids)) {
        output::error('请选择要删除的商品');
    }

    $idArr = array_map('intval', explode(',', $ids));
    $db->query("DELETE FROM {$db_prefix}shared_goods WHERE id IN (" . implode(',', $idArr) . ")");

    output::ok();
}

// ==================== Transaction Records ====================

if ($action == 'transaction') {
    $br = '<a href="./">控制台</a><a href="./shared.php">对接管理</a><a><cite>交易记录</cite></a>';
    include View::getAdmView('header');
    include __DIR__ . '/views/shared/transaction_index.php';
    include View::getAdmView('footer');
    View::output();
}

if ($action == 'transaction_list') {
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 20);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT st.*, sa.app_name, g.title as goods_title
            FROM {$db_prefix}shared_transaction st
            LEFT JOIN {$db_prefix}shared_app sa ON st.app_id = sa.app_id
            LEFT JOIN {$db_prefix}goods g ON st.goods_id = g.id
            ORDER BY st.id DESC
            LIMIT {$offset}, {$limit}";

    $list = $db->fetch_all($sql);

    // Format data
    foreach ($list as &$row) {
        $row['amount_yuan'] = number_format($row['amount'] / 100, 2);
        $row['create_time_str'] = date('Y-m-d H:i:s', $row['create_time']);
        $row['status_text'] = $row['status'] == 2 ? '已发货' : ($row['status'] == 1 ? '待发货' : '待处理');
    }

    $countRes = $db->once_fetch_array("SELECT COUNT(*) as cnt FROM {$db_prefix}shared_transaction");
    $count = (int)$countRes['cnt'];

    output::data($list ?: [], $count);
}

if ($action == 'transaction_detail') {
    $id = Input::getIntVar('id');
    $info = $db->once_fetch_array("SELECT st.*, sa.app_name, g.title as goods_title
            FROM {$db_prefix}shared_transaction st
            LEFT JOIN {$db_prefix}shared_app sa ON st.app_id = sa.app_id
            LEFT JOIN {$db_prefix}goods g ON st.goods_id = g.id
            WHERE st.id = {$id}");

    if (!$info) {
        exit('交易记录不存在');
    }

    $info['amount_yuan'] = number_format($info['amount'] / 100, 2);
    $info['create_time_str'] = date('Y-m-d H:i:s', $info['create_time']);
    $info['status_text'] = $info['status'] == 2 ? '已发货' : ($info['status'] == 1 ? '待发货' : '待处理');

    include View::getAdmView('open_head');
    include __DIR__ . '/views/shared/transaction_detail.php';
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'transaction_deliver') {
    LoginAuth::checkToken();

    $id = Input::postIntVar('id');
    $secret = Input::postStrVar('secret');

    if ($id <= 0) {
        output::error('交易记录不存在');
    }

    $db->query("UPDATE {$db_prefix}shared_transaction SET
        status = 2,
        secret = '{$db->escape_string($secret)}',
        deliver_time = " . time() . "
        WHERE id = {$id}");

    output::ok();
}
