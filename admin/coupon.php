<?php
/**
 * Coupon management
 */

require_once 'globals.php';

$db = Database::getInstance();
$db_prefix = DB_PREFIX;
$action = Input::getStrVar('action');
$couponModel = new Coupon_Model();

$allowed_types = ['general', 'goods', 'category'];
$allowed_threshold_types = ['none', 'min'];
$allowed_discount_types = ['amount', 'percent'];



if ($action === 'get') {
    $id = Input::getIntVar('id', 0);
    $row = $db->once_fetch_array("SELECT * FROM `{$db_prefix}coupon` WHERE id={$id} LIMIT 1");
    if (empty($row)) {
        Output::error('未找到优惠券');
    }
    Output::ok($row);
}

if ($action === 'goods') {
    $category_id = Input::getIntVar('category_id', 0);
    $keyword = Input::getStrVar('keyword');

    $where = "delete_time IS NULL";
    if ($category_id > 0) {
        $where .= " AND sort_id = {$category_id}";
    }
    if ($keyword !== '') {
        $keyword = $db->escape_string($keyword);
        $where .= " AND title LIKE '%{$keyword}%'";
    }

    $list = $db->fetch_all("SELECT id, title, sort_id FROM `{$db_prefix}goods` WHERE {$where} ORDER BY id DESC");
    Output::ok(['list' => $list]);
}

if ($action === 'form') {
    $id = Input::getIntVar('id', 0);
    $coupon = [];
    if ($id > 0) {
        $coupon = $db->once_fetch_array("SELECT * FROM `{$db_prefix}coupon` WHERE id={$id} LIMIT 1");
        if (empty($coupon)) {
            emMsg('未找到优惠券');
        }
    }
    $sorts = $CACHE->readCache('sort');
    include View::getAdmView('open_head');
    require_once View::getAdmView('coupon_form');
    include View::getAdmView('open_foot');
    View::output();
}

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    LoginAuth::checkToken();
    $id = Input::postIntVar('id', 0);
    $status = Input::postIntVar('status', 1);

    if ($id <= 0) {
        Output::error('优惠券ID不正确');
    }
    if (!in_array($status, [0, 1], true)) {
        Output::error('状态不合法');
    }

    $row = $db->once_fetch_array("SELECT id FROM `{$db_prefix}coupon` WHERE id={$id} LIMIT 1");
    if (empty($row)) {
        Output::error('未找到优惠券');
    }

    $db->update('coupon', [
        'status' => $status,
        'update_time' => time(),
    ], ['id' => $id]);
    Output::ok(['msg' => $status === 1 ? '优惠券已启用' : '优惠券已禁用']);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    LoginAuth::checkToken();
    $ids = Input::postStrVar('ids');
    $id_list = array_values(array_filter(array_map('intval', explode(',', $ids))));

    if (empty($id_list)) {
        Output::error('请选择要删除的优惠券');
    }

    $id_str = implode(',', $id_list);
    $db->query("DELETE FROM `{$db_prefix}coupon` WHERE id IN ({$id_str})");
    Output::ok(['msg' => '优惠券已删除']);
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    LoginAuth::checkToken();

    $id = Input::postIntVar('id', 0);
    $category_id = Input::postIntVar('category_id', 0);
    $goods_id = Input::postIntVar('goods_id', 0);
    $remark = trim(Input::postStrVar('remark', ''));
    $threshold_type = Input::postStrVar('threshold_type', 'none');
    $min_amount = (float)Input::postStrVar('min_amount', '0');
    $discount_type = Input::postStrVar('discount_type', 'amount');
    $discount_value = (float)Input::postStrVar('discount_value', '0');
    $expire_time_input = Input::postStrVar('expire_time');
    $end_time = 0;
    if ($expire_time_input !== '') {
        $end_time = strtotime($expire_time_input);
        if (!$end_time) {
            Output::error('过期时间格式不正确');
        }
    }
    $use_limit = (int)Input::postIntVar('use_limit', 1, 0);
    $prefix = normalizeCouponPrefix(Input::postStrVar('prefix', ''));
    $quantity = (int)Input::postIntVar('quantity', 1);
    $status_input = isset($_POST['status']) ? (int)Input::postIntVar('status', 1) : null;

    if ($status_input !== null && !in_array($status_input, [0, 1], true)) {
        $status_input = 1;
    }
    if (!in_array($threshold_type, $allowed_threshold_types, true)) {
        Output::error('门槛类型不合法');
    }
    if (!in_array($discount_type, $allowed_discount_types, true)) {
        Output::error('优惠方式不合法');
    }
    if ($use_limit < 0) {
        Output::error('可用次数不能小于0');
    }
    if ($threshold_type === 'none') {
        $min_amount = 0;
    } else {
        if ($min_amount <= 0) {
            Output::error('请输入最低消费金额');
        }
    }
    if ($discount_value <= 0) {
        Output::error('请输入优惠数值');
    }
    if ($discount_type === 'percent' && ($discount_value <= 0 || $discount_value > 100)) {
        Output::error('百分比应在 0-100 之间');
    }
    if ($discount_type === 'amount' && $threshold_type === 'min' && $min_amount > 0 && $discount_value > $min_amount) {
        Output::error('抵扣金额不能大于最低消费金额');
    }

    $category_id = (int)$category_id;
    $goods_id = (int)$goods_id;
    if ($category_id < 0) {
        $category_id = 0;
    }
    if ($goods_id < 0) {
        $goods_id = 0;
    }

    $type = 'general';
    if ($goods_id > 0) {
        $goods = $db->once_fetch_array("SELECT id, sort_id FROM `{$db_prefix}goods` WHERE id={$goods_id} AND delete_time IS NULL LIMIT 1");
        if (empty($goods)) {
            Output::error('所选商品不存在或已删除');
        }
        if ($category_id > 0 && (int)$goods['sort_id'] !== $category_id) {
            Output::error('所选商品不在该分类下');
        }
        $category_id = (int)$goods['sort_id'];
        $type = 'goods';
    } elseif ($category_id > 0) {
        $sorts = $CACHE->readCache('sort');
        if (empty($sorts[$category_id])) {
            Output::error('所选分类不存在');
        }
        $type = 'category';
    }
    if (!in_array($type, $allowed_types, true)) {
        $type = 'general';
    }

    $now = time();
    if ($id > 0) {
        $update_data = [
            'type' => $type,
            'category_id' => $category_id,
            'goods_id' => $goods_id,
            'remark' => $remark,
            'threshold_type' => $threshold_type,
            'min_amount' => $min_amount,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'end_time' => $end_time,
            'use_limit' => $use_limit,
            'prefix' => $prefix,
            'update_time' => $now,
        ];
        if ($status_input !== null) {
            $update_data['status'] = $status_input;
        }
        $db->update('coupon', $update_data, ['id' => $id]);
        Output::ok(['msg' => '优惠券已更新']);
    }

    if ($quantity < 1) {
        Output::error('生成数量需大于0');
    }
    if ($quantity > 5000) {
        Output::error('单次生成数量过大，请分批生成');
    }

    $codes = [];
    $max_try = $quantity * 5;
    $try = 0;

    while (count($codes) < $quantity && $try < $max_try) {
        $try++;
        $code = generateCouponCode($prefix);
        if (isset($codes[$code])) {
            continue;
        }
        $exists = $db->once_fetch_array("SELECT id FROM `{$db_prefix}coupon` WHERE code='" . $db->escape_string($code) . "' LIMIT 1");
        if (!empty($exists)) {
            continue;
        }
        $codes[$code] = true;
    }

    if (count($codes) < $quantity) {
        Output::error('生成失败，请重试');
    }

    $status = $status_input === null ? 1 : $status_input;
    foreach (array_keys($codes) as $code) {
        $db->add('coupon', [
            'type' => $type,
            'category_id' => $category_id,
            'goods_id' => $goods_id,
            'remark' => $remark,
            'threshold_type' => $threshold_type,
            'min_amount' => $min_amount,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'end_time' => $end_time,
            'use_limit' => $use_limit,
            'prefix' => $prefix,
            'code' => $code,
            'used_times' => 0,
            'status' => $status,
            'create_time' => $now,
            'update_time' => $now,
        ]);
    }

    Output::ok(['msg' => '优惠券已生成']);
}

if ($action === 'index') {
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 30);
    $owner_uid = Input::getIntVar('owner_uid', 0);
    $keyword = trim(Input::getStrVar('keyword', ''));

    $page = max(1, (int)$page);
    $limit = (int)$limit > 0 ? (int)$limit : 30;
    $offset = ($page - 1) * $limit;

    $where = "1=1";
    if ($owner_uid > 0) {
        $where .= " AND owner_uid = {$owner_uid}";
    }
    if ($keyword !== '') {
        $keyword = $db->escape_string($keyword);
        $where .= " AND (code LIKE '%{$keyword}%' OR remark LIKE '%{$keyword}%')";
    }

    $countRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM `{$db_prefix}coupon` WHERE {$where}");
    $total = (int)($countRow['total'] ?? 0);
    $list = $db->fetch_all("SELECT * FROM `{$db_prefix}coupon` WHERE {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}");

    $sorts = $CACHE->readCache('sort');
    $goods_ids = [];
    $owner_uids = [];
    foreach ($list as $row) {
        $gid = (int)($row['goods_id'] ?? 0);
        if ($gid > 0) {
            $goods_ids[$gid] = true;
        }
        $ownerUid = (int)($row['owner_uid'] ?? 0);
        if ($ownerUid > 0) {
            $owner_uids[$ownerUid] = true;
        }
    }

    $goods_map = [];
    if (!empty($goods_ids)) {
        $goods_id_str = implode(',', array_keys($goods_ids));
        $goods_rows = $db->fetch_all("SELECT id, title, sort_id FROM `{$db_prefix}goods` WHERE id IN ({$goods_id_str})");
        foreach ($goods_rows as $goods) {
            $goods_map[(int)$goods['id']] = $goods;
        }
    }

    $owner_map = [];
    if (!empty($owner_uids)) {
        $owner_id_str = implode(',', array_keys($owner_uids));
        $owner_rows = $db->fetch_all("SELECT uid, nickname FROM `{$db_prefix}user` WHERE uid IN ({$owner_id_str})");
        foreach ($owner_rows as $owner) {
            $owner_map[(int)$owner['uid']] = $owner;
        }
    }

    $state_class_map = [
        'active' => 'layui-bg-green',
        'disabled' => 'layui-bg-gray',
        'expired' => 'layui-bg-orange',
        'used_up' => 'layui-bg-red',
    ];

    $now = time();
    foreach ($list as &$row) {
        $gid = (int)($row['goods_id'] ?? 0);
        $cid = (int)($row['category_id'] ?? 0);

        $goods_title = isset($goods_map[$gid]) ? $goods_map[$gid]['title'] : '';
        $category_name = isset($sorts[$cid]) ? $sorts[$cid]['sortname'] : '';

        if ($gid > 0) {
            $scope_text = '商品：' . ($goods_title !== '' ? $goods_title : '商品已删除');
        } elseif ($cid > 0) {
            $scope_text = '分类：' . ($category_name !== '' ? $category_name : '分类已删除');
        } else {
            $scope_text = '全场';
        }

        $threshold_text = ($row['threshold_type'] ?? '') === 'min' ? ('满' . $row['min_amount']) : '无门槛';
        $discount_text = ($row['discount_type'] ?? '') === 'percent' ? ('抵扣 ' . $row['discount_value'] . ' %') : ('抵扣 ' . $row['discount_value'] . ' 元');
        $expire_text = !empty($row['end_time']) ? date('Y-m-d H:i', (int)$row['end_time']) : '永久有效';
        $use_limit_text = ((int)($row['use_limit'] ?? 0) > 0) ? (string)$row['use_limit'] : '不限';
        $prefix_text = trim((string)($row['prefix'] ?? '')) !== '' ? $row['prefix'] : '-';

        $remark_raw = trim((string)($row['remark'] ?? ''));
        $remark_text = $remark_raw !== '' ? $remark_raw : '-';

        $state = resolveCouponState($row, $now);
        $row['state'] = $state['key'];
        $row['state_text'] = $state['text'];
        $row['state_class'] = $state_class_map[$row['state']] ?? 'layui-bg-gray';

        $row['goods_title'] = $goods_title;
        $row['category_name'] = $category_name;
        $row['scope_text'] = htmlspecialchars($scope_text, ENT_QUOTES);
        $row['threshold_text'] = htmlspecialchars($threshold_text, ENT_QUOTES);
        $row['discount_text'] = htmlspecialchars($discount_text, ENT_QUOTES);
        $row['expire_text'] = htmlspecialchars($expire_text, ENT_QUOTES);
        $row['use_limit_text'] = htmlspecialchars($use_limit_text, ENT_QUOTES);
        $row['prefix_text'] = htmlspecialchars($prefix_text, ENT_QUOTES);
        $row['remark_text'] = htmlspecialchars($remark_text, ENT_QUOTES);
        $row['remark_title'] = htmlspecialchars($remark_raw, ENT_QUOTES);
        $ownerUid = (int)($row['owner_uid'] ?? 0);
        if ($ownerUid > 0) {
            $ownerNickname = isset($owner_map[$ownerUid]['nickname']) ? trim((string)$owner_map[$ownerUid]['nickname']) : '';
            $ownerLabel = $ownerNickname !== '' ? ('UID:' . $ownerUid . '（' . $ownerNickname . '）') : ('UID:' . $ownerUid);
            $row['owner_uid_text'] = htmlspecialchars($ownerLabel, ENT_QUOTES);
        } else {
            $row['owner_uid_text'] = '后台创建';
        }
    }
    unset($row);

    Output::data($list, $total);
}

// List coupons
$page = Input::getIntVar('page', 1);
$limit = 30;
$offset = ($page - 1) * $limit;
$countRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM `{$db_prefix}coupon`");
$total = (int)($countRow['total'] ?? 0);
$coupon_list = $db->fetch_all("SELECT * FROM `{$db_prefix}coupon` ORDER BY id DESC LIMIT {$offset}, {$limit}");

$sorts = $CACHE->readCache('sort');
$goods_ids = [];
foreach ($coupon_list as $row) {
    $gid = (int)($row['goods_id'] ?? 0);
    if ($gid > 0) {
        $goods_ids[$gid] = true;
    }
}
$goods_map = [];
if (!empty($goods_ids)) {
    $goods_id_str = implode(',', array_keys($goods_ids));
    $goods_rows = $db->fetch_all("SELECT id, title, sort_id FROM `{$db_prefix}goods` WHERE id IN ({$goods_id_str})");
    foreach ($goods_rows as $goods) {
        $goods_map[(int)$goods['id']] = $goods;
    }
}

$now = time();
foreach ($coupon_list as &$row) {
    $gid = (int)($row['goods_id'] ?? 0);
    $cid = (int)($row['category_id'] ?? 0);
    $row['goods_title'] = isset($goods_map[$gid]) ? $goods_map[$gid]['title'] : '';
    $row['category_name'] = isset($sorts[$cid]) ? $sorts[$cid]['sortname'] : '';
    $state = resolveCouponState($row, $now);
    $row['state'] = $state['key'];
    $row['state_text'] = $state['text'];
}
unset($row);

$page_url = 'coupon.php?page=';
$br = '<a href="./">控制台</a><a href="coupon.php">优惠券</a><a><cite>优惠券管理</cite></a>';
include View::getAdmView('header');
require_once View::getAdmView('coupon');
include View::getAdmView('footer');
View::output();

function normalizeCouponPrefix($prefix, $max_len = 8) {
    $prefix = strtoupper(trim($prefix));
    if ($prefix === '') {
        return '';
    }
    $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
    if ($prefix === '') {
        return '';
    }
    if ($max_len > 0 && strlen($prefix) > $max_len) {
        $prefix = substr($prefix, 0, $max_len);
    }
    return $prefix;
}

function resolveCouponState($coupon, $now) {
    $status = (int)($coupon['status'] ?? 0);
    $end_time = (int)($coupon['end_time'] ?? 0);
    $use_limit = (int)($coupon['use_limit'] ?? 0);
    $used_times = (int)($coupon['used_times'] ?? 0);

    if ($status !== 1) {
        return ['key' => 'disabled', 'text' => '已禁用'];
    }
    if ($end_time > 0 && $end_time < $now) {
        return ['key' => 'expired', 'text' => '已过期'];
    }
    if ($use_limit > 0 && $used_times >= $use_limit) {
        return ['key' => 'used_up', 'text' => '已用尽'];
    }
    return ['key' => 'active', 'text' => '可用'];
}

function generateCouponCode($prefix = '') {
    $random = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
    $prefix = normalizeCouponPrefix($prefix);
    if ($prefix !== '') {
        return $prefix . $random;
    }
    return $random;
}
