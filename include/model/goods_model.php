<?php
/**
 * @package EMSHOP
 */

class Goods_Model {

    private $db;
    private $Parsedown;
    private $table;
    private $table_user;
    private $table_sort;
    private $table_skus;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'goods';
        $this->table_user = DB_PREFIX . 'user';
        $this->table_sort = DB_PREFIX . 'sort';
        $this->table_skus = DB_PREFIX . 'product_sku';
        $this->Parsedown = new Parsedown();
        $this->Parsedown->setBreaksEnabled(true); //automatic line wrapping
    }

    /**
     * 后台管理 - 获取商品列表
     */
    public function getGoodsListForAdmin($post = []){
        // 获取分页参数
        $page = isset($post['page']) ? (int)$post['page'] : 1;
        $limit = isset($post['limit']) ? (int)$post['limit'] : 10;
        $start_limit = ($page - 1) * $limit;

        // 获取查询条件
        $keyword = isset($post['keyword']) ? trim($post['keyword']) : '';
        $category_id = isset($post['category_id']) ? (int)$post['category_id'] : 0;
        $is_on_shelf = isset($post['is_on_shelf']) ? trim($post['is_on_shelf']) : '';

        // 获取排序参数
        $order_field = isset($post['field']) ? $post['field'] : '';
        $order_type = isset($post['order']) ? $post['order'] : '';

        // 构建 WHERE 条件
        $where = "g.delete_time IS NULL";

        if (!empty($keyword)) {
            $keyword = $this->db->escape_string($keyword);
            $where .= " AND g.title LIKE '%{$keyword}%'";
        }

        if ($category_id != 0) {
            $where .= " AND g.sort_id = {$category_id}";
        }

        if ($is_on_shelf !== '') {
            $is_on_shelf_value = $is_on_shelf === 'y' ? 1 : 0;
            $where .= " AND g.is_on_shelf = {$is_on_shelf_value}";
        }

        // 构建 ORDER BY（白名单验证防止SQL注入）
        $order_by = "";
        $allowed_fields = ['id', 'create_time', 'sales', 'stock'];
        if ($order_field && $order_type && in_array($order_field, $allowed_fields)) {
            $order_type = strtoupper($order_type) === 'ASC' ? 'ASC' : 'DESC';
            $order_by = "{$order_field} {$order_type}, ";
        }
        $order_by .= "g.id DESC";

        // 查询商品列表
        $sql = "SELECT
                    g.id, g.cover, g.create_time, g.delete_time, g.index_top,
                    g.is_on_shelf, g.is_sku, g.sort_id, g.sort_top, g.title, g.type, g.group_id, g.home,
                    COALESCE(sku_stats.total_sales, 0) AS sales,
                    COALESCE(sku_stats.total_stock, 0) AS stock
                FROM {$this->table} g
                LEFT JOIN (
                    SELECT goods_id, SUM(sales) AS total_sales, SUM(stock) AS total_stock
                    FROM {$this->table_skus}
                    GROUP BY goods_id
                ) sku_stats ON sku_stats.goods_id = g.id
                WHERE {$where}
                ORDER BY {$order_by}
                LIMIT {$start_limit}, {$limit}";

        $res = $this->db->query($sql);
        $goods = [];

        // 获取分类缓存
        $CACHE = Cache::getInstance();
        $sorts = $CACHE->readCache('sort');

        while ($row = $this->db->fetch_array($res)) {
            $row['timestamp'] = $row['create_time'];
            $row['create_time'] = date("Y-m-d H:i", $row['create_time']);
            $row['title'] = !empty($row['title']) ? htmlspecialchars($row['title']) : '无标题';
            $row['type_text'] = goodsTypeText($row['type']);
            $row['stock'] = number_format($row['stock']);

            $sortName = isset($sorts[$row['sort_id']]['sortname']) ? $sorts[$row['sort_id']]['sortname'] : '未知分类';
            $row['sort_name'] = $row['sort_id'] == -1 ? '未分类' : $sortName;

            // 允许插件扩展商品数据（如对接商品显示来源站点）
            if ($row['group_id'] == -1) {
                $extendData = [];
                doMultiAction('adm_goods_list_extend', $row, $extendData);
                if (!empty($extendData)) {
                    $row = array_merge($row, $extendData);
                }
            }

            $goods[] = $row;
        }

        // 查询总数
        $count_sql = "SELECT COUNT(*) AS total FROM {$this->table} g WHERE {$where}";
        $count_res = $this->db->once_fetch_array($count_sql);
        $total = $count_res['total'];

        return [
            'list' => $goods,
            'total' => (int)$total
        ];
    }

    /**
     * 添加商品
     */
    public function addGoods($post){
        $this->db->beginTransaction();
        try{
            // 写入商品主表
            $goods_insert = [
                'title' => $post['title'],
                'sort_id' => $post['sort_id'],
                'type' => $post['type'],
                'is_sku' => $post['is_sku'],
                'group_id' => $post['group_id'],
                'config' => $post['config'],
                'content' => $post['content'],
                'pay_content' => $post['pay_content'],
                'cover' => $post['cover'],
                'is_on_shelf' => $post['is_on_shelf'],
                'index_top' => $post['index_top'],
                'sort_top' => $post['sort_top'],
                'sort_num' => $post['sort_num'],
                'min_qty' => $post['min_qty'],
                'max_qty' => $post['max_qty'],
                'des' => $post['des'],
                'create_time' => time(),
                'payment' => $post['payment'],
                'link' => $post['link'],
            ];
            // d($goods_insert);die;
            $goods_id = $this->db->add('goods', $goods_insert);
            
            // d($post);die;
            if($post['is_sku'] == 'n'){
                // 先写入商品规格表，获取 sku_id
                $skus_insert = [
                    'goods_id' => $goods_id,
                    'option_ids' => 0,
                    'guest_price' => $post['skus']['guest_price'] * 100,
                    'user_price' => $post['skus']['user_price'] * 100,
                    'market_price' => $post['skus']['market_price'] * 100,
                    'cost_price' => $post['skus']['cost_price'] * 100,
                    'sales' => $post['skus']['sales'],
                    'stock' => empty($post['skus']['stock']) ? 0 : $post['skus']['stock'],
                ];
                $this->db->add('product_sku', $skus_insert);
            }

            if($post['is_sku'] == 'y'){
                // 写入商品规格表
                foreach($post['skus'] as $key => $val){
                    // 先写入 product_sku
                    $skus_insert = [
                        'goods_id' => $goods_id,
                        'option_ids' => $key,
                        'guest_price' => $val['guest_price'] * 100,
                        'user_price' => $val['user_price'] * 100,
                        'market_price' => $val['market_price'] * 100,
                        'cost_price' => $val['cost_price'] * 100,
                        'sales' => $val['sales'],
                        'stock' => empty($val['stock']) ? 0 : $val['stock'],
                    ];
                    $this->db->add('product_sku', $skus_insert);
                }
            }
            $this->db->commit();
        }catch(Throwable $e){
            $this->db->rollBack();
        }
        return $goods_id;
    }

    /**
     * 编辑商品
     */
    public function editGoods($goods_id, $post){
        $this->db->beginTransaction();
        try{
            // 1. 更新商品主表
            $goods_update = [
                'title' => $post['title'],
                'sort_id' => $post['sort_id'],
                'type' => $post['type'],
                'is_sku' => $post['is_sku'],
                'group_id' => $post['group_id'],
                'config' => $post['config'],
                'content' => $post['content'],
                'pay_content' => $post['pay_content'],
                'cover' => $post['cover'],
                'is_on_shelf' => $post['is_on_shelf'],
                'index_top' => $post['index_top'],
                'sort_top' => $post['sort_top'],
                'sort_num' => $post['sort_num'],
                'min_qty' => $post['min_qty'],
                'max_qty' => $post['max_qty'],
                'des' => $post['des'],
                'payment' => $post['payment'],
                'link' => $post['link'],
            ];
            $this->db->update('goods', $goods_update, ['id' => $goods_id]);

            

            // 3. 对比更新 SKU（保留 sku_id）
            // 获取旧的 SKU 数据，以 option_ids 为键
            $old_skus_raw = $this->db->fetch_all("SELECT * FROM `{$this->table_skus}` WHERE `goods_id` = {$goods_id}");
            $old_skus = [];
            foreach($old_skus_raw as $sku){
                $old_skus[$sku['option_ids']] = $sku;
            }

            // 构建新的 SKU 数据，以 option_ids 为键
            $new_skus = [];
            if($post['is_sku'] == 'n'){
                // 单规格：option_ids 为 '0'
                $new_skus['0'] = [
                    'guest_price' => $post['skus']['guest_price'] * 100,
                    'user_price' => $post['skus']['user_price'] * 100,
                    'market_price' => $post['skus']['market_price'] * 100,
                    'cost_price' => $post['skus']['cost_price'] * 100,
                    'sales' => $post['skus']['sales'],
                    'member' => $post['skus']['member'] ?? [],
                ];
            } else {
                // 多规格
                foreach($post['skus'] as $option_ids => $sku_data){
                    $new_skus[$option_ids] = [
                        'guest_price' => $sku_data['guest_price'] * 100,
                        'user_price' => $sku_data['user_price'] * 100,
                        'market_price' => $sku_data['market_price'] * 100,
                        'cost_price' => $sku_data['cost_price'] * 100,
                        'sales' => $sku_data['sales'],
                        'member' => $sku_data['member'] ?? [],
                    ];
                }
            }

            // 计算需要删除、更新、新增的 SKU
            $old_option_ids = array_keys($old_skus);
            $new_option_ids = array_keys($new_skus);

            $to_delete = array_diff($old_option_ids, $new_option_ids); // 旧有新无，删除
            $to_update = array_intersect($old_option_ids, $new_option_ids); // 新旧都有，更新
            $to_insert = array_diff($new_option_ids, $old_option_ids); // 新有旧无，新增

            // 3.1 删除不再存在的 SKU
            foreach($to_delete as $option_ids){
                $sku_id = $old_skus[$option_ids]['id'];
                // 再删除 SKU（注意：如果 em_stock 有关联数据会成为孤儿记录，可根据业务需求决定是否级联删除）
                $this->db->query("DELETE FROM `{$this->table_skus}` WHERE `id` = {$sku_id}");
            }

            // 3.2 更新已存在的 SKU（保留 id 和 stock）
            foreach($to_update as $option_ids){
                $sku_id = $old_skus[$option_ids]['id'];
                $sku_data = $new_skus[$option_ids];

                // 更新 SKU 价格等字段，保留 stock
                $this->db->query("UPDATE `{$this->table_skus}` SET
                    `guest_price` = {$sku_data['guest_price']},
                    `user_price` = {$sku_data['user_price']},
                    `market_price` = {$sku_data['market_price']},
                    `cost_price` = {$sku_data['cost_price']},
                    `sales` = {$sku_data['sales']}
                    WHERE `id` = {$sku_id}");
            }

            // 3.3 新增不存在的 SKU
            foreach($to_insert as $option_ids){
                $sku_data = $new_skus[$option_ids];

                $skus_insert = [
                    'goods_id' => $goods_id,
                    'option_ids' => $option_ids,
                    'guest_price' => $sku_data['guest_price'],
                    'user_price' => $sku_data['user_price'],
                    'market_price' => $sku_data['market_price'],
                    'cost_price' => $sku_data['cost_price'],
                    'sales' => $sku_data['sales'],
                    'stock' => 0, // 新 SKU 默认库存为 0
                ];
                $this->db->add('product_sku', $skus_insert);
            }

            $this->db->commit();
        }catch(Throwable $e){
            $this->db->rollBack();
            return false;
        }
        return true;
    }

    /**
     * 前台商品详情页 - 获取商品动态信息[价格/库存/销量]
     */
    private function getOneGoodsDynamicForHome($goods, $user_id, $user_tier, $quantity, $selected_sku){
//        d($selected_sku);die;
        if($user_tier == 0){
            $tier_discount = 0;
        }else{
            $sql = "select * from " . DB_PREFIX . "user_tier where id = {$user_tier}";
            $user_tier_data = $this->db->once_fetch_array($sql);
            $tier_discount = empty($user_tier_data) || empty($user_tier_data['discount']) ? 1 : $user_tier_data['discount'] / 100;
        }
        $config = json_decode($goods['config'], true);
        // d($config);
        // d($goods);die;
        $stock = 0;
        $sales = 0;
        $cost_price = 0;
        if(empty($selected_sku) && $goods['is_sku'] == 'y'){ // 未选中规格
            // 默认取出第一个规格
            $first_sku = reset($goods['skus']['option_value'])['option_ids']; 
            if(empty($user_id)){ // 未登录 
                $unit_price = $goods['skus']['option_value'][$first_sku]['guest_price'] / 100; // 游客价格
            }else{ // 已登陆
                $unit_price = $goods['skus']['option_value'][$first_sku]['user_price'] / 100; // 用户价格
                if($user_tier){ // 代理价格
                    if(isset($config['tier_price'][$user_tier][$first_sku])){ // 如果为代理设置了第一个规格的价格
                        $unit_price = $config['tier_price'][$user_tier][$first_sku];
                    }else{ // 计算代理折扣率的价格
                        $unit_price *= $tier_discount;
                    }
                }
            }
            foreach($goods['skus']['option_value'] as $val){
                $stock += $val['stock'];
                $sales += $val['sales'];
            }
            $selected_sku_type = 0; // 未选中规格
        }else if(count($selected_sku) < count($goods['skus']['option_name']) && $goods['is_sku'] == 'y'){ // 选择部分规格
            // 取出匹配度最高的一个规格
            $match_sku = $this->findHighestMatchOption(implode('-', $selected_sku), $goods['skus']['option_value']);
            // 取出所有可以匹配到的规格数据
            $all_match_sku = $this->findAllMatchedOptions(implode('-', $selected_sku), $goods['skus']['option_value']);
            if(empty($user_id)){ // 未登录
                $unit_price = $match_sku['guest_price'] / 100;
            }else{ // 已登录
                $unit_price = $match_sku['user_price'] / 100;
                if($user_tier){ // 代理价格
                    if(isset($config['tier_price'][$user_tier][$match_sku['option_ids']])){ // 如果为代理设置了第一个规格的价格
                        $unit_price = $config['tier_price'][$user_tier][$match_sku['option_ids']];
                    }else{ // 计算代理折扣率的价格
                        $unit_price *= $tier_discount;
                    }
                }
            }
            foreach($all_match_sku as $val){
                $stock += $val['stock'];
                $sales += $val['sales'];
            }
            $selected_sku_type = 1; // 仅选中部分规格
        }else{ // 完整选择规格
            // 取出匹配的规格
            if($goods['is_sku'] == 'y'){
                $match_sku = $this->findHighestMatchOption(implode('-', $selected_sku), $goods['skus']['option_value']);
            }else{
                $match_sku = $goods['skus']['option_value'][0];
            }
            if(empty($user_id)){ // 未登录
                $unit_price = $match_sku['guest_price'] / 100;
            }else{ // 已登录
                $unit_price = $match_sku['user_price'] / 100;
                if($user_tier){ // 代理价格
                    if(isset($config['tier_price'][$user_tier][$match_sku['option_ids']])){ // 如果为代理设置了第一个规格的价格
                        $unit_price = $config['tier_price'][$user_tier][$match_sku['option_ids']];
                    }else{ // 计算代理折扣率的价格
                        $unit_price *= $tier_discount;
                    }
                }
            }
            $cost_price = $match_sku['cost_price'];
            $stock = $match_sku['stock'];
            $sales = $match_sku['sales'];
            $selected_sku_type = 2; // 完整选中规格
        }
//         d($config);
        // 批量优惠价格计算
        $qty_discount = [];
        $discount_price = 0;
        if(!empty($config['qty_discount'])){
            foreach($config['qty_discount'] as $val){ // 查找优惠配置是否存在
                if($val['qty'] <= $quantity && (($val['scope'] == 'login' && $user_id) || $val['scope'] == 'all')){
                    $qty_discount = $val;
                }
            }
        }

        if(!empty($qty_discount)){
            if($qty_discount['type'] == 'per_item'){ // 每件优惠
                $unit_price -= $qty_discount['value'];
                $discount_price = $qty_discount['value'] * $quantity;
            }
        }

        $price = emBcMul($unit_price, $quantity, 2); // 精确乘法

        // 处理本商品所有规格的价格
        foreach($goods['skus']['option_value'] as $key => &$val){
            if($user_tier){ // 代理价格
                if(isset($config['tier_price'][$user_tier][$key])){ // 如果为代理设置了该规格的价格
                    $val['price'] = $config['tier_price'][$user_tier][$key];
                }else{ // 计算代理折扣率的价格
                    $val['price'] = $val['user_price'] / 100 * $tier_discount;
                }
            }
        }



        return [
            'price' => $price, // 订单总价
            'unit_price' => $unit_price, // 商品单价
            'cost_price' => $cost_price, // 商品单个成本价
            'discount_price' => $discount_price, // 已优惠价格
            'stock' => $stock, // 库存
            'sales' => $sales, // 销量
            'selected_sku_type' => $selected_sku_type, // 选中规格类型[未选中|部分选中|完整选中]
            'option_value' => $goods['skus']['option_value'], // 所有规格数据
        ];

    }

    /**
     * 前台商品详情页 - 获取商品基础信息
     * @goods_id 商品ID
     * @user_id 用户ID
     * @user_tier 用户等级ID
     * @selected_sku 选中的商品规格
     * @quantity 要购买的商品数量
     * @coupon_code 优惠券码（可选）
     */
    public function getOneGoodsForHome($goods_id, $user_id, $user_tier, $selected_sku, $quantity, $coupon_code = ''){
        
        // 查询商品基本信息
        $sql = "SELECT id, type, title, cover, is_sku, des, content, pay_content, config, group_id, payment, min_qty, max_qty, sort_id
                FROM {$this->table} 
                WHERE id = {$goods_id} AND delete_time IS NULL AND is_on_shelf = 1";
        $goods = $this->db->once_fetch_array($sql);
        
        if (empty($goods)) {
            return false;
        }
//        d($goods);die;
        // 购买数量限制
        $goods['min_qty'] = isset($goods['min_qty']) ? (int)$goods['min_qty'] : 1;
        if ($goods['min_qty'] < 1) {
            $goods['min_qty'] = 1;
        }
        $goods['max_qty'] = isset($goods['max_qty']) ? (int)$goods['max_qty'] : 0;
        if ($goods['max_qty'] < 1) {
            $goods['max_qty'] = 0;
        }
        if ($goods['max_qty'] > 0 && $goods['max_qty'] < $goods['min_qty']) {
            $goods['max_qty'] = $goods['min_qty'];
        }
        if ((int)$quantity < 1) {
            $quantity = $goods['min_qty'];
        }

        // 获取必要数据
        $func = 'getOneGoodsForHome' . ucfirst($goods['type']);
        /**
         * 此处调用插件方法. 其他插件请参考独立卡密插件的实现
         * 1. 规格信息
         * 2. 发货类型
         */
        $goods = $func($goods);

//        d($goods);die;
        // 获取动态数据
        $dynamicInfo = $this->getOneGoodsDynamicForHome($goods, $user_id, $user_tier, $quantity, $selected_sku);
        $goods['price'] = $dynamicInfo['price'];
        $goods['unit_price'] = $dynamicInfo['unit_price'];
        $goods['cost_price'] = $dynamicInfo['cost_price'];
        $goods['discount_price'] = $dynamicInfo['discount_price'];
        $goods['stock'] = $dynamicInfo['stock'];
        $goods['sales'] = $dynamicInfo['sales'];
        $goods['selected_sku_type'] = $dynamicInfo['selected_sku_type'];
        $goods['skus']['option_value'] = $dynamicInfo['option_value'];
        $goods['config'] = json_decode($goods['config'], true);
        $goods['payment'] = empty($goods['payment']) ? [] : explode(',', $goods['payment']);

        $coupon_code = trim((string)$coupon_code);
        if ($coupon_code !== '') {
            $couponModel = new Coupon_Model();
            $apply = $couponModel->applyCouponCode($coupon_code, $goods, $goods['price']);
            if (!empty($apply['valid'])) {
                $goods['price_origin'] = $goods['price'];
                $goods['price'] = $apply['final_amount'];
                $goods['coupon'] = [
                    'id' => (int)($apply['coupon']['id'] ?? 0),
                    'code' => $apply['coupon']['code'] ?? $coupon_code,
                    'discount_amount' => $apply['discount_amount'],
                    'amount_before' => $apply['amount_before'],
                ];
            } else {
                $goods['coupon_error'] = $apply['msg'] ?? '优惠券不可用';
            }
        }

//        d($goods);die;

        return $goods;
    }


    

    /**
     * 后台管理 - 获取单个商品信息
     */
    public function getOneGoodsForAdmin($goods_id) {
        $sql = "select * from {$this->table} where id={$goods_id} limit 1";
        $goods = $this->db->once_fetch_array($sql);
        $sql = "select * from " . DB_PREFIX . "product_sku where goods_id={$goods_id}";
        $skus = $this->db->fetch_all($sql);

        $goods['skus'] = $skus;

        if ($goods) {
            $goods['payment'] = empty($goods['payment']) ? [] : explode(',', $goods['payment']);
            $goods['skus_json'] = json_encode($goods['skus']);
            $goods['config'] = json_decode($goods['config'], true);

            $goods['title'] = htmlspecialchars($goods['title']);
            $goods['content'] = htmlspecialchars($goods['content']);
            $goods['password'] = htmlspecialchars($goods['password']);
            $goods['template'] = !empty($goods['template']) ? htmlspecialchars(trim($goods['template'])) : 'page';
            return $goods;
        }
        return false;
    }

    /**
     * 删除商品
     */
    public function deleteGoods($goods_id) {
        $goods_id = (int)$goods_id;
        if ($goods_id <= 0) {
            return false;
        }

        $timestamp = time();
        $this->db->beginTransaction();
        try {
            // 软删除商品
            $this->db->query("UPDATE {$this->table} SET delete_time = {$timestamp} WHERE id = {$goods_id}");
            // 同步删除规格数据
            $this->db->query("DELETE FROM {$this->table_skus} WHERE goods_id = {$goods_id}");
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    

    /**
     * 前台商品列表页 - 获取商品价格
     * @goods 商品信息
     * @user_id 用户ID
     * @user_tier 用户等级
     * @tier_discount 用户等级折扣率
     */
    private function getGoodsPriceForHome($goods, $user_id, $user_tier, $tier_discount){
        
        $market_price = $goods['product_sku']['market_price'] / 100;
        if(empty($user_id)){
            $price = $goods['product_sku']['guest_price'] / 100;
        }else{
            $price = $goods['product_sku']['user_price'] / 100;
            if($user_tier > 0){
                $config = json_decode($goods['config'], true);
                $tier_price = empty($config['tier_price']) ? false : $config['tier_price'];
                if(empty($tier_price)){
                    $price = $tier_discount > 0 ? $price * $tier_discount : $price;
                }else{
                    if(empty($tier_price[$user_tier])){
                        $price = $tier_discount > 0 ? $price * $tier_discount : $price;
                    }else{
                        $price = reset($tier_price[$user_tier]);
                    }
                }
            }
        }
        return [
            'price' => $price,
            'market_price' => $market_price
        ];
    }

    /**
     * 前台商品列表页 - 获取全部商品
     * @sort_id 商品分类ID
     * @keyword 商品搜索关键词
     */
    public function getGoodsForHome($user_id, $user_tier, $sort_id, $keyword){
// echo 666;die;
        $where = '';
        if(!empty($keyword)){
            $where .= " and title like '%{$keyword}%'";
        }
        if(!empty($sort_id) && $sort_id != 0){
            $sortModel = new Sort_Model();
            $childrenSort = $sortModel->getChildren($sort_id);
            $childrenSortId = [$sort_id];
            foreach($childrenSort as $val){
                $childrenSortId[] = $val['sid'];
            }
            $childrenSortId = implode(',', $childrenSortId);
            $where .= " and sort_id in ({$childrenSortId})"; // 筛选分类下的商品
        }else{
            if(empty($keyword)){
                $where .= " and home='y'"; // 筛选首页展示的商品
            }
            
        }
        
        // echo $where;die;
        
        $sql = "SELECT
            id, title, cover, des, type, config, sort_id, index_top, sort_top, 0 AS station_id, link
        FROM {$this->table}
        WHERE is_on_shelf = 1 AND delete_time IS NULL {$where}
        ORDER BY sort_num DESC, index_top DESC, sort_top DESC, id asc";
        $goods = $this->db->fetch_all($sql);

        if($user_tier == 0){
            $tier_discount = 1;
        }else{
            $userTierModel = new User_Tier_Model();
            $userTierData = $userTierModel->getTierById(LEVEL);
            if(empty($userTierData) || empty($userTierData['discount'])){
                $tier_discount = 1;
            }else{
                $tier_discount = $userTierData['discount'] / 100;
            }
        }
        $skuModel = new Sku_Model();
        $skus = $skuModel->getAllMinSkus();

//        d($skus);die;

        $goods_ids = [];
        foreach ($goods as $val) {
            $goods_ids[] = $val['id'];
        }
        $skuTotals = [];
        if (!empty($goods_ids)) {
            $ids_str = implode(',', array_unique($goods_ids));
            $total_sql = "SELECT goods_id, SUM(stock) as stock_sum, SUM(sales) as sales_sum
                          FROM " . DB_PREFIX . "product_sku
                          WHERE goods_id IN ({$ids_str})
                          GROUP BY goods_id";
            $total_rows = $this->db->fetch_all($total_sql);
            foreach ($total_rows as $row) {
                $skuTotals[$row['goods_id']] = [
                    'stock' => $row['stock_sum'] ?? 0,
                    'sales' => $row['sales_sum'] ?? 0
                ];
            }
        }
        foreach($goods as $key => $val){
            $goods[$key]['url'] =  empty($val['link']) ? Url::goods($val['id']) : $val['link'];
            $func = "getIsAuto" . ucfirst($val['type']);
            if(!function_exists($func)){
                unset($goods[$key]);
                continue;
            }

            $goods[$key]['is_auto'] = $func($val['type']);
            $goods[$key]['stock'] = $skuTotals[$val['id']]['stock'] ?? 0;
            $goods[$key]['sales'] = $skuTotals[$val['id']]['sales'] ?? 0;
            foreach($skus as &$v){
                if($val['id'] == $v['goods_id']){
                    $goods[$key]['product_sku'] = $v;
                    unset($v);
                }

            }
            $goodsPriceData = $this->getGoodsPriceForHome($goods[$key], $user_id, $user_tier, $tier_discount);
            $goods[$key]['price'] = $goodsPriceData['price'];
            $goods[$key]['market_price'] = $goodsPriceData['market_price'];
        }
//        d($skus); d($goods); die;
        return array_values($goods);
    }


    /**
     * 传入选中的规格，返回所有可以匹配的规格数据
     */
    function findAllMatchedOptions($matchValue, $optionArray) {
        // 处理空值情况，直接返回空数组
        if (empty($matchValue) || empty($optionArray)) {
            return [];
        }
        
        // 将匹配值按 "-" 拆分成分段数组
        $matchSegments = explode('-', $matchValue);
        $matchedItems = []; // 存储最终匹配的元素
        
        // 遍历规格数组，逐个判断是否匹配
        foreach ($optionArray as $key => $item) {
            // 将当前键按 "-" 拆分成分段数组
            $keySegments = explode('-', $key);
            // 计算交集（匹配的分段数），只要交集数>0就判定为匹配
            $intersect = array_intersect($matchSegments, $keySegments);
            if (count($intersect) > 0) {
                $matchedItems[] = $item; // 直接存入原始元素
            }
        }
        
        return $matchedItems;
    }


    /**
     * 传入选中的规格，返回匹配度最高的规格数据
     */
    function findHighestMatchOption($matchValue, $optionArray) {
        // 将匹配值按 "-" 拆分成数组（如：1-3 → [1,3]）
        $matchSegments = explode('-', $matchValue);
        $highestMatchCount = 0; // 记录最高匹配数
        $highestMatchItem = null; // 记录最高匹配的元素
        
        // 遍历规格数组，逐个匹配
        foreach ($optionArray as $key => $item) {
            // 将当前键按 "-" 拆分成数组（如：1-5-3 → [1,5,3]）
            $keySegments = explode('-', $key);
            // 计算当前键与匹配值的重合分段数
            $matchCount = count(array_intersect($matchSegments, $keySegments));
            
            // 仅当匹配数>0，且高于当前最高匹配数时，更新结果
            if ($matchCount > 0 && $matchCount > $highestMatchCount) {
                $highestMatchCount = $matchCount;
                $highestMatchItem = $item;
            }
        }
        return $highestMatchItem;
    }

    /**
     * 商品上下架
     * @param int $goods_id 商品ID
     * @param int $is_on_shelf 上架=1，下架=0
     */
    public function setGoodsOnShelf($goods_id, $is_on_shelf) {
        $goods_id = (int)$goods_id;
        if ($goods_id <= 0) {
            return false;
        }
        $is_on_shelf = (int)$is_on_shelf === 1 ? 1 : 0;
        $result = $this->db->query("UPDATE {$this->table} SET is_on_shelf = {$is_on_shelf} WHERE id = {$goods_id}");
        return true;
    }
    

}
