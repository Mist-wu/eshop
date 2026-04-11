<?php
/**
 * EMSHOP 同系统对接 - 后台 API
 */

require_once '../../../init.php';

// 权限检查
if (ROLE !== 'admin' && ROLE !== 'editor') {
    outputJson(1, '无权限访问');
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'emTestConnection':
        testConnection();
        break;

    case 'emSaveSite':
        saveSite();
        break;

    case 'emDeleteSite':
        deleteSite();
        break;

    case 'emRefreshBalance':
        refreshBalance();
        break;

    case 'emImportGoods':
        importGoods();
        break;

    case 'emSyncStock':
        syncStock();
        break;

    case 'emQueryRemoteOrder':
        queryRemoteOrder();
        break;

    default:
        outputJson(1, '未知操作');
}

/**
 * 输出 JSON
 */
function outputJson($code, $msg, $data = [], $count = 0)
{
    header('Content-Type: application/json');
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
        'count' => $count
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 测试连接
 */
function testConnection()
{
    $domain = Input::postStrVar('domain');
    $appId = Input::postStrVar('app_id');
    $appKey = Input::postStrVar('app_key');

    if (empty($domain) || empty($appId) || empty($appKey)) {
        outputJson(1, '请填写完整信息');
    }

    // 确保域名格式正确
    if (strpos($domain, 'http') !== 0) {
        $domain = 'https://' . $domain;
    }

    $api = new EmApi($domain, $appId, $appKey);
    $result = $api->connect();

    if ($result) {
        outputJson(0, '连接成功', $result);
    } else {
        outputJson(1, '连接失败：' . $api->getLastError());
    }
}

/**
 * 保存站点
 */
function saveSite()
{
    $data = [
        'id' => Input::postIntVar('id'),
        'domain' => Input::postStrVar('domain'),
        'app_id' => Input::postStrVar('app_id'),
        'app_key' => Input::postStrVar('app_key')
    ];

    $result = emSaveSite($data);

    if ($result['success']) {
        outputJson(0, $result['message'], ['id' => $result['id']]);
    } else {
        outputJson(1, $result['message']);
    }
}

/**
 * 删除站点
 */
function deleteSite()
{
    $siteId = Input::postIntVar('site_id');

    if (emDeleteSite($siteId)) {
        outputJson(0, '删除成功');
    } else {
        outputJson(1, '删除失败，该站点可能有关联商品');
    }
}

/**
 * 刷新余额
 */
function refreshBalance()
{
    emGetSiteList(true);
    outputJson(0, '刷新成功');
}

/**
 * 导入商品
 */
function importGoods()
{
    $params = [
        'site_id' => Input::postIntVar('site_id'),
        'goods_ids' => $_POST['goods_ids'] ?? [],
        'sort_id' => Input::postIntVar('sort_id'),
        'raise_type' => Input::postStrVar('raise_type', 'percent'),
        'raise_value' => floatval($_POST['raise_value'] ?? 10)
    ];

    $result = emImportGoods($params);
    outputJson(0, '导入完成', $result);
}

/**
 * 同步库存
 */
function syncStock()
{
    $goodsId = Input::postIntVar('goods_id');

    $db = Database::getInstance();
    $goods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "goods WHERE id = " . (int)$goodsId);

    if (!$goods) {
        outputJson(1, '商品不存在');
    }

    if (emSyncSku($goods)) {
        outputJson(0, '同步成功');
    } else {
        outputJson(1, '同步失败');
    }
}

/**
 * 查询远程订单
 */
function queryRemoteOrder()
{
    $orderListId = Input::postIntVar('order_list_id');

    $db = Database::getInstance();

    // 获取子订单
    $childOrder = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "order_list WHERE id = " . (int)$orderListId);
    if (!$childOrder || empty($childOrder['remote_trade_no'])) {
        outputJson(1, '订单不存在或无远程订单号');
    }

    // 获取商品信息
    $goods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "goods WHERE id = " . (int)$childOrder['goods_id']);
    if (!$goods) {
        outputJson(1, '商品不存在');
    }

    // 获取对接信息
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
    if (!$emGoods) {
        outputJson(1, '对接信息不存在');
    }

    $site = emGetSite($emGoods['site_id']);
    if (!$site) {
        outputJson(1, '对接站点不存在');
    }

    // 查询远程订单（如果远程系统支持）
    $api = EmApi::fromSite($site);
    $result = $api->query($childOrder['remote_trade_no']);

    if ($result) {
        // 如果远程订单已发货，更新本地状态
        if (isset($result['status']) && $result['status'] == 2 && !empty($result['secret'])) {
            $secrets = EmApi::parseSecrets($result['secret']);
            $timestamp = time();

            foreach ($secrets as $secret) {
                $db->query("INSERT INTO " . DB_PREFIX . "stock_usage
                    (stock_id, order_id, order_list_id, content, create_time)
                    VALUES (0, {$childOrder['order_id']}, {$childOrder['id']}, '{$db->escape_string($secret)}', {$timestamp})");
            }

            $db->query("UPDATE " . DB_PREFIX . "order_list SET status = 2 WHERE id = {$childOrder['id']}");
            $db->query("UPDATE " . DB_PREFIX . "order SET status = 2 WHERE id = {$childOrder['order_id']}");
        }

        outputJson(0, '查询成功', $result);
    } else {
        outputJson(1, '查询失败：' . $api->getLastError());
    }
}
