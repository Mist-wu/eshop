<?php
/*
Plugin Name: 商品类型【独立卡密】
Version: 1.3.6
Plugin URL:
Description: 创建一次性卡密类型的商品，并管理与发货相关卡密库存。适合用于有效性为一次性的单条卡密商品
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

/**
 * 前台 - 商品列表页
 */
function getIsAutoOnce(){
    return true;
}

function once_plugin_get_type_name_text(){
    return '独立卡密';
}

/**
 * 后台管理 - 库存管理
 */
function adminStockOnce($goods){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    // 获取 SKU 列表
    $skus = $db->fetch_all("SELECT * FROM {$db_prefix}product_sku WHERE goods_id = {$goods['id']} ORDER BY id ASC");
    if (empty($skus)) {
        $skus = [['id' => 0, 'option_ids' => '0']];
    }

    // 获取每个规格的库存统计
    $sku_stock_stats = [];
    foreach ($skus as $sku) {
        $sku_id = (int)$sku['id'];
        $available = $db->once_fetch_array(
            "SELECT COUNT(*) as cnt FROM {$db_prefix}stock
             WHERE goods_id = {$goods['id']} AND sku_id = {$sku_id}
             AND status = 0 AND max_uses = 1 AND used_count < max_uses"
        )['cnt'] ?? 0;
        $sold = $db->once_fetch_array(
            "SELECT COUNT(*) as cnt FROM {$db_prefix}stock_usage u
             INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
             WHERE s.goods_id = {$goods['id']} AND s.sku_id = {$sku_id}"
        )['cnt'] ?? 0;

        $sku_stock_stats[] = [
            'sku_id' => $sku_id,
            'sku_name' => $goods['is_sku'] == 'y' ? getSkuName($sku['option_ids']) : '默认规格',
            'available' => (int)$available,
            'sold' => (int)$sold
        ];
    }

    $total_available = array_sum(array_column($sku_stock_stats, 'available'));
    $total_sold = array_sum(array_column($sku_stock_stats, 'sold'));

    include_once EM_ROOT . '/content/plugins/goods_once/views/admin_stock.php';
}

/**
 * 后台管理 - 订单详情页
 */
function adminOrderDetailOnce($goods, $order, $child_order, $user){
    include_once EM_ROOT . '/content/plugins/goods_once/views/admin_order_detail.php';
}

/**
 * 个人中心 - 订单详情页
 */
function orderDetailOnce($goods, $order, $child_order){
    include_once EM_ROOT . '/content/plugins/goods_once/views/user_order_detail.php';
}

/**
 * 发货操作
 */
function goodsDeliverOnce($goods, $order, $child_order){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $timestamp = time();
    $big_order_threshold = 3000;
    $batch_size = 1000;
    // 获取 SKU ID（用于关联库存）
    $option_ids = empty($child_order['sku']) ? '0' : $child_order['sku'];
    $option_ids = $db->escape_string($option_ids);
    $goods_id = (int)$child_order['goods_id'];
    $order_id = (int)$order['id'];
    $order_list_id = (int)$child_order['id'];
    $need = (int)$child_order['quantity'];
    $sku_row = $db->once_fetch_array("SELECT id FROM {$db_prefix}product_sku WHERE goods_id = {$goods_id} AND option_ids = '{$option_ids}'");
    $sku_id = $sku_row ? (int)$sku_row['id'] : 0;

    if ($need <= 0) {
        $status = 1;
        $content = [];
        $delivered_count = 0;
        $stock = 0;
    } else {
        $plugin_storage = Storage::getInstance('goods_once');
        $deliver_order = $plugin_storage->getValue('deliver_order');
        switch ($deliver_order) {
            case 'new':
                $order_by = 'weight DESC, id DESC';
                break;
            case 'rand':
                $order_by = 'weight DESC, RAND()';
                break;
            default:
                $order_by = 'weight DESC, id ASC';
                break;
        }

        $lock_sql = "SELECT id FROM {$db_prefix}stock WHERE goods_id = {$goods_id} AND sku_id = {$sku_id} AND status = 0 AND max_uses = 1 ORDER BY {$order_by} LIMIT {$need} FOR UPDATE";
        $lock_rows = $db->fetch_all($lock_sql);
        $available_count = count($lock_rows);

        if ($available_count < $need) {
            // 库存不足，不发货
            $status = 1; // 待处理（无库存）
            $content = [];
            $delivered_count = 0;
            $stock = $available_count;
        } else {
            // 库存充足，按批次处理
            $content = [];
            $delivered_count = 0;
            $stock_ids_all = [];
            foreach ($lock_rows as $row) {
                $stock_ids_all[] = (int)$row['id'];
            }

            $batch_limit = $need > $big_order_threshold ? $batch_size : $need;
            for ($offset = 0; $offset < $need; $offset += $batch_limit) {
                $batch_ids = array_slice($stock_ids_all, $offset, $batch_limit);
                if (empty($batch_ids)) {
                    break;
                }
                $ids_str = implode(',', $batch_ids);
                $stock_list = $db->fetch_all("SELECT id, content FROM {$db_prefix}stock WHERE id IN ({$ids_str}) ORDER BY FIELD(id, {$ids_str})");
                if (empty($stock_list)) {
                    continue;
                }

                $usage_values = [];
                $batch_update_ids = [];
                foreach ($stock_list as $stock) {
                    $stock_id = (int)$stock['id'];
                    $batch_update_ids[] = $stock_id;
                    $content[] = $stock['content'];
                    $usage_values[] = "({$stock_id}, {$order_id}, {$order_list_id}, {$timestamp})";
                }

                if (!empty($usage_values)) {
                    $db->query("INSERT INTO {$db_prefix}stock_usage (stock_id, order_id, order_list_id, create_time) VALUES " . implode(',', $usage_values));
                }
                if (!empty($batch_update_ids)) {
                    $batch_ids_str = implode(',', $batch_update_ids);
                    $db->query("UPDATE {$db_prefix}stock SET status = 1, used_count = 1, use_time = {$timestamp} WHERE id IN ({$batch_ids_str})");
                    $delivered_count += count($batch_update_ids);
                }
            }

            // 更新商品 SKU 表的库存和销量
            $db->query("UPDATE {$db_prefix}product_sku SET sales = sales + {$delivered_count} WHERE id = {$sku_id}");
            $stockModel = new Stock_Model();
            $stock = $stockModel->syncSkuStock($goods_id, $sku_id);
            $status = 2; // 全部发货成功
        }
    }

    // 更新订单状态
    $db->query("UPDATE {$db_prefix}order SET status = {$status}, pay_time = {$timestamp} WHERE id = {$order['id']}");
    // 更新子订单状态
    $db->query("UPDATE {$db_prefix}order_list SET status = {$status} WHERE id = {$child_order['id']}");

    // 标记已处理并返回结果
    $result['code'] = 0;
    $result['status'] = $status;
    $result['content'] = $content;
    $result['deliver_count'] = $delivered_count;
    $result['stock'] = $stock;
    return $result;
}

/**
 * 前台 - 商品详情页
 */
function getOneGoodsForHomeOnce($goods){
    $db = Database::getInstance();
    $sql = "SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} ORDER BY user_price ASC";
    $product_sku = $db->fetch_all($sql);
//    d($product_sku);die;
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
//    d($skus);die;
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
//    d($skus);die;
    $goods['skus'] = $skus;
    $goods['is_auto'] = true;
    return $goods;
}

/**
 * Hook: 添加商品类型
 */
function plugin_goods_once_type($goods, &$result){
    $workingGoods = !empty($result) && is_array($result) ? $result : $goods;
    $workingGoods['goods_type_all'][] = ['name' => '独立卡密', 'value' => 'once'];
    $result = $workingGoods;
}
addAction('adm_add_goods_goodsinfo', 'plugin_goods_once_type');

/**
 * Hook: 后台发货视图
 */
function plugin_goods_once_adm_deliver_view($db, $db_prefix, $goods, $order, $child_order){
    if (($goods['type'] ?? '') != 'once') {
        return;
    }
    include View::getAdmView('open_head');
    include EM_ROOT . '/content/plugins/goods_once/views/adm_deliver.php';
    include View::getAdmView('open_foot');
    View::output();
}
addAction('adm_deliver_view', 'plugin_goods_once_adm_deliver_view');
