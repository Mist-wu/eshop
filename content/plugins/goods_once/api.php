<?php
/**
 * 独立卡密库存管理 API
 *
 * @package EMSHOP
 */

require_once '../../../init.php';

// 检查管理员权限
if (ROLE !== 'admin' && ROLE !== 'editor') {
    outputJson(1, '无权限访问');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$db = Database::getInstance();
$db_prefix = DB_PREFIX;
$stockModel = new Stock_Model();

switch ($action) {
    case 'priority_stock':
        updateStockPriority(true);
        break;
    case 'unpriority_stock':
        updateStockPriority(false);
        break;
    case 'clear_stock':
        clearStock();
        break;
    case 'deliver':
        manualDeliver();
        break;
    case 'stock_list':
        getStockList();
        break;
    case 'sold_list':
        getSoldList();
        break;
    case 'marked_list':
        getMarkedList();
        break;
    case 'add_stock':
        addStock();
        break;
    case 'edit_stock':
        editStock();
        break;
    case 'delete_stock':
        deleteStock();
        break;
    case 'mark_sold':
        markSold();
        break;
    case 'unmark_sold':
        unmarkSold();
        break;
    case 'export_stock':
        exportStock();
        break;
    case 'dedupe_stock':
        dedupeStock();
        break;
    default:
        outputJson(1, '未知操作');
}

/**
 * 设置库存优先级（weight）
 */
function updateStockPriority($setPriority) {
    global $db, $db_prefix;

    $ids = Input::postStrVar('ids');
    $goods_id = Input::postIntVar('goods_id');
    if (empty($ids)) {
        outputJson(1, '请选择数据');
    }

    $idArr = array_filter(array_map('intval', explode(',', $ids)));
    if (empty($idArr)) {
        outputJson(1, '无效的ID');
    }

    $weight = 0;
    if ($setPriority) {
        if (defined('TEMESTAMP')) {
            $weight = (int)TEMESTAMP;
        } elseif (defined('TIMESTAMP')) {
            $weight = (int)TIMESTAMP;
        } else {
            $weight = time();
        }
    }

    $idsStr = implode(',', $idArr);
    $where = "id IN ({$idsStr})";
    if ($goods_id > 0) {
        $where .= " AND goods_id = {$goods_id}";
    }
    $db->query("UPDATE {$db_prefix}stock SET weight = {$weight} WHERE {$where}");

    outputJson(0, 'success', ['updated' => count($idArr), 'weight' => $weight]);
}

/**
 * 清空库存（未售出/禁用）
 */
function clearStock() {
    global $db, $db_prefix, $stockModel;

    $goods_id = Input::postIntVar('goods_id');
    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $where = "goods_id = {$goods_id} AND status IN (0, 2) AND max_uses = 1 AND used_count < max_uses";

    $skuRows = $db->fetch_all("SELECT DISTINCT sku_id FROM {$db_prefix}stock WHERE {$where}");
    $db->query("DELETE FROM {$db_prefix}stock WHERE {$where}");
    $deleted = (int)$db->affected_rows();

    if (!empty($skuRows)) {
        foreach ($skuRows as $row) {
            $sku_id = (int)$row['sku_id'];
            if ($sku_id > 0) {
                $stockModel->syncSkuStock($goods_id, $sku_id, false);
            }
        }
    }

    outputJson(0, 'success', ['deleted' => $deleted]);
}

/**
 * 手动发货（后台）
 */
function manualDeliver() {
    global $db, $db_prefix, $stockModel;

    $order_id = Input::postIntVar('order_id');
    $order_list_id = Input::postIntVar('order_list_id');
    $content = trim(Input::postStrVar('content'));

    if ($order_id <= 0 || $order_list_id <= 0) {
        outputJson(1, '订单信息无效');
    }
    if ($content === '') {
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

    $goods = $db->once_fetch_array("SELECT id, type FROM {$db_prefix}goods WHERE id = {$child_order['goods_id']}");
    if (empty($goods) || $goods['type'] !== 'once') {
        outputJson(1, '商品类型不匹配');
    }

    $option_ids = empty($child_order['sku']) ? '0' : $child_order['sku'];
    $option_ids = $db->escape_string($option_ids);
    $sku_row = $db->once_fetch_array("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$child_order['goods_id']} AND option_ids = '{$option_ids}'");
    $sku_id = $sku_row ? (int)$sku_row['id'] : 0;
    if ($sku_id <= 0) {
        outputJson(1, '商品规格获取失败');
    }

    $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $content)));
    if (empty($lines)) {
        outputJson(1, '发货内容不能为空');
    }

    $existingRow = $db->once_fetch_array("SELECT COUNT(*) AS total FROM {$db_prefix}stock_usage WHERE order_list_id = {$order_list_id}");
    $existingCount = (int)($existingRow['total'] ?? 0);
    $quantity = max(1, (int)($child_order['quantity'] ?? 1));
    $remaining = $quantity - $existingCount;
    if ($remaining <= 0) {
        outputJson(1, '该订单已发货完成');
    }
    if (count($lines) > $remaining) {
        outputJson(1, '发货内容数量超过待发货数量');
    }

    $delivered = 0;
    foreach ($lines as $line) {
        $stock_id = $stockModel->addStock($child_order['goods_id'], $sku_id, $line, 1);
        if ($stock_id > 0 && $stockModel->useStock($stock_id, $order_id, $order_list_id)) {
            $delivered++;
        }
    }

    if ($delivered > 0) {
        $db->query("UPDATE {$db_prefix}product_sku SET sales = sales + {$delivered} WHERE id = {$sku_id}");
        $stockModel->syncSkuStock($child_order['goods_id'], $sku_id, false);
    }

    $totalDelivered = $existingCount + $delivered;
    $status = $totalDelivered >= $quantity ? 2 : 1;

    $db->query("UPDATE {$db_prefix}order SET status = {$status} WHERE id = {$order_id}");
    $db->query("UPDATE {$db_prefix}order_list SET status = {$status} WHERE id = {$order_list_id}");

    outputJson(0, 'success', ['count' => $delivered, 'status' => $status]);
}

/**
 * 获取库存列表（未售出）
 */
function getStockList() {
    global $db, $db_prefix;

    $goods_id = Input::getIntVar('goods_id');
    $sku_id = Input::getIntVar('sku_id', -1);
    $keyword = Input::getStrVar('keyword');
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $where = "s.goods_id = {$goods_id} AND s.status = 0 AND s.max_uses = 1 AND s.used_count < s.max_uses";
    if ($sku_id >= 0) {
        $where .= " AND s.sku_id = {$sku_id}";
    }
    if (!empty($keyword)) {
        $keyword = addslashes($keyword);
        $where .= " AND s.content LIKE '%{$keyword}%'";
    }

    $countSql = "SELECT COUNT(*) as total FROM {$db_prefix}stock s WHERE {$where}";
    $total = $db->once_fetch_array($countSql)['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT s.* FROM {$db_prefix}stock s
            WHERE {$where}
            ORDER BY s.id DESC
            LIMIT {$offset}, {$limit}";
    $rows = $db->fetch_all($sql);

    $skuNames = getSkuNameMap($goods_id);
    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'id' => $row['id'],
            'goods_id' => $row['goods_id'],
            'sku_id' => $row['sku_id'],
            'sku_name' => $skuNames[$row['sku_id']] ?? '默认',
            'content' => $row['content'],
            'batch_no' => $row['batch_no'],
            'max_uses' => $row['max_uses'],
            'used_count' => $row['used_count'],
            'status' => $row['status'],
            'weight' => (int)($row['weight'] ?? 0),
            'create_time' => $row['create_time'],
            'use_time' => $row['use_time'],
            'create_time_fmt' => date('Y-m-d H:i:s', $row['create_time'])
        ];
    }

    outputJson(0, 'success', $list, $total);
}

/**
 * 获取已售出列表
 */
function getSoldList() {
    global $db, $db_prefix;

    $goods_id = Input::getIntVar('goods_id');
    $keyword = Input::getStrVar('keyword');
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $where = "s.goods_id = {$goods_id}";
    if (!empty($keyword)) {
        $keyword = addslashes($keyword);
        $where .= " AND s.content LIKE '%{$keyword}%'";
    }

    $countSql = "SELECT COUNT(*) as total
                 FROM {$db_prefix}stock_usage u
                 INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
                 WHERE {$where}";
    $total = $db->once_fetch_array($countSql)['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT u.id as usage_id, u.order_id, u.order_list_id, u.create_time as sale_time,
                   s.id, s.sku_id, s.content
            FROM {$db_prefix}stock_usage u
            INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
            WHERE {$where}
            ORDER BY u.create_time DESC
            LIMIT {$offset}, {$limit}";
    $rows = $db->fetch_all($sql);

    $skuNames = getSkuNameMap($goods_id);
    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'id' => $row['id'],
            'usage_id' => $row['usage_id'],
            'sku_id' => $row['sku_id'],
            'sku_name' => $skuNames[$row['sku_id']] ?? '默认',
            'content' => $row['content'],
            'order_id' => $row['order_id'],
            'sale_time' => $row['sale_time'],
            'sale_time_fmt' => date('Y-m-d H:i:s', $row['sale_time'])
        ];
    }

    outputJson(0, 'success', $list, $total);
}

/**
 * 获取标记售出列表
 */
function getMarkedList() {
    global $db, $db_prefix;

    $goods_id = Input::getIntVar('goods_id');
    $keyword = Input::getStrVar('keyword');
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $where = "s.goods_id = {$goods_id} AND s.status = -1";
    if (!empty($keyword)) {
        $keyword = addslashes($keyword);
        $where .= " AND s.content LIKE '%{$keyword}%'";
    }

    $countSql = "SELECT COUNT(*) as total FROM {$db_prefix}stock s WHERE {$where}";
    $total = $db->once_fetch_array($countSql)['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT s.* FROM {$db_prefix}stock s
            WHERE {$where}
            ORDER BY s.id DESC
            LIMIT {$offset}, {$limit}";
    $rows = $db->fetch_all($sql);

    $skuNames = getSkuNameMap($goods_id);
    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'id' => $row['id'],
            'goods_id' => $row['goods_id'],
            'sku_id' => $row['sku_id'],
            'sku_name' => $skuNames[$row['sku_id']] ?? '默认',
            'content' => $row['content'],
            'batch_no' => $row['batch_no'],
            'max_uses' => $row['max_uses'],
            'used_count' => $row['used_count'],
            'status' => $row['status'],
            'weight' => (int)($row['weight'] ?? 0),
            'create_time' => $row['create_time'],
            'use_time' => $row['use_time'],
            'create_time_fmt' => date('Y-m-d H:i:s', $row['create_time'])
        ];
    }

    outputJson(0, 'success', $list, $total);
}

/**
 * 标记售出（状态置为 -1）
 */
function markSold() {
    global $db, $db_prefix, $stockModel;

    $ids = Input::postStrVar('ids');
    $goods_id = Input::postIntVar('goods_id');

    if (empty($ids)) {
        outputJson(1, '请选择要标记的数据');
    }
    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $idArr = array_filter(array_map('intval', explode(',', $ids)));
    if (empty($idArr)) {
        outputJson(1, '无效的ID');
    }

    $idsStr = implode(',', $idArr);
    $skuRows = $db->fetch_all("SELECT DISTINCT sku_id FROM {$db_prefix}stock WHERE id IN ({$idsStr}) AND goods_id = {$goods_id} AND status = 0 AND max_uses = 1 AND used_count < max_uses");

    $db->query("UPDATE {$db_prefix}stock SET status = -1 WHERE id IN ({$idsStr}) AND goods_id = {$goods_id} AND status = 0 AND max_uses = 1 AND used_count < max_uses");
    $updated = (int)$db->affected_rows();

    foreach ($skuRows as $sku) {
        $sku_id = (int)$sku['sku_id'];
        if ($sku_id > 0) {
            $stockModel->syncSkuStock($goods_id, $sku_id, false);
        }
    }

    outputJson(0, 'success', ['updated' => $updated]);
}

/**
 * 取消标记（状态恢复为 0）
 */
function unmarkSold() {
    global $db, $db_prefix, $stockModel;

    $ids = Input::postStrVar('ids');
    $goods_id = Input::postIntVar('goods_id');

    if (empty($ids)) {
        outputJson(1, '请选择要取消标记的数据');
    }
    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $idArr = array_filter(array_map('intval', explode(',', $ids)));
    if (empty($idArr)) {
        outputJson(1, '无效的ID');
    }

    $idsStr = implode(',', $idArr);
    $skuRows = $db->fetch_all("SELECT DISTINCT sku_id FROM {$db_prefix}stock WHERE id IN ({$idsStr}) AND goods_id = {$goods_id} AND status = -1 AND max_uses = 1 AND used_count < max_uses");

    $db->query("UPDATE {$db_prefix}stock SET status = 0 WHERE id IN ({$idsStr}) AND goods_id = {$goods_id} AND status = -1 AND max_uses = 1 AND used_count < max_uses");
    $updated = (int)$db->affected_rows();

    foreach ($skuRows as $sku) {
        $sku_id = (int)$sku['sku_id'];
        if ($sku_id > 0) {
            $stockModel->syncSkuStock($goods_id, $sku_id, false);
        }
    }

    outputJson(0, 'success', ['updated' => $updated]);
}

/**
 * 添加库存
 */
function addStock() {
    global $db, $db_prefix, $stockModel;

    $goods_id = Input::postIntVar('goods_id');
    $sku_id = Input::postIntVar('sku_id');
    $content = trim(Input::postStrVar('content'));

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }
    if (empty($content)) {
        outputJson(1, '卡密内容不能为空');
    }

    $goods = $db->once_fetch_array("SELECT * FROM {$db_prefix}goods WHERE id = {$goods_id}");
    if (empty($goods)) {
        outputJson(1, '商品不存在');
    }

    if ($goods['is_sku'] == 'y' && $sku_id <= 0) {
        outputJson(1, '请选择商品规格');
    }

    if ($goods['is_sku'] == 'n') {
        $defaultSku = $db->once_fetch_array("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$goods_id} LIMIT 1");
        $sku_id = $defaultSku ? (int)$defaultSku['id'] : 0;
    }
    if (empty($sku_id)) {
        outputJson(1, '商品规格ID获取失败');
    }

    $lines = array_filter(array_map('trim', explode("\n", $content)));
    if (empty($lines)) {
        outputJson(1, '卡密内容不能为空');
    }

    $count = $stockModel->addStockBatch($goods_id, $sku_id, $lines, 1);
    $stockModel->syncSkuStock($goods_id, $sku_id, false);

    outputJson(0, 'success', ['count' => $count]);
}

/**
 * 编辑库存
 */
function editStock() {
    global $db, $db_prefix, $stockModel;

    $id = Input::postIntVar('id');
    $goods_id = Input::postIntVar('goods_id');
    $sku_id = Input::postIntVar('sku_id');
    $content = trim(Input::postStrVar('content'));

    if ($id <= 0) {
        outputJson(1, '库存ID无效');
    }
    if (empty($content)) {
        outputJson(1, '卡密内容不能为空');
    }

    $stock = $stockModel->getStockById($id);
    if (empty($stock)) {
        outputJson(1, '库存不存在');
    }
    if ($stock['status'] != 0) {
        outputJson(1, '该卡密已售出或禁用，无法编辑');
    }

    $old_sku_id = (int)$stock['sku_id'];
    $content = addslashes($content);
    $sql = "UPDATE {$db_prefix}stock SET content = '{$content}'";
    if ($sku_id > 0 && $sku_id != $old_sku_id) {
        $sql .= ", sku_id = {$sku_id}";
    }
    $sql .= " WHERE id = {$id}";
    $db->query($sql);

    if ($sku_id > 0 && $sku_id != $old_sku_id) {
        $stockModel->syncSkuStock($goods_id, $old_sku_id, false);
        $stockModel->syncSkuStock($goods_id, $sku_id, false);
    } else {
        $stockModel->syncSkuStock($goods_id, $old_sku_id, false);
    }

    outputJson(0, 'success');
}

/**
 * 删除库存
 */
function deleteStock() {
    global $db, $db_prefix, $stockModel;

    $ids = Input::postStrVar('ids');
    $goods_id = Input::postIntVar('goods_id');

    if (empty($ids)) {
        outputJson(1, '请选择要删除的数据');
    }

    $idArr = array_filter(array_map('intval', explode(',', $ids)));
    if (empty($idArr)) {
        outputJson(1, '无效的ID');
    }

    $idsStr = implode(',', $idArr);
    $skuIds = $db->fetch_all("SELECT DISTINCT sku_id FROM {$db_prefix}stock WHERE id IN ({$idsStr})");

    $stockModel->deleteStock($idArr);

    foreach ($skuIds as $sku) {
        $stockModel->syncSkuStock($goods_id, $sku['sku_id'], false);
    }

    outputJson(0, 'success');
}

/**
 * 导出库存
 */
function exportStock() {
    global $db, $db_prefix;

    $goods_id = Input::getIntVar('goods_id');
    $sku_id = Input::getIntVar('sku_id', -1);

    if ($goods_id <= 0) {
        die('商品ID无效');
    }

    $goods = $db->once_fetch_array("SELECT * FROM {$db_prefix}goods WHERE id = {$goods_id}");
    if (empty($goods)) {
        die('商品不存在');
    }

    $where = "goods_id = {$goods_id} AND status = 0 AND max_uses = 1 AND used_count < max_uses";
    if ($sku_id >= 0) {
        $where .= " AND sku_id = {$sku_id}";
    }

    $sql = "SELECT * FROM {$db_prefix}stock WHERE {$where} ORDER BY id ASC";
    $stocks = $db->fetch_all($sql);
    if (empty($stocks)) {
        die('暂无库存数据');
    }

    $content = '';
    foreach ($stocks as $stock) {
        $content .= $stock['content'] . "\r\n";
    }

    $filename = '独立卡密导出_' . $goods['title'] . '_' . date('YmdHis') . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

/**
 * 库存去重（删除重复卡密）
 */
function dedupeStock() {
    global $db, $db_prefix, $stockModel;

    $goods_id = Input::postIntVar('goods_id');
    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }

    $sql = "DELETE s1 FROM {$db_prefix}stock s1
            INNER JOIN {$db_prefix}stock s2
                ON s1.goods_id = s2.goods_id
                AND s1.sku_id = s2.sku_id
                AND s1.content = s2.content
                AND s1.id > s2.id
            WHERE s1.goods_id = {$goods_id}
                AND s1.status = 0
                AND s1.max_uses = 1
                AND s1.used_count < s1.max_uses
                AND s2.status = 0
                AND s2.max_uses = 1
                AND s2.used_count < s2.max_uses";
    $db->query($sql);
    $deleted = $db->affected_rows();

    $skus = $db->fetch_all("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$goods_id}");
    foreach ($skus as $sku) {
        $stockModel->syncSkuStock($goods_id, (int)$sku['id'], false);
    }

    outputJson(0, 'success', ['deleted' => (int)$deleted]);
}

/**
 * 获取 SKU 名称映射
 */
function getSkuNameMap($goods_id) {
    global $db, $db_prefix;

    $skus = $db->fetch_all("SELECT id, option_ids FROM {$db_prefix}product_sku WHERE goods_id = {$goods_id}");
    $map = [];
    foreach ($skus as $sku) {
        $map[$sku['id']] = getSkuName($sku['option_ids']);
    }
    return $map;
}

/**
 * 输出 JSON
 */
function outputJson($code, $msg, $data = [], $count = 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
        'count' => $count
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
