<?php
/**
 * 通用卡密库存管理 API
 *
 * @package ESHOP
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
    case 'stock_list':
        getStockList();
        break;
    case 'sold_list':
        getSoldList();
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
    case 'export_stock':
        exportStock();
        break;
    default:
        outputJson(1, '未知操作');
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

    $where = "s.goods_id = {$goods_id} AND s.status = 0 AND s.max_uses > 0 AND s.used_count < s.max_uses";
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
        $maxUses = (int)$row['max_uses'];
        $usedCount = (int)$row['used_count'];
        $remaining = $maxUses > 0 ? max(0, $maxUses - $usedCount) : 0;

        $list[] = [
            'id' => $row['id'],
            'sku_id' => $row['sku_id'],
            'sku_name' => $skuNames[$row['sku_id']] ?? '默认',
            'content' => $row['content'],
            'max_uses' => $maxUses,
            'used_count' => $usedCount,
            'remaining' => $remaining,
            'create_time' => $row['create_time'],
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
 * 添加库存
 */
function addStock() {
    global $db, $db_prefix, $stockModel;

    $goods_id = Input::postIntVar('goods_id');
    $sku_id = Input::postIntVar('sku_id');
    $content = trim(Input::postStrVar('content'));
    $max_uses = Input::postIntVar('max_uses', 1);

    if ($goods_id <= 0) {
        outputJson(1, '商品ID无效');
    }
    if (empty($content)) {
        outputJson(1, '卡密内容不能为空');
    }
    if ($max_uses <= 0) {
        outputJson(1, '可用次数必须大于 0');
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

    $count = $stockModel->addStockBatch($goods_id, $sku_id, $lines, $max_uses);
    $stockModel->syncSkuStock($goods_id, $sku_id, true);

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
    $max_uses = Input::postIntVar('max_uses', 1);

    if ($id <= 0) {
        outputJson(1, '库存ID无效');
    }
    if (empty($content)) {
        outputJson(1, '卡密内容不能为空');
    }
    if ($max_uses <= 0) {
        outputJson(1, '可用次数必须大于 0');
    }

    $stock = $stockModel->getStockById($id);
    if (empty($stock)) {
        outputJson(1, '库存不存在');
    }
    if ($stock['status'] != 0) {
        outputJson(1, '该卡密已售出或禁用，无法编辑');
    }
    if ($max_uses < (int)$stock['used_count']) {
        outputJson(1, '可用次数不能小于已使用次数');
    }

    $old_sku_id = (int)$stock['sku_id'];
    $content = addslashes($content);
    $new_status = ($max_uses > 0 && (int)$stock['used_count'] >= $max_uses) ? 1 : 0;
    $sql = "UPDATE {$db_prefix}stock SET content = '{$content}', max_uses = {$max_uses}, status = {$new_status}";
    if ($sku_id > 0 && $sku_id != $old_sku_id) {
        $sql .= ", sku_id = {$sku_id}";
    }
    $sql .= " WHERE id = {$id}";
    $db->query($sql);

    if ($sku_id > 0 && $sku_id != $old_sku_id) {
        $stockModel->syncSkuStock($goods_id, $old_sku_id, true);
        $stockModel->syncSkuStock($goods_id, $sku_id, true);
    } else {
        $stockModel->syncSkuStock($goods_id, $old_sku_id, true);
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
        $stockModel->syncSkuStock($goods_id, $sku['sku_id'], true);
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

    $where = "goods_id = {$goods_id} AND status = 0 AND max_uses > 0 AND used_count < max_uses";
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

    $filename = '通用卡密导出_' . $goods['title'] . '_' . date('YmdHis') . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
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
