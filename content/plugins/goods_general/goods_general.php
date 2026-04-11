<?php
/*
Plugin Name: 商品类型【通用卡密】
Version: 1.1.1
Plugin URL:
Description: 创建通用卡密类型的商品，并管理与发货相关卡密库存。适合用于卡密可重复使用的商品类型（如教程、资料等）
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

/**
 * 前台 - 商品列表页
 */
function getIsAutoGeneral($type = null){
    return true;
}

function general_plugin_get_type_name_text(){
    return '通用卡密';
}

/**
 * 后台管理 - 库存管理
 */
function adminStockGeneral($goods){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    // 获取 SKU 列表
    $skus = $db->fetch_all("SELECT * FROM {$db_prefix}product_sku WHERE goods_id = {$goods['id']} ORDER BY id ASC");
    if (empty($skus)) {
        $skus = [['id' => 0, 'option_ids' => '0']];
    }

    // 获取每个规格的库存统计（通用卡密按剩余次数统计）
    $sku_stock_stats = [];
    foreach ($skus as $sku) {
        $sku_id = (int)$sku['id'];
        $availableRow = $db->once_fetch_array(
            "SELECT SUM(max_uses - used_count) as total
             FROM {$db_prefix}stock
             WHERE goods_id = {$goods['id']} AND sku_id = {$sku_id}
             AND status = 0 AND max_uses > 0 AND used_count < max_uses"
        );
        $available = (int)($availableRow['total'] ?? 0);

        $soldRow = $db->once_fetch_array(
            "SELECT COUNT(*) as cnt FROM {$db_prefix}stock_usage u
             INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
             WHERE s.goods_id = {$goods['id']} AND s.sku_id = {$sku_id}"
        );
        $sold = (int)($soldRow['cnt'] ?? 0);

        $sku_stock_stats[] = [
            'sku_id' => $sku_id,
            'sku_name' => $goods['is_sku'] == 'y' ? getSkuName($sku['option_ids']) : '默认规格',
            'available' => $available,
            'sold' => $sold
        ];
    }

    $total_available = array_sum(array_column($sku_stock_stats, 'available'));
    $total_sold = array_sum(array_column($sku_stock_stats, 'sold'));

    include_once EM_ROOT . '/content/plugins/goods_general/views/admin_stock.php';
}

/**
 * 后台管理 - 订单详情页
 */
function adminOrderDetailGeneral($goods, $order, $child_order, $user){
    include_once EM_ROOT . '/content/plugins/goods_general/views/admin_order_detail.php';
}

/**
 * 个人中心 - 订单详情页
 */
function orderDetailGeneral($goods, $order, $child_order){
    include_once EM_ROOT . '/content/plugins/goods_general/views/user_order_detail.php';
}

/**
 * 发货操作（通用卡密）
 */
function goodsDeliverGeneral($goods, $order, $child_order){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $stockModel = new Stock_Model();
    $timestamp = time();

    // 获取 SKU ID（用于关联库存）
    $option_ids = empty($child_order['sku']) ? '0' : $child_order['sku'];
    $option_ids = $db->escape_string($option_ids);
    $sku_row = $db->once_fetch_array("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$child_order['goods_id']} AND option_ids = '{$option_ids}'");
    $sku_id = $sku_row ? (int)$sku_row['id'] : 0;

    $need = (int)$child_order['quantity'];
    $available_total = $stockModel->getAvailableTotalUses($child_order['goods_id'], $sku_id);

    $content = [];
    $delivered_count = 0;
    $status = 1;

    if ($available_total >= $need && $need > 0) {
        $sql = "SELECT * FROM {$db_prefix}stock
                WHERE goods_id = {$child_order['goods_id']} AND sku_id = {$sku_id}
                AND status = 0 AND max_uses > 0 AND used_count < max_uses
                ORDER BY id ASC";
        $stock_list = $db->fetch_all($sql);

        $remaining_need = $need;
        foreach ($stock_list as $stock) {
            $remaining = (int)$stock['max_uses'] - (int)$stock['used_count'];
            if ($remaining <= 0) {
                continue;
            }
            $times = min($remaining, $remaining_need);
            for ($i = 0; $i < $times; $i++) {
                if ($stockModel->useStock($stock['id'], $order['id'], $child_order['id'])) {
                    $content[] = $stock['content'];
                    $delivered_count++;
                    $remaining_need--;
                }
                if ($remaining_need <= 0) {
                    break;
                }
            }
            if ($remaining_need <= 0) {
                break;
            }
        }

        if ($delivered_count >= $need) {
            $status = 2;
        } elseif ($delivered_count > 0) {
            $status = -1;
        } else {
            $status = 1;
        }

        if ($delivered_count > 0 && $sku_id > 0) {
            $db->query("UPDATE {$db_prefix}product_sku SET sales = sales + {$delivered_count} WHERE id = {$sku_id}");
        }
        if ($sku_id > 0) {
            $stockModel->syncSkuStock($child_order['goods_id'], $sku_id, true);
        }
    }

    // 更新订单状态
    $db->query("UPDATE {$db_prefix}order SET status = {$status}, pay_time = {$timestamp} WHERE id = {$order['id']}");
    // 更新子订单状态
    $db->query("UPDATE {$db_prefix}order_list SET status = {$status} WHERE id = {$child_order['id']}");

    return [
        'code' => 0,
        'status' => $status,
        'content' => $content,
        'deliver_count' => $delivered_count
    ];
}

/**
 * 前台 - 商品详情页
 */
function getOneGoodsForHomeGeneral($goods){
    $db = Database::getInstance();
    $sql = "SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} ORDER BY user_price ASC";
    $product_sku = $db->fetch_all($sql);

    $skus = [
        'option_name' => [],
        'option_value' => [],
        'option_ids' => [],
        'spec_ids' => []
    ];
    foreach($product_sku as $val){
        $skus['option_value'][$val['option_ids']] = $val;
        $option_ids = explode('-', $val['option_ids']);
        foreach($option_ids as $v){
            $skus['option_ids'][] = $v;
        }
    }
    if($goods['is_sku'] == 'y'){
        $skus['option_ids'] = array_unique($skus['option_ids']);
        $sql = "select * from " . DB_PREFIX . "spec_option where id in (" . implode(',', $skus['option_ids']) . ")";
        $spec_option = $db->fetch_all($sql);
        foreach($spec_option as $val){
            $skus['spec_ids'][] = $val['spec_id'];
        }
        $skus['spec_ids'] = array_unique($skus['spec_ids']);
        $sql = "select * from " . DB_PREFIX . "specification where id in (" . implode(',', $skus['spec_ids']) . ")";
        $specification = $db->fetch_all($sql);

        foreach($specification as $key => $val){
            $skus['option_name'][$key]['title'] = $val['spec_name'];
            $skus['option_name'][$key]['sku_values'] = [];
            foreach($spec_option as $v){
                if($val['id'] == $v['spec_id']){
                    $skus['option_name'][$key]['sku_values'][] = [
                        'option_id' => $v['id'],
                        'option_name' => $v['option_name']
                    ];
                }
            }
        }
    }

    unset($skus['option_ids']);
    unset($skus['spec_ids']);
    $goods['skus'] = $skus;
    $goods['is_auto'] = true;
    return $goods;
}

/**
 * 用户 API - 获取订单发货内容
 */
function plugin_goods_general_get_order_serect($db, $db_prefix, $goods, $order, $child_order, $limit = 0){
    $limitSql = '';
    $limit = (int)$limit;
    if ($limit > 0) {
        $limitSql = " LIMIT {$limit}";
    }
    $sql = "SELECT s.content, u.create_time
            FROM {$db_prefix}stock_usage u
            INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
            WHERE u.order_list_id = {$child_order['id']}
            ORDER BY u.id ASC{$limitSql}";
    $list = $db->fetch_all($sql);
    return $list ?: [];
}

/**
 * 后台 - 下载发货内容
 */
function adm_download_deliver_content_general($db, $db_prefix, $order_list_id){
    $sql = "SELECT s.content
            FROM {$db_prefix}stock_usage u
            INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
            WHERE u.order_list_id = {$order_list_id}
            ORDER BY u.id ASC";
    $rows = $db->fetch_all($sql);
    if (empty($rows)) {
        die('暂无发货内容');
    }
    $content = '';
    foreach ($rows as $row) {
        $content .= $row['content'] . "\r\n";
    }
    $filename = '通用卡密_' . date('YmdHis') . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

/**
 * Hook: 后台商品列表类型徽章
 */
function plugin_goods_general_list_type($type){
    echo "{{#  if(d.type == 'general'){ }}<span class=\\\"layui-badge layui-bg-blue\\\">通用卡密</span>{{#  } }}";
}
addAction('adm_goods_list_type', 'plugin_goods_general_list_type');

/**
 * Hook: 添加商品类型
 */
function plugin_goods_general_type($goods, &$result){
    $workingGoods = !empty($result) && is_array($result) ? $result : $goods;
    $workingGoods['goods_type_all'][] = ['name' => '通用卡密', 'value' => 'general'];
    $result = $workingGoods;
}
addAction('adm_add_goods_goodsinfo', 'plugin_goods_general_type');
