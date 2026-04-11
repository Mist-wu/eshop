<?php
/**
 * @package EMSHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$goodsModel = new Goods_Model();
$stockModel = new Stock_Model();
$User_Model = new User_Model();
$MediaSort_Model = new MediaSort_Model();
$Template_Model = new Template_Model();

/**
 * 库存管理首页
 * 根据商品类型调用对应插件的方法
 */
if($action == 'index'){
    $goods_id = Input::getIntVar('goods_id');

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    // 获取商品信息
    $goods = $db->once_fetch_array("SELECT * FROM `{$db_prefix}goods` WHERE `id` = {$goods_id}");

    $func = "adminStock" . ucfirst($goods['type']);

    include View::getAdmView('open_head');
    $func($goods);
    include View::getAdmView('open_foot');
    View::output();
}
if($action == 'index_ws_ajax'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $order_by = "order by ";
    $field = Input::getStrVar('field');
    $order_type = Input::getStrVar('order');
    if(!empty($order_type)){
        $order_by .= "s.{$field} {$order_type}, ";
    }

    $order_by .= "s.id asc";

    $sql = "SELECT * FROM `{$db_prefix}goods` where delete_time is null and is_on_shelf=1 order by id asc";
    $goods_json = [];
    $goods = $db->fetch_all($sql);
    foreach($goods as $val){
        $goods_json[] = [
            'text' => $val['title'],
            'value' => $val['id']
        ];
    }

    $goods_json = json_encode($goods_json);
    $goods_id = Input::getIntVar('goods_id');
    $keyword = Input::getStrVar('keyword');
    $sku = Input::getStrVar('sku');


    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit');

    $where = "g.id={$goods_id}";

    if(!empty($sku)){
        $where .= " and s.sku = '{$sku}'";
    }
    if(!empty($keyword)){
        $where .= " and s.content like '%{$keyword}%'";
    }


    $start_limit = !empty($page) ? ($page - 1) * $limit : 0;
    $limit = "LIMIT $start_limit, " . $limit;

    $sql = "SELECT 
            g.title, s.content, s.create_time, g.delete_time, g.type, 
            sku.stock quantity, s.sku, s.id stock_id, g.type goods_type 
            FROM {$db_prefix}stock as s
            left join {$db_prefix}skus sku on s.sku=sku.sku and s.goods_id=sku.goods_id  
            JOIN {$db_prefix}goods as g 
                ON s.goods_id = g.id 
            WHERE 
                {$where}
            GROUP BY s.id 
            {$order_by} {$limit} ";

    $list = $db->fetch_all($sql);

    $sku = $db->fetch_all("select * from {$db_prefix}sku_value");

    foreach($list as $key => $val){
        $list[$key]['sku_name'] = '';
        $list[$key]['create_time'] = date('Y-m-d H:i', $val['create_time']);
        if($val['sku'] == 0){
            continue;
        }
        $s = explode('-', $val['sku']);
        foreach($sku as $v){
            foreach($s as $sv){
                if($v['id'] == $sv){
                    $list[$key]['sku_name'] .= $v['name'] . "；";
                }
            }
        }
    }
    $res = $db->once_fetch_array("SELECT 
    COUNT(DISTINCT s.id) AS total 
FROM {$db_prefix}stock as s 
JOIN {$db_prefix}goods as g 
    ON s.goods_id = g.id 
WHERE 
    {$where}
    ; ");

    $total = $res['total'];
    output::data($list, $res['total']);
}

if ($action == 'index_ys_ajax') {

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $sql = "SELECT * FROM `{$db_prefix}goods` where delete_time is null and is_on_shelf=1 order by id asc";
    $goods_json = [];
    $goods = $db->fetch_all($sql);
    foreach($goods as $val){
        $goods_json[] = [
            'text' => $val['title'],
            'value' => $val['id']
        ];
    }

    $goods_json = json_encode($goods_json);
    $goods_id = Input::getIntVar('goods_id', 0);
    $keyword = Input::getStrVar('keyword', '');

    $page = Input::getIntVar('page', 1);


    $where = "g.type NOT IN ('post', 'xuni') and d.delete_time is null";
    if(!empty($goods_id)){
        $where .= " and g.id={$goods_id}";
    }
    if(!empty($keyword)){
        $where .= " and (d.content LIKE '%{$keyword}%' or skv.name LIKE '%{$keyword}%')";
    }


    $start_limit = !empty($page) ? ($page - 1) * Option::get('admin_article_perpage_num') : 0;
    $limit = "LIMIT $start_limit, " . Option::get('admin_article_perpage_num');

    if(empty($keyword) && empty($keyword)){
        $sql = "select 
                d.*, g.title, g.delete_time goods_delete_time, g.type goods_type, ol.sku
            from " . DB_PREFIX . "deliver d
            left join " . DB_PREFIX . "order_list ol on d.order_list_id=ol.id 
            left join " . DB_PREFIX . "goods g on ol.goods_id=g.id
            where {$where}
            order by id desc {$limit}";
    }else{
        $sql = "SELECT 
    d.*, 
    g.title, 
    g.delete_time goods_delete_time, 
    g.type goods_type, ol.sku, 
    GROUP_CONCAT(DISTINCT skv.name SEPARATOR ', ') AS sku_names
FROM " . DB_PREFIX . "deliver d
LEFT JOIN " . DB_PREFIX . "order_list ol ON d.order_list_id = ol.id 
LEFT JOIN " . DB_PREFIX . "goods g ON ol.goods_id = g.id
LEFT JOIN " . DB_PREFIX . "sku_value skv 
    ON FIND_IN_SET(skv.id, REPLACE(ol.sku, '-', ',')) > 0
WHERE {$where}
GROUP BY d.id
ORDER BY d.id DESC {$limit};";
    }


    $list = $db->fetch_all($sql);



    $sku = $db->fetch_all("select * from " . DB_PREFIX . "sku_value");

    foreach($list as $key => $val){
        $list[$key]['sku_name'] = '';
        $list[$key]['create_time'] = date('Y-m-d H:i', $val['create_time']);
        if(empty($val['sku'])){
            continue;
        }
        $s = explode('-', $val['sku']);
        foreach($sku as $v){
            foreach($s as $sv){
                if($v['id'] == $sv){
                    $list[$key]['sku_name'] .= $v['name'] . "；";
                }
            }
        }
    }

//    d($list);die;

    if(empty($keyword) && empty($keyword)){
        $res = $db->once_fetch_array("select 
                count(d.id) total
            from " . DB_PREFIX . "deliver d
            left join " . DB_PREFIX . "order_list ol on d.order_list_id=ol.id 
            left join " . DB_PREFIX . "goods g on ol.goods_id=g.id
            where {$where}");
    }else{
        $sql = "select 
                count(d.id) total
            from " . DB_PREFIX . "deliver d
            left join " . DB_PREFIX . "order_list ol on d.order_list_id=ol.id 
            left join " . DB_PREFIX . "goods g on ol.goods_id=g.id
            LEFT JOIN " . DB_PREFIX . "sku_value skv ON FIND_IN_SET(skv.id, REPLACE(ol.sku, '-', ',')) > 0
            where {$where}";
//echo $sql;die;

        $res = $db->once_fetch_array($sql);
    }

    output::data($list, $res['total']);



}

if($action == 'export_page'){
    $goods_id = Input::getIntVar('goods_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "SELECT * FROM `{$db_prefix}goods` WHERE `id` = {$goods_id}";
    $goods = $db->once_fetch_array($sql);
    include View::getAdmView('open_head');
    require_once View::getAdmView('stock_export');
    include View::getAdmView('open_foot');
    View::output();
}

if($action == 'export_log'){
    $goods_id = Input::getIntVar('goods_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "SELECT * FROM `{$db_prefix}goods` WHERE `id` = {$goods_id}";
    $goods = $db->once_fetch_array($sql);
    include View::getAdmView('open_head');
    require_once View::getAdmView('stock_export_log');
    include View::getAdmView('open_foot');
    View::output();
}
if($action == 'export_log_ajax'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $page = Input::getIntVar('page', 1);
    $page_num = Input::getIntVar('limit', 10);
    $start = ($page - 1) * $page_num;
    $limit = "limit {$start}, {$page_num}";
    $order_field = Input::getStrVar('field', 'id');
    $order_type = Input::getStrVar('order', 'desc');
    $order_by = "order by l.{$order_field} {$order_type}";

    $sql = "select l.*, g.title from {$db_prefix}stock_export_log as l 
left join {$db_prefix}goods as g on g.id=l.goods_id 
 {$order_by} {$limit}";
    $list = $db->fetch_all($sql);
    foreach($list as $key => $val){
        $list[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
    }
    $sql = "select count(distinct id) as count from {$db_prefix}stock_export_log";
    $total = $db->once_fetch_array($sql)['count'];

    output::data($list, $total);
}

if($action == 'index_ys'){
    $goods_id = Input::getIntVar('goods_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "SELECT * FROM `{$db_prefix}goods` WHERE `id` = {$goods_id}";
    $goods = $db->once_fetch_array($sql);
    $goods['stock_page'] = "stock_index_ys";

    doMultiAction("adm_stock_page_ys", $goods, $goods);

    include View::getAdmView('open_head');

    require_once View::getAdmView($goods['stock_page']);
    include View::getAdmView('open_foot');
    View::output();
}

if(empty($action) || $action == 'sales'){
    $br = '<ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="./">控制台</a></li>
        <li class="breadcrumb-item"><a href="./goods.php">商品管理</a></li>
        <li class="breadcrumb-item active" aria-current="page">库存管理</li>
    </ol>';
}

if (empty($action)) {

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $sql = "SELECT * FROM `{$db_prefix}goods` where delete_time is null and is_on_shelf=1 order by id asc";
    $goods_json = [];
    $goods = $db->fetch_all($sql);
    foreach($goods as $val){
        $goods_json[] = [
            'text' => $val['title'],
            'value' => $val['id']
        ];
    }

    $goods_json = json_encode($goods_json);
    $goods_id = Input::getIntVar('goods_id', 0);
    $keyword = Input::getStrVar('keyword', '');


	$page = Input::getIntVar('page', 1);


    $where = "";
    if(!empty($goods_id)){
        $where .= "g.id={$goods_id}";
    }
    if(!empty($keyword)){
        if(empty($where)){
            $where .= "s.content LIKE '%{$keyword}%' or skv.name LIKE '%{$keyword}%'";
        }else{
            $where .= " and (s.content LIKE '%{$keyword}%' or skv.name LIKE '%{$keyword}%')";
        }
    }
//echo $where;die;

    $start_limit = !empty($page) ? ($page - 1) * Option::get('admin_article_perpage_num') : 0;
    $limit = "LIMIT $start_limit, " . Option::get('admin_article_perpage_num');
    if(empty($goods_id) && empty($keyword)){
        $sql = "SELECT s.create_time, g.title, s.content, g.delete_time, g.type goods_type, s.quantity, s.sku, s.id stock_id FROM " . DB_PREFIX . "stock as s join " . DB_PREFIX . "goods as g on s.goods_id=g.id order by s.id desc {$limit}";
    }else{

        $sql = "SELECT 
    g.title, s.content, s.create_time, g.delete_time, g.type, s.quantity, s.sku, s.id stock_id, g.type goods_type 
FROM " . DB_PREFIX . "stock as s 
JOIN " . DB_PREFIX . "goods as g 
    ON s.goods_id = g.id 
LEFT JOIN " . DB_PREFIX . "sku_value as skv 
    ON FIND_IN_SET(skv.id, REPLACE(s.sku, '-', ',')) > 0 
WHERE 
    {$where}
GROUP BY s.id 
ORDER BY s.id DESC {$limit} ";
    }
//echo $sql;die;
//    OR s.content LIKE '%{$keyword}%'
//    OR skv.name LIKE '%{$keyword}%'

    $list = $db->fetch_all($sql);
//echo 1;die;

    $sku = $db->fetch_all("select * from " . DB_PREFIX . "sku_value");

    foreach($list as $key => $val){
        $list[$key]['sku_name'] = '';
        if($val['sku'] == 0){
            continue;
        }
        $s = explode('-', $val['sku']);
        foreach($sku as $v){
            foreach($s as $sv){
                if($v['id'] == $sv){
                    $list[$key]['sku_name'] .= $v['name'] . "；";
                }
            }
        }
    }
    if(empty($goods_id) && empty($keyword)){
        $res = $db->once_fetch_array("SELECT count(s.id) total FROM " . DB_PREFIX . "stock as s join " . DB_PREFIX . "goods as g on s.goods_id=g.id");
    }else{
        $res = $db->once_fetch_array("SELECT 
    COUNT(DISTINCT s.id) AS total 
FROM " . DB_PREFIX . "stock as s 
JOIN " . DB_PREFIX . "goods as g 
    ON s.goods_id = g.id 
LEFT JOIN " . DB_PREFIX . "sku_value as skv 
    ON FIND_IN_SET(skv.id, REPLACE(s.sku, '-', ',')) > 0 
WHERE 
    {$where}
    ; ");
    }

    $total = $res['total'];


    $subPage = '';
    foreach ($_GET as $key => $val) {
        $subPage .= $key != 'page' ? "&$key=$val" : '';
    }
    $pageurl = pagination($total, Option::get('admin_article_perpage_num'), $page, "stock.php?{$subPage}&page=");

    $Sort_Model = new Sort_Model();
    $sorts = $Sort_Model->getSorts('goods');
    $goods = $db->fetch_all("select * from " . DB_PREFIX . "goods where delete_time is null order by id desc");

    $skus = $db->fetch_all("select goods_id, sku from " . DB_PREFIX . "skus");

    $sku_list = [];
    foreach($skus as $key => $val){
        $sku_list[$key]['sku_name'] = '';
        if($val['sku'] == 0){
            continue;
        }
        $sku_list[$key]['goods_id'] = $val['goods_id'];
        $sku_list[$key]['sku'] = $val['sku'];
        $sku_list[$key]['sku_name'] = '';
        $s = explode('-', $val['sku']);
        foreach($sku as $v){
            foreach($s as $sv){
                if($v['id'] == $sv){
                    $sku_list[$key]['sku_name'] .= $v['name'] . "；";
                }
            }
        }
    }


    include View::getAdmView(User::haveEditPermission() ? 'header' : 'uc_header');
    require_once View::getAdmView('stock');
    include View::getAdmView(User::haveEditPermission() ? 'footer' : 'uc_footer');
    View::output();
}

if($action == 'export_ajax'){
    $goods_id = Input::postStrVar('goods_id');
    $sku = Input::postStrVar('sku');
    $export_range = Input::postStrVar('export_range');
    $export_num = Input::postIntVar('export_num', 0);
    $is_delete = Input::postStrVar('is_delete');

    $start_time = Input::postStrVar('start_time', null);
    $end_time = Input::postStrVar('end_time', null);


    if($export_range == 'time'){
        if(empty($start_time) || empty($end_time)){
            output::error('请选择卡密添加时间');
        }
        if($start_time > $end_time){
            output::error('开始时间不能大于截止时间');
        }
    }

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $where = '';
    if($sku != 0 && !empty($sku)){
        $where .= " and sku='{$sku}'";
    }
    if($export_range == 'time'){
        $start_time .= ' 00:00:00';
        $end_time .= ' 23:59:59';
        $where .= " and create_time BETWEEN UNIX_TIMESTAMP('{$start_time}') and UNIX_TIMESTAMP('{$end_time}')";
    }

    $sql = "select * from {$db_prefix}stock where goods_id={$goods_id} {$where} order by id asc;";



    $stock = $db->fetch_all($sql);

    if(empty($stock)){
        emMsg('暂无库存', 'javascript:window.close();');
    }

    $sku_value = $db->fetch_all("select * from {$db_prefix}sku_value");

    $data = [];
    foreach($stock as $val){
        if($val['sku'] == 0){
            if($export_range == 'num'){
                if(!empty($data['默认规格']) && count($data['默认规格']) >= $export_num){
                    continue;
                }
            }
            $data['默认规格'][] = [
                'content' => $val['content'],
                'id' => $val['id'],
                'sku' => $val['sku'],
                'goods_id' => $val['goods_id']
            ];
        }else{


            $temp = explode('-', $val['sku']);
            $sku_name = "";
            foreach($temp as $v){
                foreach($sku_value as $sv){
                    if($v == $sv['id']){
                        $sku_name .= $sv['name'] . "；";
                    }
                }
            }

            if($export_range == 'num'){
                if(!empty($data[$sku_name]) && count($data[$sku_name]) >= $export_num){
                    continue;
                }
            }

            $data[$sku_name][] = [
                'content' => $val['content'],
                'id' => $val['id'],
                'quantity' => $val['quantity'],
                'sku' => $val['sku'],
                'goods_id' => $val['goods_id']
            ];
        }
    }
    $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$goods_id}");

    $timestamp = time();

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $filename = "导出卡密_{$timestamp}_{$goods_id}.txt";
    $saveDir = EM_ROOT . '/content/em_temp/';
    if (!is_dir($saveDir)) {
        mkdir($saveDir, 0755, true);
    }
    $filePath = $saveDir . $filename;
    // 循环数组并写入文件
    $fileHandle = fopen($filePath, 'w');
    if ($fileHandle) {
        // 遍历数组，将每个卡密写入文件
        foreach ($data as $key => $val) {
            fwrite($fileHandle, "---- " . $key . "\n");
            foreach ($val as $v) {
                fwrite($fileHandle, $v['content'] . "\n");
            }
        }
        fclose($fileHandle);


        // 删除卡密
        try {
            $db->beginTransaction();

            if($is_delete == 1){

                foreach($data as $val){
                    $ids = [];
                    foreach($val as $v){
                        $ids[] = $v['id'];
                    }
                    $ids = implode(',', $ids);
                    $db->query("delete from {$db_prefix}stock where id in({$ids})");

                    $goods_stock_count = $stockModel->getStockCount($v['goods_id']);
                    $goods_sku_stock = $stockModel->getSkuStock($v['goods_id'], $v['sku']);
                    $stockModel->updateSkuStock($v['goods_id'], $v['sku'], $goods_sku_stock);
                    $stockModel->updateGoodsStock($v['goods_id'], $goods_stock_count);
                }




            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            output::error($e->getMessage());
        }

        // 保存库存导出记录

        $sql = "INSERT INTO `{$db_prefix}stock_export_log` (`filename`, `goods_id`, `create_time`) VALUES ('{$filename}', {$goods_id}, {$timestamp})";
        $db->query($sql);

        // 生成下载地址
        output::ok(EM_URL . 'admin/download.php?filename=' . $filename);
    } else {
        output::error('文件权限不足，请设置网站目录权限为755');
    }


}



if($action == 'del'){
    $ids = Input::postStrVar('ids');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $goods_id = Input::postIntVar('goods_id');
    try {
        $db->beginTransaction();
        $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$goods_id}");
        $res = $db->fetch_all("select * from {$db_prefix}stock where id in ({$ids})");
        $db->query("DELETE FROM {$db_prefix}stock WHERE id IN ({$ids})");
        if($goods['is_sku'] == 'n'){
            $sku = '0';
            if($goods['type'] != 'duli'){
                $stockModel->updateSkuStock($goods_id, $sku, 0);
            }
            $sku_stock_count = $stockModel->getStockCount($goods_id);
            $goods_stock_count = $sku_stock_count;

            $stockModel->updateSkuStock($goods_id, $sku, $sku_stock_count);
            $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
        }
        if($goods['is_sku'] == 'y'){
            foreach($res as $val){
                if($goods['type'] != 'duli'){
                    $stockModel->updateSkuStock($goods_id, $val['sku'], 0);
                }
//                d($val);
                $sku_stock_count = $stockModel->getSkuStock($goods_id, $val['sku']);
//                var_dump($sku_stock_count);die;
                $goods_stock_count = $stockModel->getStockCount($goods_id);

                $stockModel->updateSkuStock($goods_id, $val['sku'], $sku_stock_count);
                $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
            }
        }



        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        output::error($e->getMessage());
    }



    output::ok();
}
if($action == 'del_export_log'){
    $ids = Input::postStrVar('ids');
    $sql = "DELETE FROM " . DB_PREFIX . "stock_export_log WHERE id IN ({$ids})";
    $db = Database::getInstance();
    $db->query($sql);
    output::ok();
}

if($action == 'delete_sales'){
    $ids = Input::postStrVar('ids');
    $timestamp = time();
    $sql = "update " . DB_PREFIX . "deliver set delete_time = '{$timestamp}' WHERE id IN ({$ids})";

    $db = Database::getInstance();
    $db->query($sql);
    output::ok();
}

if ($action === 'add_ajax') {
    $goods_id = Input::postIntVar('goods_id', null);


    if($goods_id){
        $timestamp = time();
        $goods = $goodsModel->getOneGoodsForAdmin($goods_id);
        $db = Database::getInstance();
        $db_prefix = DB_PREFIX;

        try {
            $db->beginTransaction();

            if($goods['is_sku'] == 'n'){
                $sku = '0';
                $content = Input::postStrVar('content');
                $quantity = Input::postIntVar('quantity');
                $skus = $db->once_fetch_array("select * from {$db_prefix}skus where goods_id={$goods_id} and sku='{$sku}'");
                if($goods['type'] == 'guding'){ // 固定卡密
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $stockModel->updateStockContent($is_stock['id'], $content);
                    }else{
                        $stockModel->addStock($goods_id, $sku, $content);
                    }
                    // 更新sku表和商品表的库存数量
                    $stockModel->updateSkuStock($goods_id, $sku, $quantity);
                    $goods_stock_count = $stockModel->getStockCount($goods_id, $sku);
                    $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                }
                if($goods['type'] == 'xuni'){ // 虚拟服务
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $stockModel->updateStockContent($is_stock['id'], null);
                    }else{
                        $stockModel->addStock($goods_id, $sku, null);
                    }
                    // 更新sku表和商品表的库存数量
                    $stockModel->updateSkuStock($goods_id, $sku, $quantity);
                    $goods_stock_count = $stockModel->getStockCount($goods_id, $sku);
                    $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                }
                if($goods['type'] == 'post'){ // 自定义访问接口
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $update = [
                            'create_time' => $timestamp,
                            'quantity' => Input::postIntVar('quantity', 0),
                        ];
                        $stockModel->updateStock($update, $goods_id, $sku);
                        $add_stock_count += Input::postIntVar('quantity', 0) - $is_stock['quantity'];
                    }else{
                        $insert = [
                            'goods_id' => $goods_id,
                            'sku' => $sku,
                            'create_time' => $timestamp,
                            'quantity' => Input::postIntVar('quantity'),
                        ];
                        $stockModel->addStock($insert);
                        $add_stock_count += Input::postIntVar('quantity');
                    }
                }
                if($goods['type'] == 'duli'){ // 独立卡密
                    $stock = Input::postStrVar('content');
                    $stock = array_filter(explode("\n", $stock));

                    if(!empty($stock)){
                        // 每批次插入数量（可根据实际情况调整，建议500-2000之间）
                        $batchSize = 1000;
                        $total = count($stock);
                        $batches = array_chunk($stock, $batchSize); // 分割数组为多个批次
                        foreach($batches as $batch){
                            $content = [];
                            foreach($batch as $v){
                                $content[] = "({$goods_id}, '0', '{$v}', '{$timestamp}')";
                            }
                            // 执行当前批次的批量插入
                            $db->query("INSERT INTO {$db_prefix}stock (goods_id, sku, content, create_time) VALUES " . implode(',', $content));
                            // 释放当前批次内存
                            unset($content, $batch);
                        }
                        // 更新sku表和商品表的库存数量
                        $goods_stock_count = $stockModel->getStockCount($goods_id, $sku);
                        $sku_stock_count = $goods_stock_count;
                        $stockModel->updateSkuStock($goods_id, $sku, $sku_stock_count);
                        $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                    }
                }

            }
            if($goods['is_sku'] == 'y'){
                $quantity = Input::postIntVar('quantity');
                $sku = Input::postStrVar('sku');
                $content = Input::postStrVar('content');
                if(empty($sku)){
                    throw new Exception('请选择商品规格');
                }
                if($goods['type'] == 'duli'){
                    $stock = Input::postStrVar('content');
                    $stock = array_filter(explode("\n", $stock));
                    if(!empty($stock)){
                        foreach($stock as $v){
                            $stockModel->addStock($goods_id, $sku, $v);
                        }
                    }
                    // 更新sku表和商品表的库存数量
                    $goods_stock_count = $stockModel->getStockCount($goods_id, $sku);
                    $sku_stock_count = $stockModel->getSkuStock($goods_id, $sku);
                    $stockModel->updateSkuStock($goods_id, $sku, $sku_stock_count);
                    $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                }
                if($goods['type'] == 'guding'){
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $stockModel->updateStockContent($is_stock['id'], $content);
                    }else{
                        $stockModel->addStock($goods_id, $sku, $content);
                    }
                    // 更新sku表和商品表的库存数量
                    $stockModel->updateSkuStock($goods_id, $sku, $quantity);
                    $goods_stock_count = $stockModel->getStockCount($goods_id);
                    $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                }
                if($goods['type'] == 'xuni'){
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $stockModel->updateStockContent($is_stock['id'], null);
                    }else{
                        $stockModel->addStock($goods_id, $sku, null);
                    }
                    // 更新sku表和商品表的库存数量
                    $stockModel->updateSkuStock($goods_id, $sku, $quantity);
                    $goods_stock_count = $stockModel->getStockCount($goods_id);
                    $stockModel->updateGoodsStock($goods_id, $goods_stock_count);
                }
                if($goods['type'] == 'post'){
                    $sku = Input::postStrVar('sku');
                    $is_stock = $stockModel->isStock($goods_id, $sku);
                    if($is_stock){
                        $update = [
                            'quantity' => Input::postIntVar('quantity'),
                        ];
                        $stockModel->updateStock($update, $goods_id, $sku);
                        $add_stock_count += Input::postIntVar('quantity') - $is_stock['quantity'];
                    }else{
                        $insert = [
                            'goods_id' => $goods_id,
                            'sku' => $sku,
                            'create_time' => $timestamp,
                            'quantity' => Input::postIntVar('quantity'),
                        ];
                        $stockModel->addStock($insert);
                        $add_stock_count += Input::postIntVar('quantity');
                    }
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            output::error($e->getMessage());
        }
        output::ok();
    }
}

if($action == 'repair'){
    $goods_id = Input::postStrVar('goods_id');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $skus = $db->fetch_all("select * from {$db_prefix}skus where goods_id = {$goods_id}");
    $goods_stock = 0;

    try {
        $db->beginTransaction();
        foreach($skus as $val){
            $stock = $db->fetch_all("select * from {$db_prefix}skus where goods_id = {$goods_id} and sku = '{$val['sku']}'");
            $count = 0;
            foreach($stock as $v){
                $count += $v['quantity'];
            }
            $db->query("update {$db_prefix}goods_trade set stock = {$count} where goods_id = {$goods_id} and sku = '{$val['sku']}'");
            $goods_stock += $count;
        }
        $db->query("update {$db_prefix}goods set stock = $goods_stock where id = {$goods_id}");
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        output::error($e->getMessage());
    }
    output::ok();
}

// 编辑库存
if ($action === 'edit_ajax') {
    $stock_id = Input::postIntVar('stock_id');
    $sku_stock = Input::postIntVar('quantity');
    $content = Input::postStrVar('content');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $stockModel = new Stock_Model();
    try {
        $db->beginTransaction();
        // 查询库存条目
        $stock = $db->once_fetch_array("select * from {$db_prefix}stock where id = $stock_id");
        $goods = $db->once_fetch_array("select * from {$db_prefix}goods where id = {$stock['goods_id']}");

        // 获取当前规格下的库存数量
        if($goods['type'] == 'duli'){
            $sku_stock = $stockModel->getSkuStock($stock['goods_id'], $stock['sku']);
        }
        // 修改规格下的库存数量
        $stockModel->updateSkuStock($stock['goods_id'], $stock['sku'], $sku_stock);
        // 获取当前商品下所有的库存数量
        $stock_count = $stockModel->getStockCount($stock['goods_id']);
        // 修改库存内容
        $stockModel->updateStockContent($stock_id, $content);
        // 修改商品表的库存数量
        $stockModel->updateGoodsStock($stock['goods_id'], $stock_count);
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        output::error($e->getMessage());
    }





    Output::ok();
}