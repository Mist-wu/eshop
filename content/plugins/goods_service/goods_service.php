<?php
/*
Plugin Name: 商品类型【虚拟服务】
Version: 1.1.3
Plugin URL:
Description: 创建虚拟服务类型的商品，适合用于需要人工发货的商品（只需设置库存数量，无需卡密）
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

/**
 * 前台 - 商品列表页
 */
function getIsAutoService($type = null){
    return false;
}

function service_plugin_get_type_name_text(){
    return '虚拟服务';
}

/**
 * 后台管理 - 库存管理
 */
function adminStockService($goods){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $skus = $db->fetch_all("SELECT * FROM {$db_prefix}product_sku WHERE goods_id = {$goods['id']} ORDER BY id ASC");
    if (empty($skus)) {
        $skus = [['id' => 0, 'option_ids' => '0', 'stock' => 0, 'sales' => 0]];
    }

    $sku_stock_stats = [];
    foreach ($skus as $sku) {
        $sku_stock_stats[] = [
            'sku_id' => (int)$sku['id'],
            'sku_name' => $goods['is_sku'] == 'y' ? getSkuName($sku['option_ids']) : '默认规格',
            'stock' => (int)($sku['stock'] ?? 0),
            'sales' => (int)($sku['sales'] ?? 0)
        ];
    }

    $total_stock = array_sum(array_column($sku_stock_stats, 'stock'));
    $total_sales = array_sum(array_column($sku_stock_stats, 'sales'));

    $goodsId = (int)$goods['id'];
    $page = max(1, (int)Input::getIntVar('page', 1));
    $pageSize = (int)Option::get('admin_article_perpage_num');
    if ($pageSize <= 0) {
        $pageSize = 20;
    }
    $keyword = trim((string)Input::getStrVar('keyword', ''));
    $tab = trim((string)Input::getStrVar('tab', 'stock'));
    if ($tab !== 'sold') {
        $tab = 'stock';
    }

    $where = "ol.goods_id = {$goodsId} AND su.stock_id = 0";
    if ($keyword !== '') {
        $keywordEsc = $db->escape_string($keyword);
        $like = "%{$keywordEsc}%";
        $where .= " AND (su.content LIKE '{$like}' OR o.up_no LIKE '{$like}' OR o.out_trade_no LIKE '{$like}' OR o.trade_no LIKE '{$like}' OR o.api_trade_no LIKE '{$like}')";
    }

    $countRow = $db->once_fetch_array("SELECT COUNT(*) as total
        FROM {$db_prefix}stock_usage su
        INNER JOIN {$db_prefix}order_list ol ON su.order_list_id = ol.id
        INNER JOIN {$db_prefix}order o ON su.order_id = o.id
        WHERE {$where}");
    $total = (int)($countRow['total'] ?? 0);

    $offset = ($page - 1) * $pageSize;
    $records = $db->fetch_all("SELECT su.*, ol.sku, o.up_no, o.out_trade_no
        FROM {$db_prefix}stock_usage su
        INNER JOIN {$db_prefix}order_list ol ON su.order_list_id = ol.id
        INNER JOIN {$db_prefix}order o ON su.order_id = o.id
        WHERE {$where}
        ORDER BY su.id DESC
        LIMIT {$offset}, {$pageSize}");

    $baseUrl = "stock.php?action=index&goods_id={$goodsId}&tab=sold";
    if ($keyword !== '') {
        $baseUrl .= "&keyword=" . urlencode($keyword);
    }

    include_once EM_ROOT . '/content/plugins/goods_service/views/admin_stock.php';
}

/**
 * 后台管理 - 订单详情页
 */
function adminOrderDetailService($goods, $order, $child_order, $user){
    include_once EM_ROOT . '/content/plugins/goods_service/views/admin_order_detail.php';
}

/**
 * 个人中心 - 订单详情页
 */
function orderDetailService($goods, $order, $child_order){
    include_once EM_ROOT . '/content/plugins/goods_service/views/user_order_detail.php';
}

/**
 * 发货操作（人工发货：支付后进入待处理）
 */
function goodsDeliverService($goods, $order, $child_order){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $timestamp = time();

    $option_ids = empty($child_order['sku']) ? '0' : $child_order['sku'];
    $option_ids = $db->escape_string($option_ids);
    $sku_row = $db->once_fetch_array("SELECT id, stock FROM {$db_prefix}product_sku WHERE goods_id = {$child_order['goods_id']} AND option_ids = '{$option_ids}'");
    $sku_id = $sku_row ? (int)$sku_row['id'] : 0;
    $stock = $sku_row ? (int)$sku_row['stock'] : 0;
    $quantity = (int)$child_order['quantity'];

    $status = 1; // 待发货
    $content = ['订单已支付，等待商家处理发货'];
    $delivered_count = 0;

    if ($sku_id > 0 && $stock >= $quantity) {
        $db->query("UPDATE {$db_prefix}product_sku SET stock = stock - {$quantity}, sales = sales + {$quantity} WHERE id = {$sku_id}");
        $delivered_count = $quantity;
    }

    $db->query("UPDATE {$db_prefix}order SET status = {$status}, pay_time = {$timestamp} WHERE id = {$order['id']}");
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
function getOneGoodsForHomeService($goods){
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
    $goods['is_auto'] = false;
    return $goods;
}

/**
 * 用户 API - 获取订单发货内容
 */
function plugin_goods_service_get_order_serect($db, $db_prefix, $goods, $order, $child_order, $limit = 0){
    $limitSql = '';
    $limit = (int)$limit;
    if ($limit > 0) {
        $limitSql = " LIMIT {$limit}";
    }
    $sql = "SELECT content, create_time
            FROM {$db_prefix}stock_usage
            WHERE order_list_id = {$child_order['id']} AND stock_id = 0
            ORDER BY id ASC{$limitSql}";
    $list = $db->fetch_all($sql);
    return $list ?: [];
}

/**
 * 后台 - 下载发货内容
 */
function adm_download_deliver_content_service($db, $db_prefix, $order_list_id){
    $sql = "SELECT content FROM {$db_prefix}stock_usage
            WHERE order_list_id = {$order_list_id} AND stock_id = 0
            ORDER BY id ASC";
    $rows = $db->fetch_all($sql);
    if (empty($rows)) {
        die('暂无发货内容');
    }
    $content = '';
    foreach ($rows as $row) {
        $content .= $row['content'] . "\r\n";
    }
    $filename = '发货内容_' . date('YmdHis') . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

/**
 * Hook: 后台商品列表类型徽章
 */
function plugin_goods_service_list_type($type){
    echo "{{#  if(d.type == 'service'){ }}<span class=\\\"layui-badge layui-bg-orange\\\">人工发货</span>{{#  } }}";
}
addAction('adm_goods_list_type', 'plugin_goods_service_list_type');

/**
 * Hook: 添加商品类型
 */
function plugin_goods_service_type($goods, &$result){
    $workingGoods = !empty($result) && is_array($result) ? $result : $goods;
    $workingGoods['goods_type_all'][] = ['name' => '人工发货', 'value' => 'service'];
    $result = $workingGoods;
}
addAction('adm_add_goods_goodsinfo', 'plugin_goods_service_type');

/**
 * Hook: 后台发货视图
 */
function plugin_goods_service_adm_deliver_view($db, $db_prefix, $goods, $order, $child_order){
    if ($goods['type'] != 'service') {
        return;
    }
    include View::getAdmView('open_head');
    include EM_ROOT . '/content/plugins/goods_service/views/adm_deliver.php';
    include View::getAdmView('open_foot');
    View::output();
}
addAction('adm_deliver_view', 'plugin_goods_service_adm_deliver_view');
