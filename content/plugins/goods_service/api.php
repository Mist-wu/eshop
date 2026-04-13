<?php
/**
 * 人工发货商品 API
 */

require_once '../../../init.php';

// 检查管理员权限
if (ROLE !== 'admin' && ROLE !== 'editor') {
    outputJson(1, '无权限访问');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'deliver':
        manualDeliver();
        break;
    case 'update_stock':
        updateStock();
        break;
    default:
        outputJson(1, '未知操作');
}

/**
 * 手动发货
 */
function manualDeliver() {
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $order_id = Input::postIntVar('order_id');
    $order_list_id = Input::postIntVar('order_list_id');
    $content = trim(Input::postStrVar('content'));

    if ($order_id <= 0 || $order_list_id <= 0) {
        outputJson(1, '订单信息无效');
    }
    if (empty($content)) {
        outputJson(1, '发货内容不能为空');
    }

    $order = $db->once_fetch_array("SELECT * FROM {$db_prefix}order WHERE id = {$order_id}");
    $child_order = $db->once_fetch_array("SELECT * FROM {$db_prefix}order_list WHERE id = {$order_list_id}");
    if (empty($order) || empty($child_order) || (int)$child_order['order_id'] !== $order_id) {
        outputJson(1, '订单不存在');
    }
    if (!empty($order['delete_time']) || (int)($order['status'] ?? 0) === -2) {
        outputJson(1, '已取消订单不能发货');
    }
    if ((int)($order['pay_status'] ?? 0) !== 1 && empty($order['pay_time'])) {
        outputJson(1, '订单未支付，不能发货');
    }

    $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $content)));
    if (empty($lines)) {
        outputJson(1, '发货内容不能为空');
    }

    $existingRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM {$db_prefix}stock_usage WHERE order_list_id = {$order_list_id} AND stock_id = 0");
    $existingCount = (int)($existingRow['total'] ?? 0);
    $quantity = max(1, (int)($child_order['quantity'] ?? 1));
    if ((int)($child_order['status'] ?? 0) === 2 && $existingCount >= $quantity) {
        outputJson(1, '该订单已发货完成');
    }

    $timestamp = time();
    foreach ($lines as $line) {
        $safe = $db->escape_string($line);
        $db->query("INSERT INTO {$db_prefix}stock_usage (stock_id, order_id, order_list_id, content, create_time)
                    VALUES (0, {$order_id}, {$order_list_id}, '{$safe}', {$timestamp})");
    }

    $totalDelivered = $existingCount + count($lines);
    $status = ($quantity > 1 && $totalDelivered < $quantity) ? 1 : 2;

    $db->query("UPDATE {$db_prefix}order SET status = {$status} WHERE id = {$order_id}");
    $db->query("UPDATE {$db_prefix}order_list SET status = {$status} WHERE id = {$order_list_id}");


    doAction('after_manual_deliver', [
        'order_id' => $order_id,
        'order_list_id' => $order_list_id,
        'content' => $lines
    ]);

    outputJson(0, 'success', ['count' => count($lines), 'status' => $status]);
}

/**
 * 更新库存（人工发货）
 */
function updateStock() {
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $goods_id = Input::postIntVar('goods_id');
    $stocks = $_POST['stock'] ?? [];

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }
    if (!is_array($stocks)) {
        outputJson(1, '库存数据无效');
    }

    $goods = $db->once_fetch_array("SELECT id, type FROM {$db_prefix}goods WHERE id = {$goods_id}");
    if (empty($goods) || $goods['type'] !== 'service') {
        outputJson(1, '商品类型不匹配');
    }

    $skuRows = $db->fetch_all("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$goods_id}");
    if (empty($skuRows)) {
        outputJson(1, '未找到规格');
    }

    $validIds = [];
    foreach ($skuRows as $row) {
        $validIds[] = (int)$row['id'];
    }

    $updated = 0;
    foreach ($stocks as $sku_id => $stock) {
        $sku_id = (int)$sku_id;
        if (!in_array($sku_id, $validIds, true)) {
            continue;
        }
        $stockVal = (int)$stock;
        if ($stockVal < 0) {
            $stockVal = 0;
        }
        $db->query("UPDATE {$db_prefix}product_sku SET stock = {$stockVal} WHERE id = {$sku_id}");
        $updated++;
    }

    outputJson(0, 'success', ['updated' => $updated]);
}

/**
 * 输出 JSON
 */
function outputJson($code, $msg, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
