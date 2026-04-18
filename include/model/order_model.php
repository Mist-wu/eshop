<?php
/**
 * @package ESHOP
 */

class Order_Model {

    private $db;
    private $Parsedown;
    private $table;
    private $db_prefix;
    private static $expiredUnpaidCleaned = false;
    private static $expiredUnpaidCleanupChecked = false;
    private static $orderPaymentReferenceSchemaChecked = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'order';
        $this->db_prefix = DB_PREFIX;
        $this->Parsedown = new Parsedown();
        $this->Parsedown->setBreaksEnabled(true); //automatic line wrapping
        $this->ensureOrderPaymentReferenceSchema();
    }

    protected function ensureOrderPaymentReferenceSchema() {
        if (self::$orderPaymentReferenceSchemaChecked) {
            return;
        }
        self::$orderPaymentReferenceSchemaChecked = true;

        $alterClauses = [];
        if (!$this->orderTableHasColumn('trade_no')) {
            $alterClauses[] = "ADD COLUMN `trade_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '易付通平台订单号'";
        }
        if (!$this->orderTableHasColumn('api_trade_no')) {
            $alterClauses[] = "ADD COLUMN `api_trade_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '微信/支付宝渠道订单号'";
        }
        if (!$this->orderTableHasIndex('idx_order_trade_no')) {
            $alterClauses[] = "ADD INDEX `idx_order_trade_no`(`trade_no`)";
        }
        if (!$this->orderTableHasIndex('idx_order_api_trade_no')) {
            $alterClauses[] = "ADD INDEX `idx_order_api_trade_no`(`api_trade_no`)";
        }

        if (empty($alterClauses)) {
            return;
        }

        $this->db->query(
            'ALTER TABLE ' . $this->quoteIdentifier($this->table) . "\n            " . implode(",\n            ", $alterClauses)
        );
        Log::info('订单表已自动补齐支付单号字段迁移');
    }

    private function orderTableHasColumn($columnName) {
        if (!defined('DB_NAME')) {
            return true;
        }

        $tableName = $this->db->escape_string($this->table);
        $schemaName = $this->db->escape_string(DB_NAME);
        $columnName = $this->db->escape_string((string)$columnName);

        $sql = "SELECT 1 AS exists_flag
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '{$schemaName}'
                AND TABLE_NAME = '{$tableName}'
                AND COLUMN_NAME = '{$columnName}'
                LIMIT 1";

        return !empty($this->db->once_fetch_array($sql));
    }

    private function orderTableHasIndex($indexName) {
        if (!defined('DB_NAME')) {
            return true;
        }

        $tableName = $this->db->escape_string($this->table);
        $schemaName = $this->db->escape_string(DB_NAME);
        $indexName = $this->db->escape_string((string)$indexName);

        $sql = "SELECT 1 AS exists_flag
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = '{$schemaName}'
                AND TABLE_NAME = '{$tableName}'
                AND INDEX_NAME = '{$indexName}'
                LIMIT 1";

        return !empty($this->db->once_fetch_array($sql));
    }

    private function quoteIdentifier($identifier) {
        return '`' . str_replace('`', '``', (string)$identifier) . '`';
    }

    /**
     * 自动清理超时未支付订单
     */
    public function maybeCleanupExpiredUnpaidOrders($force = false) {
        if (self::$expiredUnpaidCleanupChecked && !$force) {
            return 0;
        }
        self::$expiredUnpaidCleanupChecked = true;

        if (!$this->claimExpiredOrderCleanupSlot($force)) {
            return 0;
        }

        return $this->cleanupExpiredUnpaidOrders(true);
    }

    public function cleanupExpiredUnpaidOrders($force = false) {
        if (self::$expiredUnpaidCleaned && !$force) {
            return 0;
        }
        self::$expiredUnpaidCleaned = true;

        $timestamp = time();
        $rows = $this->db->fetch_all(
            "SELECT id, out_trade_no, pay_plugin FROM {$this->table}
             WHERE delete_time IS NULL
             AND status = 0
             AND (pay_time IS NULL OR pay_time = 0 OR pay_time = '')
             AND expire_time IS NOT NULL
             AND expire_time > 0
             AND expire_time <= {$timestamp}
             ORDER BY expire_time ASC
             LIMIT 20"
        );

        if (empty($rows)) {
            return 0;
        }

        $orderIds = [];
        $recovered = 0;
        foreach ($rows as $row) {
            if ($this->tryRecoverExpiredPaidOrder($row)) {
                $recovered++;
                continue;
            }
            $orderIds[] = (int)$row['id'];
        }
        $orderIds = array_filter($orderIds);
        if (!empty($orderIds)) {
            $idsStr = implode(',', $orderIds);
            $this->db->beginTransaction();
            $this->releaseCouponUsage($orderIds);
            $this->db->query("UPDATE {$this->table} SET status = -3, update_time = {$timestamp} WHERE id IN ({$idsStr})");
            $this->db->query("UPDATE {$this->db_prefix}order_list SET status = -3 WHERE order_id IN ({$idsStr}) AND status = 0");
            $this->db->commit();
        }

        return count($orderIds) + $recovered;
    }

    private function claimExpiredOrderCleanupSlot($force = false) {
        if ($force) {
            return true;
        }

        $interval = (int)emEnv('EM_ORDER_CLEANUP_INTERVAL', 300);
        if ($interval < 30) {
            $interval = 30;
        }

        $markerPath = $this->getExpiredOrderCleanupMarkerPath();
        isFolder(dirname($markerPath), true);
        $handle = @fopen($markerPath, 'c+');
        if ($handle === false) {
            return true;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return true;
        }

        $content = stream_get_contents($handle);
        $lastRun = $this->parseCleanupMarkerTimestamp($content);
        $now = time();
        if ($lastRun > 0 && ($now - $lastRun) < $interval) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, "<?php exit;//" . $now);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }

    private function getExpiredOrderCleanupMarkerPath() {
        return EM_ROOT . '/content/cache/order_cleanup_runtime.php';
    }

    private function parseCleanupMarkerTimestamp($content) {
        $content = trim(str_replace('<?php exit;//', '', (string)$content));
        return ctype_digit($content) ? (int)$content : 0;
    }

    private function tryRecoverExpiredPaidOrder($orderRow) {
        $orderRow = is_array($orderRow) ? $orderRow : [];
        if (empty($orderRow['out_trade_no']) || empty($orderRow['pay_plugin'])) {
            return false;
        }
        if (strpos((string)$orderRow['pay_plugin'], 'yifut_') !== 0 || !function_exists('yifutQueryOrder')) {
            return false;
        }

        $remoteOrder = yifutQueryOrder(['out_trade_no' => (string)$orderRow['out_trade_no']]);
        if (empty($remoteOrder)) {
            if (function_exists('yifutCloseOrder')) {
                yifutCloseOrder(['out_trade_no' => (string)$orderRow['out_trade_no']]);
            }
            return false;
        }

        if ((int)($remoteOrder['status'] ?? 0) !== 1) {
            if (function_exists('yifutCloseOrder')) {
                yifutCloseOrder([
                    'trade_no' => (string)($remoteOrder['trade_no'] ?? ''),
                    'out_trade_no' => (string)$orderRow['out_trade_no'],
                ]);
            }
            return false;
        }

        $paymentData = yifutBuildPaymentSnapshotFromRemoteOrder(
            $remoteOrder,
            (string)($remoteOrder['type'] ?? ''),
            yifutPaymentLabel((string)($remoteOrder['type'] ?? ''))
        );
        $result = $this->processConfirmedPayment((string)$orderRow['out_trade_no'], $paymentData, ['source' => 'expire_reconcile']);
        return !empty($result['ok']);
    }

    private function normalizeOrderIds($orderIds) {
        if (!is_array($orderIds)) {
            $orderIds = [$orderIds];
        }

        $ids = [];
        foreach ($orderIds as $orderId) {
            $orderId = (int)$orderId;
            if ($orderId > 0) {
                $ids[] = $orderId;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function buildUpdateAssignments($data) {
        $items = [];
        foreach ((array)$data as $key => $var) {
            if ($var === null) {
                $items[] = "{$key}=NULL";
            } elseif (is_bool($var)) {
                $items[] = "{$key}=" . ($var ? 1 : 0);
            } elseif (is_int($var) || is_float($var)) {
                $items[] = "{$key}={$var}";
            } else {
                $var = $this->db->escape_string((string)$var);
                $items[] = "{$key}='{$var}'";
            }
        }

        return implode(',', $items);
    }

    public function markOrderPaid($order_id, $data = [], $options = []) {
        $order_id = (int)$order_id;
        if ($order_id <= 0) {
            return false;
        }

        if (!isset($data['pay_status'])) {
            $data['pay_status'] = 1;
        }
        if (!isset($data['update_time'])) {
            $data['update_time'] = time();
        }

        $upStr = $this->buildUpdateAssignments($data);
        if ($upStr === '') {
            return false;
        }

        $strictPendingOnly = empty($options['allow_any_unpaid']);
        $where = "id = {$order_id}";
        if ($strictPendingOnly) {
            $where .= "
                AND delete_time IS NULL
                AND status = 0
                AND (pay_time IS NULL OR pay_time = 0 OR pay_time = '')
                AND (pay_status IS NULL OR pay_status <> 1)";
        } else {
            $where .= " AND (pay_status IS NULL OR pay_status <> 1)";
        }

        $sql = "UPDATE {$this->table}
                SET {$upStr}
                WHERE {$where}";
        $this->db->query($sql);
        return $this->db->affected_rows() > 0;
    }

    public function confirmCouponUsage($order_id) {
        $order_id = (int)$order_id;
        if ($order_id <= 0) {
            return 0;
        }

        $rows = $this->db->fetch_all(
            "SELECT coupon_id, COUNT(*) AS total
             FROM {$this->db_prefix}coupon_usage
             WHERE order_id = {$order_id} AND status = 0
             GROUP BY coupon_id"
        );
        if (empty($rows)) {
            return 0;
        }

        $timestamp = time();
        $this->db->query("UPDATE {$this->db_prefix}coupon_usage SET status = 1, update_time = {$timestamp} WHERE order_id = {$order_id} AND status = 0");
        $confirmed = (int)$this->db->affected_rows();
        if ($confirmed < 1) {
            return 0;
        }

        foreach ($rows as $row) {
            $couponId = (int)($row['coupon_id'] ?? 0);
            $total = (int)($row['total'] ?? 0);
            if ($couponId <= 0 || $total <= 0) {
                continue;
            }
            $this->db->query(
                "UPDATE {$this->db_prefix}coupon
                 SET used_times = used_times + {$total},
                     update_time = {$timestamp}
                 WHERE id = {$couponId}"
            );
        }

        return $confirmed;
    }

    public function releaseCouponUsage($orderIds) {
        $orderIds = $this->normalizeOrderIds($orderIds);
        if (empty($orderIds)) {
            return 0;
        }

        $idsStr = implode(',', $orderIds);
        $timestamp = time();

        $this->db->query("UPDATE {$this->db_prefix}coupon_usage SET status = -1, update_time = {$timestamp} WHERE order_id IN ({$idsStr}) AND status = 0");
        return (int)$this->db->affected_rows();
    }

    public function cancelPendingOrder($order_id) {
        $order_id = (int)$order_id;
        if ($order_id <= 0) {
            return false;
        }

        $order = $this->getOrderInfoId($order_id, true);
        if (empty($order)) {
            return false;
        }

        $timestamp = time();
        $this->db->beginTransaction();

        $this->db->query(
            "UPDATE {$this->table}
             SET status = -2, update_time = {$timestamp}
             WHERE id = {$order_id}
             AND delete_time IS NULL
             AND status = 0
             AND (pay_time IS NULL OR pay_time = 0 OR pay_time = '')
             AND (pay_status IS NULL OR pay_status = 0)"
        );

        if ((int)$this->db->affected_rows() < 1) {
            $this->db->rollback();
            return false;
        }

        $this->db->query("UPDATE {$this->db_prefix}order_list SET status = -2 WHERE order_id = {$order_id}");
        $this->releaseCouponUsage($order_id);
        $this->db->commit();

        if (strpos((string)($order['pay_plugin'] ?? ''), 'yifut_') === 0 && function_exists('yifutCloseOrder')) {
            $lookup = [];
            if (!empty($order['trade_no'])) {
                $lookup['trade_no'] = (string)$order['trade_no'];
            } else {
                $lookup['out_trade_no'] = (string)$order['out_trade_no'];
            }
            yifutCloseOrder($lookup);
        }

        return true;
    }
    
    /**
     * 创建订单
     * @return array|string 成功返回数组，失败返回错误码字符串
     */
    public function createOrder($post, $goods_id, $quantity, $user_id, $user_tier, $config, $visitor_input, $sku_ids){
        $payment_plugin = $post['payment_plugin'];
        $payment_title = $post['payment_name'];
        $coupon_code = trim((string)($post['coupon_code'] ?? ''));

        if (empty($payment_plugin)) {
            return ['code' => '400', 'msg' => '请选择支付方式'];
        }
        if ($payment_plugin === 'balance') {
            return ['code' => '400', 'msg' => '余额支付已下线，请选择支付宝或其他外部支付方式'];
        }

        $goodsModel = new Goods_Model();
        
        $goods = $goodsModel->getOneGoodsForHome($goods_id, $user_id, $user_tier, $sku_ids, $quantity);
        if($goods['selected_sku_type'] == 0){
            return ['code' => '400', 'msg' => '请选择规格'];
        }
        if($goods['selected_sku_type'] == 1){
            return ['code' => '400', 'msg' => '请完整选择规格'];
        }
        $minQty = isset($goods['min_qty']) ? (int)$goods['min_qty'] : 1;
        if ($minQty < 1) {
            $minQty = 1;
        }
        $maxQty = isset($goods['max_qty']) ? (int)$goods['max_qty'] : 0;
        if ($maxQty < 1) {
            $maxQty = 0;
        }
        if ($maxQty > 0 && $maxQty < $minQty) {
            $maxQty = $minQty;
        }
        if ($quantity < $minQty) {
            return ['code' => '400', 'msg' => '最小购买数量为' . $minQty];
        }
        if ($maxQty > 0 && $quantity > $maxQty) {
            return ['code' => '400', 'msg' => '最大购买数量为' . $maxQty];
        }
        
        // 验证库存
        if($quantity > $goods['stock']){
            return ['code' => '400', 'msg' => '库存不足'];
        }
        // 验证附加选项
        if(!empty($goods['config']['input'])){
            foreach($goods['config']['input'] as $val){
                if(empty($config['input'][$val['name_en']])){
                    return['code' => 400, 'msg' => '请填写' . $val['name']];
                }
            }
        }
        
        // 验证对接站点库存及余额是否充足
        if($goods['group_id'] == -1){
            $func = "remoteGoodsVerify" . ucfirst($goods['type']);
            if(function_exists($func)){
                $sku_str = empty($sku_ids) ? '0' : implode('-', $sku_ids);
                $remote_res = $func($goods, $quantity, $sku_str);
                if(is_array($remote_res) && isset($remote_res['code']) && (int)$remote_res['code'] !== 0){
                    return $remote_res;
                }
            }else{
                return ['code' => 400, 'msg' => '对接验证函数不存在'];
            }
        }
        // 构建规格描述
        $attr_spec = '';
        foreach($goods['skus']['option_name'] as $val){
            foreach($val['sku_values'] as $v){
                foreach($sku_ids as $sku_id){
                    if($v['option_id'] == $sku_id){
                        $attr_spec .= $val['title'] . '：' . $v['option_name'] . '；';
                    }
                }
            }
        }
        // 构建附加选项
        $_config['input'] = [];
        if(!empty($config['input'])){
            foreach($config['input'] as $key => $val){
                foreach($goods['config']['input'] as $v){
                    if($key == $v['name_en']){
                        $_config['input'][$v['name']] = $val;
                    }
                }
            }
        }

        $out_trade_no = date('YmdHis', TIMESTAMP) . mt_rand(1000, 9999);

        // 开启事务 - 所有数据库操作都在事务中
        try {
            $this->db->beginTransaction();

            $origin_price = $goods['price'];
            $final_price = $origin_price;
            $coupon_id = 0;
            $coupon_code_used = '';
            $coupon_discount = '0.00';

            if ($coupon_code !== '') {
                $couponModel = new Coupon_Model();
                $coupon_row = $couponModel->getByCode($coupon_code, true);
                $apply = $couponModel->applyCouponRow($coupon_row, $goods, $origin_price);
                if (empty($apply['valid'])) {
                    throw new Exception($apply['msg'] ?? '优惠券不可用');
                }
                $coupon_id = (int)($coupon_row['id'] ?? 0);
                $coupon_code_used = $coupon_row['code'] ?? $coupon_code;
                $coupon_discount = $apply['discount_amount'];
                $final_price = $apply['final_amount'];
                $goods['price'] = $final_price;
                if ($quantity > 0) {
                    $goods['unit_price'] = emBcDiv($final_price, (string)$quantity, 2);
                }
            }

            $origin_amount = (int)round(((float)$origin_price) * 100);
            $final_amount = (int)round(((float)$goods['price']) * 100);
            $coupon_amount = $coupon_id > 0 ? (int)round(((float)$coupon_discount) * 100) : 0;
            if ($coupon_id > 0 && $coupon_amount <= 0 && $origin_amount > $final_amount) {
                $coupon_amount = $origin_amount - $final_amount;
            }

            $expireSeconds = (int)emEnv('EM_ORDER_EXPIRE_SECONDS', 1800);
            if ($expireSeconds < 300) {
                $expireSeconds = 300;
            }

            // 创建主订单
            $insert_order = [
                'client_ip' => getClientIP(),
                'user_id' => $user_id,
                'out_trade_no' => $out_trade_no,
                'amount' => $final_amount,
                'origin_amount' => $origin_amount,
                'coupon_amount' => $coupon_amount,
                'coupon_id' => $coupon_id,
                'coupon_code' => $coupon_code_used,
                'payment' => $payment_title,
                'pay_name' => $payment_title,
                'pay_plugin' => $payment_plugin,
                'expire_time' => TIMESTAMP + $expireSeconds,
                'create_time' => TIMESTAMP,
                'contact' => $visitor_input['contact'] ?? '',
                'pwd' => $visitor_input['password'] ?? ''
            ];
            $this->db->add('order', $insert_order);
            $order_id = $this->db->insert_id();

            // 创建子订单
            $insert_child_order = [
                'order_id' => $order_id,
                'goods_id' => $goods_id,
                'sku' => empty($sku_ids) ? '0' : implode('-', $sku_ids),
                'attr_spec' => $attr_spec,
                'attach_user' => json_encode($_config['input'], JSON_UNESCAPED_UNICODE),
                'quantity' => $quantity,
                'unit_price' => (int)round(((float)$goods['unit_price']) * 100),
                'price' => $final_amount,
                'cost_price' => $goods['cost_price']
            ];
            $this->db->add('order_list', $insert_child_order);
            $order_list_id = $this->db->insert_id();

            if ($coupon_id > 0) {
                $this->db->add('coupon_usage', [
                    'coupon_id' => $coupon_id,
                    'coupon_code' => $coupon_code_used,
                    'user_id' => (int)$user_id,
                    'order_id' => $order_id,
                    'order_list_id' => $order_list_id,
                    'amount_before' => $origin_amount,
                    'discount_amount' => $coupon_amount,
                    'status' => 0,
                    'create_time' => TIMESTAMP,
                    'update_time' => TIMESTAMP,
                ]);
            }

            $this->db->commit();

            Log::info('已创建订单：' . $out_trade_no);

            // 创建订单后的挂载点
            doAction('order_created', $order_id);


            return ['code' => 0, 'out_trade_no' => $out_trade_no];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['code' => 'error', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 获取sku的规格属性
     */
    public function getSpecification($specification_ids){
        if (empty($specification_ids)) {
            return false;
        }
        $data = [];
        foreach($specification_ids as $val){
            $sql = "SELECT * FROM " . DB_PREFIX . "specification where id={$val}";
            $res = $this->db->query($sql);
            $row = $this->db->fetch_array($res);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 获取sku的规格属性值
     */
    public function getSpecificationValue($specification) {
        if (empty($specification)) {
            return false;
        }
        $specification = explode('-', $specification);
        $data = [];
        foreach($specification as $val){
            $sql = "SELECT * FROM " . DB_PREFIX . "spec_option where id={$val}";
            $res = $this->db->query($sql);
            $row = $this->db->fetch_array($res);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 删除订单
     */
    function delete($id){
        $timestamp = time();
        $sql = "UPDATE " . DB_PREFIX . "order set delete_time={$timestamp} where id=$id";
        $this->db->query($sql);
    }

    /**
     * 通过订单号获取主订单信息
     */
    public function getOrderInfoRaw($out_trade_no, $includeDeleted = false) {
        $out_trade_no = trim((string)$out_trade_no);
        if ($out_trade_no === '') {
            return [];
        }

        $row = $this->fetchOrderInfoRaw($out_trade_no, $includeDeleted);
        if ($this->resolveExpiredPendingOrder($row)) {
            $row = $this->fetchOrderInfoRaw($out_trade_no, $includeDeleted);
        }

        return $row;
    }

    private function fetchOrderInfoRaw($out_trade_no, $includeDeleted = false) {
        $out_trade_no = $this->db->escape_string($out_trade_no);
        $sql = "SELECT * FROM {$this->table} WHERE out_trade_no='{$out_trade_no}'";
        if (!$includeDeleted) {
            $sql .= " AND delete_time IS NULL";
        }
        $sql .= " LIMIT 1";
        $res = $this->db->query($sql);
        $row = $this->db->fetch_array($res);
        return empty($row) ? [] : $row;
    }

    /**
     * 通过订单号获取主订单信息
     */
    public function getOrderInfo($out_trade_no, $includeDeleted = false) {
        $row = $this->getOrderInfoRaw($out_trade_no, $includeDeleted);
        if (empty($row)) {
            return [];
        }
        $row['amount'] /= 100;
        return $row;
    }

    /**
     * 通过ID获取主订单信息
     */
    public function getOrderInfoId($id, $includeDeleted = false) {
        $id = (int)$id;
        if ($id <= 0) {
            return [];
        }

        $row = $this->fetchOrderInfoById($id, $includeDeleted);
        if ($this->resolveExpiredPendingOrder($row)) {
            $row = $this->fetchOrderInfoById($id, $includeDeleted);
        }

        return $row;
    }

    private function fetchOrderInfoById($id, $includeDeleted = false) {
        $id = (int)$id;
        $sql = "SELECT * FROM {$this->table} WHERE id={$id}";
        if (!$includeDeleted) {
            $sql .= " AND delete_time IS NULL";
        }
        $sql .= " LIMIT 1";
        $res = $this->db->query($sql);
        $row = $this->db->fetch_array($res);
        return empty($row) ? [] : $row;
    }

    public function getOrderByReference($reference, $includeDeleted = false) {
        $reference = $this->db->escape_string(trim((string)$reference));
        if ($reference === '') {
            return [];
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE (out_trade_no = '{$reference}'
                    OR up_no = '{$reference}'
                    OR trade_no = '{$reference}'
                    OR api_trade_no = '{$reference}')";
        if (!$includeDeleted) {
            $sql .= " AND delete_time IS NULL";
        }
        $sql .= " LIMIT 1";

        $row = $this->db->once_fetch_array($sql) ?: [];
        if ($this->resolveExpiredPendingOrder($row)) {
            $row = $this->db->once_fetch_array($sql) ?: [];
        }

        return $row;
    }

    private function resolveExpiredPendingOrder($orderRow) {
        $orderRow = is_array($orderRow) ? $orderRow : [];
        if (!$this->isExpiredPendingUnpaidOrder($orderRow)) {
            return false;
        }

        if ($this->tryRecoverExpiredPaidOrder($orderRow)) {
            return true;
        }

        $orderId = (int)($orderRow['id'] ?? 0);
        if ($orderId <= 0) {
            return false;
        }

        $timestamp = time();
        $this->db->beginTransaction();
        $this->db->query(
            "UPDATE {$this->table}
             SET status = -3, update_time = {$timestamp}
             WHERE id = {$orderId}
             AND delete_time IS NULL
             AND status = 0
             AND (pay_time IS NULL OR pay_time = 0 OR pay_time = '')
             AND (pay_status IS NULL OR pay_status <> 1)
             AND expire_time IS NOT NULL
             AND expire_time > 0
             AND expire_time <= {$timestamp}"
        );
        $affected = (int)$this->db->affected_rows();
        if ($affected > 0) {
            $this->releaseCouponUsage($orderId);
            $this->db->query("UPDATE {$this->db_prefix}order_list SET status = -3 WHERE order_id = {$orderId} AND status = 0");
        }
        $this->db->commit();

        return $affected > 0;
    }

    private function isExpiredPendingUnpaidOrder($orderRow) {
        $orderRow = is_array($orderRow) ? $orderRow : [];
        if (empty($orderRow)) {
            return false;
        }

        return empty($orderRow['delete_time'])
            && (int)($orderRow['status'] ?? 0) === 0
            && (int)($orderRow['pay_status'] ?? 0) !== 1
            && !empty($orderRow['expire_time'])
            && (int)$orderRow['expire_time'] > 0
            && (int)$orderRow['expire_time'] <= time();
    }
    /**
     * 获取子订单信息
     */
    public function getOrderList($order_id) {
        $prefix = DB_PREFIX;
        $sql = <<<sql
                    SELECT 
                        ol.*, g.type 
                    FROM 
                        {$prefix}order_list as ol
                    LEFT JOIN
                        {$prefix}goods as g ON ol.goods_id=g.id
                    WHERE 
                        ol.order_id={$order_id}
sql;

        $res = $this->db->query($sql);
        $data = [];
        while ($row = $this->db->fetch_array($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public function processConfirmedPayment($out_trade_no, $paymentData = [], $options = []) {
        $out_trade_no = trim((string)$out_trade_no);
        if ($out_trade_no === '') {
            return ['ok' => false, 'msg' => '订单号不能为空'];
        }

        $order = $this->getOrderInfoRaw($out_trade_no, true);
        if (empty($order)) {
            Log::warning('支付成功处理未找到本地订单：' . $out_trade_no);
            return ['ok' => false, 'msg' => '订单不存在'];
        }

        $timestamp = time();
        $paymentData = is_array($paymentData) ? $paymentData : [];
        $payTime = isset($paymentData['pay_time']) ? (int)$paymentData['pay_time'] : 0;
        if ($payTime <= 0) {
            $payTime = $timestamp;
        }

        $orderUpdate = [
            'pay_status' => 1,
            'pay_time' => $payTime,
            'payment' => !empty($paymentData['payment']) ? (string)$paymentData['payment'] : (string)($order['payment'] ?? '在线支付'),
            'trade_no' => $paymentData['trade_no'] ?? ($order['trade_no'] ?? null),
            'api_trade_no' => $paymentData['api_trade_no'] ?? ($order['api_trade_no'] ?? null),
            'up_no' => $paymentData['up_no'] ?? ($paymentData['api_trade_no'] ?? ($paymentData['trade_no'] ?? ($order['up_no'] ?? null))),
            'delete_time' => null,
            'update_time' => $timestamp,
        ];
        if ($this->shouldRecoverOrderStateAfterConfirmedPayment($order)) {
            $orderUpdate['status'] = 0;
        }

        $wasUpdated = false;
        if ((int)($order['pay_status'] ?? 0) !== 1) {
            $wasUpdated = $this->markOrderPaid((int)$order['id'], $orderUpdate, ['allow_any_unpaid' => true]);
            if ($wasUpdated) {
                $this->confirmCouponUsage((int)$order['id']);
            }
        } else {
            $patch = [
                'delete_time' => null,
                'update_time' => $timestamp,
            ];
            foreach (['pay_time', 'payment', 'trade_no', 'api_trade_no', 'up_no'] as $field) {
                if (isset($orderUpdate[$field]) && $orderUpdate[$field] !== null && $orderUpdate[$field] !== '') {
                    $patch[$field] = $orderUpdate[$field];
                }
            }
            if ($this->shouldRecoverOrderStateAfterConfirmedPayment($order)) {
                $patch['status'] = 0;
            }
            $this->updateOrderInfoById((int)$order['id'], $patch);
        }

        $currentOrder = $this->getOrderInfoId((int)$order['id'], true);
        if (empty($currentOrder)) {
            return ['ok' => false, 'msg' => '订单更新失败'];
        }

        $deliverResult = ['code' => 0, 'msg' => 'skipped'];
        if ($this->shouldDeliverAfterConfirmedPayment($currentOrder)) {
            $deliverResult = $this->deliver((int)$currentOrder['id']);
            if (isset($deliverResult['code']) && (int)$deliverResult['code'] !== 0 && (int)$deliverResult['code'] !== 200) {
                Log::warning('支付成功后发货失败：' . $out_trade_no . ' - ' . ($deliverResult['msg'] ?? 'unknown'));
                return ['ok' => false, 'msg' => $deliverResult['msg'] ?? '发货失败', 'order' => $currentOrder, 'deliver' => $deliverResult];
            }
        }

        return [
            'ok' => true,
            'order' => $this->getOrderInfoId((int)$order['id'], true),
            'updated' => $wasUpdated,
            'deliver' => $deliverResult,
            'source' => $options['source'] ?? '',
        ];
    }

    private function shouldRecoverOrderStateAfterConfirmedPayment($order) {
        $order = is_array($order) ? $order : [];
        $status = (int)($order['status'] ?? 0);
        if ($status === -1) {
            return false;
        }

        return !empty($order['delete_time']) || in_array($status, [-2, -3], true);
    }

    private function shouldDeliverAfterConfirmedPayment($order) {
        $order = is_array($order) ? $order : [];
        if ((int)($order['pay_status'] ?? 0) !== 1) {
            return false;
        }

        $status = (int)($order['status'] ?? 0);
        return in_array($status, [0, 1], true);
    }

    public function reconcileOrderPayment($reference) {
        $order = $this->getOrderByReference($reference, true);
        if (empty($order)) {
            return ['ok' => false, 'msg' => '订单不存在'];
        }

        if (strpos((string)($order['pay_plugin'] ?? ''), 'yifut_') !== 0 || !function_exists('yifutQueryOrder')) {
            return ['ok' => false, 'msg' => '当前订单不支持主动查单'];
        }

        $lookup = [];
        if (!empty($order['trade_no'])) {
            $lookup['trade_no'] = (string)$order['trade_no'];
        } else {
            $lookup['out_trade_no'] = (string)$order['out_trade_no'];
        }

        $remoteOrder = yifutQueryOrder($lookup);
        if (empty($remoteOrder)) {
            return ['ok' => false, 'msg' => '上游查单失败或订单不存在'];
        }

        $status = (int)($remoteOrder['status'] ?? -1);
        if ($status !== 1) {
            if (!empty($order['expire_time']) && (int)$order['expire_time'] > 0 && (int)$order['expire_time'] <= time() && function_exists('yifutCloseOrder')) {
                yifutCloseOrder($lookup);
            }
            return ['ok' => false, 'msg' => '上游订单尚未支付', 'remote' => $remoteOrder];
        }

        $paymentData = yifutBuildPaymentSnapshotFromRemoteOrder(
            $remoteOrder,
            (string)($remoteOrder['type'] ?? ''),
            !empty($order['payment']) ? (string)$order['payment'] : ''
        );

        return $this->processConfirmedPayment((string)$order['out_trade_no'], $paymentData, ['source' => 'manual_query']);
    }

    public function reconcileRecentYifutPaidOrders($limit = 50, $offset = 0) {
        if (!function_exists('yifutListMerchantOrders')) {
            return ['ok' => false, 'msg' => '当前环境不支持对账接口'];
        }

        $remoteResult = yifutListMerchantOrders($offset, $limit, 1);
        if (empty($remoteResult) || !isset($remoteResult['data']) || !is_array($remoteResult['data'])) {
            return ['ok' => false, 'msg' => '最近支付订单拉取失败'];
        }

        $summary = [
            'ok' => true,
            'checked' => 0,
            'fixed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        foreach ($remoteResult['data'] as $remoteOrder) {
            $summary['checked']++;
            $outTradeNo = trim((string)($remoteOrder['out_trade_no'] ?? ''));
            if ($outTradeNo === '') {
                $summary['failed']++;
                $summary['messages'][] = '存在缺少商户订单号的上游订单';
                continue;
            }

            $localOrder = $this->getOrderInfoRaw($outTradeNo, true);
            if (empty($localOrder)) {
                $summary['failed']++;
                $summary['messages'][] = '本地不存在：' . $outTradeNo;
                continue;
            }

            $needsFix = (int)($localOrder['pay_status'] ?? 0) !== 1
                || !empty($localOrder['delete_time'])
                || empty($localOrder['trade_no'])
                || empty($localOrder['api_trade_no']);

            if (!$needsFix) {
                $summary['skipped']++;
                continue;
            }

            $paymentData = yifutBuildPaymentSnapshotFromRemoteOrder(
                $remoteOrder,
                (string)($remoteOrder['type'] ?? ''),
                !empty($localOrder['payment']) ? (string)$localOrder['payment'] : ''
            );
            $result = $this->processConfirmedPayment($outTradeNo, $paymentData, ['source' => 'recent_reconcile']);
            if (!empty($result['ok'])) {
                $summary['fixed']++;
            } else {
                $summary['failed']++;
                $summary['messages'][] = $outTradeNo . '：' . ($result['msg'] ?? '修复失败');
            }
        }

        return $summary;
    }

    /**
     * 支付完成后，给订单发货
     * @param int $order_id 订单ID
     * @return array 发货结果
     */
    public function deliver($order_id){
        $order_list = $this->getOrderList($order_id);
        $goodsModel = new Goods_Model();
        $order = $this->getOrderInfoId($order_id);
        $userModel = new User_Model();
        if($order['user_id'] == 0){
            $user_tier = 0;
        }else{
            $user = $userModel->getOneUser($order['user_id']);
            $user_tier = $user['level'];
        }


        try {
            $this->db->beginTransaction();

            $deliverResult = ['code' => 0, 'content' => []];

            foreach($order_list as $child_order){
                $goods = $goodsModel->getOneGoodsForHome(
                    $child_order['goods_id'], $order['user_id'], $user_tier, explode('-', $child_order['sku']), $child_order['quantity']
                );

                // 调用发货方法
                $func = "goodsDeliver" . ucfirst($goods['type']);
                $deliverResult = $func($goods, $order, $child_order);
                // d($deliverResult);
                if(isset($deliverResult['code']) && $deliverResult['code'] != 0 && $deliverResult['code'] != 200){
                    throw new Exception($deliverResult['msg']);
                }
            }

            $this->db->commit();

            
            // 增加用户消费额
            $userModel->expendInc($order['user_id'], $order['amount'] / 100);

            // 发货完成钩子
            $this->deliverComplete($goods, $order, $order_list, $deliverResult);

            return $deliverResult;

        } catch (Exception $e) {
            $this->db->rollback();
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 发货流程结束后的挂载
     */
    public function deliverComplete($goods, $order, $child_order, $deliverResult){
        $plugin_goods_name = $goods['title'];
        $plugin_goods_type = $goods['type'];
        foreach($child_order as $val){
            $plugin_sku = $val['attr_spec'];
            $plugin_attach_user = json_decode($val['attach_user']);
            $plugin_quantity = $val['quantity'];
            $plugin_goods_price = $val['unit_price'] / 100;
        }
        $deliver_content = implode("/r/n", $deliverResult['content']);

        $user = [];
        if($order['user_id'] > 0){
            $userModel = new User_Model();
            $user = $userModel->getOneUser($order['user_id']);
        }



        $plugin_data = [
            'out_trade_no' => $order['out_trade_no'], // 订单号
            'client_ip' => $order['client_ip'], // 下单IP
            'order_amount' => $order['amount'] / 100, // 订单总金额
            'create_time' => date('Y-m-d H:i:s', $order['create_time']), // 下单时间
            'payment' => $order['payment'], // 支付方式
            'pay_time' => date('Y-m-d H:i:s', $order['pay_time']), // 支付时间
            'contact' => $order['contact'], // 游客联系方式
            'pwd' => $order['pwd'], // 游客订单密码
            'goods_name' => $plugin_goods_name, // 商品名称
            'goods_type' => $plugin_goods_type, // 商品类型
            'sku' => $plugin_sku, // sku
            'attach_user' => $plugin_attach_user, // 附加选项
            'quantity' => $plugin_quantity, // 购买数量
            'goods_unit_price' => $plugin_goods_price, // 商品单价
            'deliver_content' => $deliver_content, // 发乎内容
            'order' => $order,
            'goods' => $goods,
            'child_order' => $child_order,
            'user' => $user,
            'deliverResult' => $deliverResult
        ];
        doAction('deliver_after', $plugin_data);
    }
    

    /**
     * 更新订单的支付状态
     */
	public function updateOrderPayStatus($order_id, $data){
		return $this->markOrderPaid($order_id, $data);
	}

    public function updateOrderInfoById($order_id, $data) {
        $order_id = (int)$order_id;
        if ($order_id <= 0) {
            return false;
        }

        $upStr = $this->buildUpdateAssignments($data);
        if ($upStr === '') {
            return false;
        }

        return $this->db->query("UPDATE {$this->table} SET {$upStr} WHERE id = {$order_id}");
    }

    /**
     * 修改主订单
     */
    public function updateOrderInfo($out_trade_no, $data) {
        $out_trade_no = $this->db->escape_string($out_trade_no);
        $upStr = $this->buildUpdateAssignments($data);
        if ($upStr === '') {
            return false;
        }
        return $this->db->query("UPDATE {$this->table} SET {$upStr} WHERE out_trade_no = '{$out_trade_no}'");
    }



    /**
     * 获取订单总数
     */
    public function getOrderNum($where) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $w = "";
        $prefix = DB_PREFIX;
        if(!empty($where['email_username'])){
            $email_username = $where['email_username'];
            $w .= " and (u.email like  CONCAT('%', '{$email_username}', '%') or u.username like  CONCAT('%', '{$email_username}', '%') or u.nickname like  CONCAT('%', '{$email_username}', '%') or u.tel like  CONCAT('%', '{$email_username}', '%'))";
        }

        if(!empty($where['out_trade_no'])){
            $out_trade_no = $where['out_trade_no'];
            $w .= " and (o.out_trade_no like  CONCAT('%', '{$out_trade_no}', '%') or o.up_no like  CONCAT('%', '{$out_trade_no}', '%') or o.trade_no like CONCAT('%', '{$out_trade_no}', '%') or o.api_trade_no like CONCAT('%', '{$out_trade_no}', '%'))";
        }
        if(!empty($where['goods_title'])){
            $goods_title = $where['goods_title'];
            $w .= " and g.title like  CONCAT('%', '{$goods_title}', '%')";
        }
        if(!empty($where['client_ip'])){
            $client_ip = $where['client_ip'];
            $w .= " and o.client_ip like  CONCAT('%', '{$client_ip}', '%')";
        }
        if(isset($where['pay_status']) && $where['pay_status'] !== ''){
            if($where['pay_status'] == 'y'){
                $w .= " and o.pay_time IS NOT NULL";
            }else if($where['pay_status'] == 'n'){
                $w .= " and o.pay_time IS NULL";
            }
        }
        if(!empty($where['create_time'])){
            $time_range = explode(' - ', $where['create_time']);
            if(count($time_range) == 2){
                $start_time = strtotime($time_range[0]);
                $end_time = strtotime($time_range[1]);
                $w .= " and o.create_time >= {$start_time} and o.create_time <= {$end_time}";
            }
        }
        $promoter_uid = isset($where['promoter_uid']) ? (int)$where['promoter_uid'] : 0;
        if($promoter_uid > 0){
            $w .= " and c.owner_uid = {$promoter_uid}";
        }



        $data = $this->db->once_fetch_array("SELECT
                            count(DISTINCT o.id) as total
                        FROM
                            {$this->table} as o
                        LEFT JOIN {$prefix}user as u on o.user_id=u.uid
                        LEFT JOIN {$prefix}order_list as ol on o.id=ol.order_id
                        LEFT JOIN {$prefix}goods g on g.id=ol.goods_id
                        LEFT JOIN {$prefix}coupon c on o.coupon_id=c.id
                        WHERE
                            o.delete_time IS NULL {$w}
                            ");
        return $data['total'];
    }

    /**
     * 获取订单总金额
     */
    public function getOrderTotalAmount($where) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $w = "";
        $prefix = DB_PREFIX;
        if(!empty($where['email_username'])){
            $email_username = $where['email_username'];
            $w .= " and (u.email like  CONCAT('%', '{$email_username}', '%') or u.username like  CONCAT('%', '{$email_username}', '%') or u.nickname like  CONCAT('%', '{$email_username}', '%') or u.tel like  CONCAT('%', '{$email_username}', '%'))";
        }

        if(!empty($where['out_trade_no'])){
            $out_trade_no = $where['out_trade_no'];
            $w .= " and (o.out_trade_no like  CONCAT('%', '{$out_trade_no}', '%') or o.up_no like  CONCAT('%', '{$out_trade_no}', '%') or o.trade_no like CONCAT('%', '{$out_trade_no}', '%') or o.api_trade_no like CONCAT('%', '{$out_trade_no}', '%'))";
        }
        if(!empty($where['goods_title'])){
            $goods_title = $where['goods_title'];
            $w .= " and g.title like  CONCAT('%', '{$goods_title}', '%')";
        }
        if(!empty($where['client_ip'])){
            $client_ip = $where['client_ip'];
            $w .= " and o.client_ip like  CONCAT('%', '{$client_ip}', '%')";
        }
        if(isset($where['pay_status']) && $where['pay_status'] !== ''){
            if($where['pay_status'] == 'y'){
                $w .= " and o.pay_time IS NOT NULL";
            }else if($where['pay_status'] == 'n'){
                $w .= " and o.pay_time IS NULL";
            }
        }
        if(!empty($where['create_time'])){
            $time_range = explode(' - ', $where['create_time']);
            if(count($time_range) == 2){
                $start_time = strtotime($time_range[0]);
                $end_time = strtotime($time_range[1]);
                $w .= " and o.create_time >= {$start_time} and o.create_time <= {$end_time}";
            }
        }
        $promoter_uid = isset($where['promoter_uid']) ? (int)$where['promoter_uid'] : 0;
        if($promoter_uid > 0){
            $w .= " and c.owner_uid = {$promoter_uid}";
        }

        $data = $this->db->once_fetch_array("SELECT
                            SUM(o.amount) as total_amount
                        FROM
                            {$this->table} as o
                        LEFT JOIN {$prefix}user as u on o.user_id=u.uid
                        LEFT JOIN {$prefix}order_list as ol on o.id=ol.order_id
                        LEFT JOIN {$prefix}goods g on g.id=ol.goods_id
                        LEFT JOIN {$prefix}coupon c on o.coupon_id=c.id
                        WHERE
                            o.delete_time IS NULL {$w}
                            ");
        return $data['total_amount'] ? $data['total_amount'] / 100 : 0;
    }

    /**
     * 获取订单列表
     */
    public function getOrderForAdmin($start, $limit, $where) {
        $this->maybeCleanupExpiredUnpaidOrders();

        $prefix = DB_PREFIX;

        $w = "";

        if(!empty($where['email_username'])){
            $email_username = $where['email_username'];
            $w .= " and (u.email like  CONCAT('%', '{$email_username}', '%') or u.username like  CONCAT('%', '{$email_username}', '%') or u.nickname like  CONCAT('%', '{$email_username}', '%') or u.tel like  CONCAT('%', '{$email_username}', '%'))";
        }

        if(!empty($where['out_trade_no'])){
            $out_trade_no = $where['out_trade_no'];
            $w .= " and (o.out_trade_no like  CONCAT('%', '{$out_trade_no}', '%') or o.up_no like  CONCAT('%', '{$out_trade_no}', '%') or o.trade_no like CONCAT('%', '{$out_trade_no}', '%') or o.api_trade_no like CONCAT('%', '{$out_trade_no}', '%'))";
        }
        if(!empty($where['goods_title'])){
            $goods_title = $where['goods_title'];
            $w .= " and g.title like  CONCAT('%', '{$goods_title}', '%')";
        }
        if(!empty($where['client_ip'])){
            $client_ip = $where['client_ip'];
            $w .= " and o.client_ip like  CONCAT('%', '{$client_ip}', '%')";
        }

        if(isset($where['pay_status']) && $where['pay_status'] !== ''){
            if($where['pay_status'] == 'y'){
                $w .= " and o.pay_time IS NOT NULL";
            }else if($where['pay_status'] == 'n'){
                $w .= " and o.pay_time IS NULL";
            }
        }

        if(!empty($where['create_time'])){
            $time_range = explode(' - ', $where['create_time']);
            if(count($time_range) == 2){
                $start_time = strtotime($time_range[0]);
                $end_time = strtotime($time_range[1]);
                $w .= " and o.create_time >= {$start_time} and o.create_time <= {$end_time}";
            }
        }
        $promoter_uid = isset($where['promoter_uid']) ? (int)$where['promoter_uid'] : 0;
        if($promoter_uid > 0){
            $w .= " and c.owner_uid = {$promoter_uid}";
        }
        $sql = <<<sql
                        SELECT 
                            o.* , u.email user_email, u.tel user_tel, u.nickname user_nickname 
                        FROM 
                            {$this->table} as o
                        LEFT JOIN {$prefix}user as u on o.user_id=u.uid
                        LEFT JOIN {$prefix}order_list as ol on o.id=ol.order_id
                        LEFT JOIN {$prefix}goods g on g.id=ol.goods_id
                        LEFT JOIN {$prefix}coupon c on o.coupon_id=c.id
                        WHERE 
                            o.delete_time IS NULL {$w} 
                        GROUP BY 
                            o.id 
                        ORDER BY 
                            o.id desc 
                        limit 
                            {$start}, {$limit}
sql;
//        echo $sql;die;
        $res = $this->db->query($sql);

        $order_ids = [];
        $data = [];
        while ($row = $this->db->fetch_array($res)) {
            $row['status_text'] = orderStatusText($row['status']);
            $row['amount'] /= 100;
            $row['email'] = empty($row['email']) ? '无' : $row['email'];
            $row['pwd'] = empty($row['pwd']) ? '无' : $row['pwd'];
            $row['tel'] = empty($row['tel']) ? '无' : $row['tel'];
            $row['up_no'] = empty($row['up_no']) ? '无' : $row['up_no'];
            $row['trade_no'] = empty($row['trade_no']) ? '无' : $row['trade_no'];
            $row['api_trade_no'] = empty($row['api_trade_no']) ? '无' : $row['api_trade_no'];
            $row['user_nickname'] = $row['user_id'] == 0 ? '游客身份' : $row['user_nickname'];
            

            $data[] = $row;
            $order_ids[] = $row['id'];
        }
        if(empty($data)) {
            return $data;
        }
        $order_ids = array_unique($order_ids);
        $order_ids = implode(',', $order_ids);

        $prefix = DB_PREFIX;
        $sql = <<<sql
                SELECT
                    ol.*, g.title, g.type, g.cover
                FROM
                    {$prefix}order_list as ol
                LEFT JOIN
                    {$prefix}goods as g ON ol.goods_id = g.id
                WHERE
                    ol.order_id IN ($order_ids) 
sql;
//        echo $sql;die;
        $order_list = [];
//        echo $order_ids;die;

        $res = $this->db->query($sql);
        while($row = $this->db->fetch_array($res)){

            foreach($data as $key => $val){
                if($val['id'] == $row['order_id']){
                    $row['goods_url'] = Url::log($row['goods_id']);
//                    var_dump($row['attach_user']);
                    $row['attach_user'] = empty($row['attach_user']) ? [] : json_decode($row['attach_user'], true);
                    $row['unit_price'] /= 100;
                    $attach_user = '';
//                    d($row);
                    foreach($row['attach_user'] as $k => $v){
                        $attach_user .= $k . '：' . $v . '；';
                    }
                    $row['attach_user'] = empty($attach_user) ? '无' : $attach_user;
                    if (in_array($row['type'], ['em_auto', 'em_manual']) && function_exists('emFormatSkuOptionIds')) {
                        $row['attr_spec'] = emFormatSkuOptionIds($row['goods_id'], $row['sku'] ?? '');
                    } else {
                        $row['attr_spec'] = empty($row['attr_spec']) ? '默认规格' : $row['attr_spec'];
                    }
                    $data[$key]['list'][] = $row;
                    $row['attach_user'] = '';
                }
            }
        }

//        d($data);die;


        return $data;
    }



    /**
     * 获取当前登录用户的订单总数
     */
    public function getUserOrderNum() {
        $data = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM $this->table where delete_time is null and user_id=" . UID);
        if(empty($data)){
            return 0;
        }
        return $data['total'];
    }


    public function getUserOrderForHome($page = 1) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $perpage_num = Option::get('admin_article_perpage_num');
        $perpage_num = $perpage_num ? $perpage_num : 10;

        $start_limit = !empty($page) ? ($page - 1) * $perpage_num : 0;
        $limit = "$start_limit, " . $perpage_num;
        $prefix = DB_PREFIX;
        $sql = "
                        SELECT 
                            o.* 
                        FROM 
                            {$this->table} as o
                        WHERE 
                            o.delete_time IS NULL and user_id=" . UID . " 
                        GROUP BY 
                            o.id 
                        ORDER BY 
                            o.id desc 
                        LIMIT 
                            $limit";
        $res = $this->db->query($sql);

        $order_ids = "";
        $data = [];
        while ($row = $this->db->fetch_array($res)) {
            $row['status'] = orderStatusText($row['status']);
            $row['amount'] /= 100;

            $data[] = $row;
            $order_ids .= $row['id'] . ",";
        }
        if(empty($data)) {
            return $data;
        }
        $order_ids = rtrim($order_ids, ",");
        $prefix = DB_PREFIX;
        $sql = <<<sql
                SELECT
                    ol.*, g.title, g.type, g.cover
                FROM
                    {$prefix}order_list as ol
                LEFT JOIN
                    {$prefix}goods as g ON ol.goods_id = g.id
                WHERE
                    ol.order_id IN ($order_ids)
sql;
        $res = $this->db->query($sql);
        while($row = $this->db->fetch_array($res)){
            foreach($data as $key => $val){
                if($val['id'] == $row['order_id']){
                    $row['goods_url'] = Url::goods($row['goods_id']);
                    $data[$key]['list'][] = $row;
                }
            }
        }

        return $data;
    }


    /**
     * 根据订单号查询订单
     */
    public function getOrderByOrderNo($order_no, $includeDeleted = false) {
        return $this->getOrderByReference($order_no, $includeDeleted);
    }

    /**
     * 根据订单号和游客信息查询订单
     */
    public function getOrderByVisitorInfo($order_no, $contact = '', $password = '', $includeDeleted = false) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $order_no = $this->db->escape_string($order_no);
        $contact = $this->db->escape_string($contact);
        $password = $this->db->escape_string($password);

        $conditions = ["(out_trade_no = '{$order_no}' OR up_no = '{$order_no}' OR trade_no = '{$order_no}' OR api_trade_no = '{$order_no}')"];

        if (!empty($contact)) {
            $conditions[] = "contact = '{$contact}'";
        }

        if (!empty($password)) {
            $conditions[] = "pwd = '{$password}'";
        }

        if (!$includeDeleted) {
            $conditions[] = "delete_time IS NULL";
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";

        $order = $this->db->once_fetch_array($sql);
        if ($this->resolveExpiredPendingOrder($order)) {
            $order = $this->db->once_fetch_array($sql);
        }

        return $order;
    }

    /**
     * 根据游客信息查询订单列表
     */
    public function getOrdersByVisitorInfo($contact = '', $password = '', $page = 1, $pageSize = 10) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $contact = $this->db->escape_string($contact);
        $password = $this->db->escape_string($password);

        $conditions = [];

        if (!empty($contact)) {
            $conditions[] = "o.contact = '{$contact}'";
        }

        if (!empty($password)) {
            $conditions[] = "o.pwd = '{$password}'";
        }

        if (empty($conditions)) {
            return [];
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $pageSize;
        $prefix = DB_PREFIX;

        $sql = "SELECT DISTINCT o.id, ol.goods_id, g.type,
                o.out_trade_no, o.up_no, o.trade_no, o.api_trade_no, g.title, o.create_time, o.pay_time, o.status, o.amount, ol.quantity, ol.attr_spec, ol.sku,
                ol.attach_user, ol.unit_price, ol.cost_price, o.payment, g.cover
                FROM {$prefix}order o
                INNER JOIN {$prefix}order_list ol ON o.id = ol.order_id
                LEFT JOIN {$prefix}goods g on ol.goods_id = g.id
                WHERE o.delete_time is null AND {$where}
                ORDER BY o.id DESC LIMIT {$offset}, {$pageSize}";

        $res = $this->db->fetch_all($sql);

        foreach($res as $key => $val){
            $_text = empty($val['attach_user']) ? [] : json_decode($val['attach_user'], true);
            $res[$key]['attach_user_text'] = '';
            if(!empty($_text)){
                foreach($_text as $k => $v){
                    $res[$key]['attach_user_text'] .= $k . "：" . $v . "；";
                }
            }

            $res[$key]['amount'] = number_format($val['amount'] / 100, 2);
            $res[$key]['goods_url'] = Url::goods($val['goods_id']);
            $res[$key]['url'] = Url::goods($val['goods_id']);
            $res[$key]['pay_time_text'] = empty($val['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $val['pay_time']);
            $res[$key]['status_text'] = orderStatusText($val['status']);
        }

        return $res;
    }

    /**
     * 根据游客信息查询订单数量
     */
    public function getOrdersCountByVisitorInfo($contact = '', $password = '') {
        $this->maybeCleanupExpiredUnpaidOrders();
        $contact = $this->db->escape_string($contact);
        $password = $this->db->escape_string($password);

        $conditions = [];

        if (!empty($contact)) {
            $conditions[] = "contact = '{$contact}'";
        }

        if (!empty($password)) {
            $conditions[] = "pwd = '{$password}'";
        }

        if (empty($conditions)) {
            return 0;
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE delete_time IS NULL AND {$where}";

        $data = $this->db->once_fetch_array($sql);
        return empty($data['total']) ? 0 : $data['total'];
    }

    /**
     * 游客查询订单 - 订单总数量
     */
    public function getYoukeOrderCount($pwd) {
        $this->maybeCleanupExpiredUnpaidOrders();
        $prefix = DB_PREFIX;
        $sql = "SELECT count(o.id) AS total FROM $this->table as o LEFT JOIN {$prefix}order_list as ol on o.id=ol.order_id where delete_time is null and (contact = '{$pwd}' or up_no='{$pwd}' or out_trade_no='{$pwd}' or trade_no='{$pwd}' or api_trade_no='{$pwd}' or ol.attach_user LIKE '%:\"{$pwd}\"%')";
        $data = $this->db->once_fetch_array($sql);
        return empty($data['total']) ? 0 : $data['total'];
    }

    /**、
     * 游客订单列表
     */
    public function getYoukeOrderForHome($keyword, $page = 1, $pageNum = 10) {
        $this->maybeCleanupExpiredUnpaidOrders();
        if (is_numeric($keyword) && !is_numeric($page)) {
            $temp = $keyword;
            $keyword = $page;
            $page = (int)$temp;
        }

        $keyword = trim((string)$keyword);
        if ($keyword === '') {
            return [];
        }

        $offset = ($page - 1) * $pageNum;
        $prefix = DB_PREFIX;
        $keyword = $this->db->escape_string($keyword);

        $sql = "SELECT DISTINCT o.id, ol.goods_id, g.type, 
                o.out_trade_no, o.up_no, o.trade_no, o.api_trade_no, g.title, o.create_time, o.pay_time, o.status, o.amount, ol.quantity, ol.attr_spec, ol.sku, 
                ol.attach_user, ol.unit_price, ol.cost_price, o.payment, g.cover 
                FROM {$prefix}order o 
                INNER JOIN {$prefix}order_list ol ON o.id = ol.order_id 
                LEFT JOIN {$prefix}goods g on ol.goods_id = g.id 
                WHERE o.delete_time is null 
                AND (o.contact = '{$keyword}' OR o.up_no = '{$keyword}' OR o.out_trade_no = '{$keyword}' OR o.trade_no = '{$keyword}' OR o.api_trade_no = '{$keyword}' OR ol.attach_user LIKE '%:\"{$keyword}\"%')
                ORDER BY o.id DESC LIMIT {$offset}, {$pageNum}";

        $res = $this->db->fetch_all($sql);

        foreach($res as $key => $val){
            $_text = empty($val['attach_user']) ? [] : json_decode($val['attach_user'], true);
            $res[$key]['attach_user_text'] = '';
            if(!empty($_text)){
                foreach($_text as $k => $v){
                    $res[$key]['attach_user_text'] .= $k . "：" . $v . "；";
                }
            }

            $res[$key]['amount'] = number_format($val['amount'] / 100, 2);
            $res[$key]['goods_url'] = Url::goods($val['goods_id']);
            $res[$key]['url'] = Url::goods($val['goods_id']);
            $res[$key]['pay_time_text'] = empty($val['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $val['pay_time']);
            $res[$key]['status_text'] = orderStatusText($val['status']);
        }

        return $res;
    }
    /**
     * 获取登录用户的订单数量
     */
    public function getOrderCountForHome($user_id, $status = null, $search = null){
        $this->maybeCleanupExpiredUnpaidOrders();
        $sql = "SELECT count(*) as total FROM {$this->db_prefix}order o 
                LEFT JOIN {$this->db_prefix}order_list ol ON o.id = ol.order_id
                LEFT JOIN {$this->db_prefix}goods g ON ol.goods_id = g.id
                WHERE o.user_id = {$user_id} and o.delete_time is null";
        
        // 添加状态筛选条件
        $conditions = [];
        if ($status !== null && $status !== '') {
            $conditions[] = "o.status = '" . intval($status) . "'";
        }
        
        // 添加搜索条件
        if ($search !== null && trim($search) !== '') {
            $search = $this->db->escape_string(trim($search));
            $conditions[] = "(o.out_trade_no LIKE '%{$search}%' OR o.up_no LIKE '%{$search}%' OR o.trade_no LIKE '%{$search}%' OR o.api_trade_no LIKE '%{$search}%' OR g.title LIKE '%{$search}%')";
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 获取登录用户的订单
     */
    public function getOrderForHome($user_id, $page = 1, $pageSize = 10, $status = null, $search = null){
        $this->maybeCleanupExpiredUnpaidOrders();
        $offset = ($page - 1) * $pageSize;
        $prefix = DB_PREFIX;
        
        // 基础查询
        $sql = "SELECT DISTINCT o.id, ol.goods_id, g.type, 
                o.out_trade_no, o.up_no, o.trade_no, o.api_trade_no, g.title, o.create_time, o.pay_time, o.status, o.amount, ol.quantity, ol.attr_spec, ol.sku, 
                ol.attach_user, ol.unit_price, ol.cost_price, o.payment, g.cover 
                FROM {$prefix}order o 
                INNER JOIN {$prefix}order_list ol ON o.id = ol.order_id 
                LEFT JOIN {$prefix}goods g on ol.goods_id = g.id 
                WHERE o.user_id = {$user_id} and o.delete_time is null";
        
        // 添加状态筛选条件
        $conditions = [];
        if ($status !== null && $status !== '') {
            $conditions[] = "o.status = '" . intval($status) . "'";
        }
        
        // 添加搜索条件
        if ($search !== null && trim($search) !== '') {
            $search = $this->db->escape_string(trim($search));
            $conditions[] = "(o.out_trade_no LIKE '%{$search}%' OR o.up_no LIKE '%{$search}%' OR o.trade_no LIKE '%{$search}%' OR o.api_trade_no LIKE '%{$search}%' OR g.title LIKE '%{$search}%')";
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY o.id DESC LIMIT {$offset}, {$pageSize}";

        $res = $this->db->fetch_all($sql);

        foreach($res as $key => $val){
            $_text = empty($val['attach_user']) ? [] : json_decode($val['attach_user'], true);
            $res[$key]['attach_user_text'] = '';
            if(!empty($_text)){
                foreach($_text as $k => $v){
                    $res[$key]['attach_user_text'] .= $k . "：" . $v . "；";
                }
            }


            $res[$key]['amount'] = number_format($val['amount'] / 100, 2);
            $res[$key]['goods_url'] = Url::goods($val['goods_id']);
            $res[$key]['url'] = Url::goods($val['goods_id']);
            $res[$key]['pay_time_text'] = empty($val['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $val['pay_time']);
            $res[$key]['status_text'] = orderStatusText($val['status']);
        }
        
        return $res;
    }



    /**
     * 获取待处理订单数量
     */
    public function getDcl() {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} where status = 1 and delete_time is null";
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 获取今日订单数量
     */
    public function getTodayOrder() {
        $timestamp = time();
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} where DATE(FROM_UNIXTIME({$timestamp})) = CURDATE() and delete_time is null and pay_time is not null";
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 获取昨日订单数量
     */
    public function getYesterdayOrder() {
        $timestamp = time();
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} where DATE(FROM_UNIXTIME({$timestamp})) = CURDATE() - INTERVAL 1 DAY and delete_time is null and pay_time is not null";
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 获取近七天订单数量
     */
    public function getSevendayOrder() {

        $timestamp = time();
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} where DATE(FROM_UNIXTIME({$timestamp})) BETWEEN CURDATE() - INTERVAL 6 DAY AND CURDATE() and delete_time is null and pay_time is not null";
        $res = $this->db->once_fetch_array($sql);
        return $res['total'];
    }

    /**
     * 设置订单的本地标识
     * @param string $out_trade_no 订单号
     * @param string $local 本地标识
     * @return bool
     */
    public function setLocal($out_trade_no, $local) {
        $out_trade_no = $this->db->escape_string($out_trade_no);
        $local = $this->db->escape_string($local);
        $sql = "UPDATE {$this->table} SET em_local = '{$local}' WHERE out_trade_no = '{$out_trade_no}'";
        return $this->db->query($sql);
    }

    /**
     * 根据本地标识获取订单列表
     * @param string $local 本地标识
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public function getOrdersByLocal($local, $page = 1, $pageSize = 10) {
        $local = $this->db->escape_string($local);
        $offset = ($page - 1) * $pageSize;
        $prefix = DB_PREFIX;

        $sql = "SELECT DISTINCT o.id, ol.goods_id, g.type,
                o.out_trade_no, o.up_no, o.trade_no, o.api_trade_no, g.title, o.create_time, o.pay_time, o.status, o.amount, ol.quantity, ol.attr_spec, ol.sku,
                ol.attach_user, ol.unit_price, ol.cost_price, o.payment, g.cover
                FROM {$prefix}order o
                INNER JOIN {$prefix}order_list ol ON o.id = ol.order_id
                LEFT JOIN {$prefix}goods g on ol.goods_id = g.id
                WHERE o.delete_time is null AND o.em_local = '{$local}'
                ORDER BY o.id DESC LIMIT {$offset}, {$pageSize}";

        $res = $this->db->fetch_all($sql);

        foreach($res as $key => $val){
            $_text = empty($val['attach_user']) ? [] : json_decode($val['attach_user'], true);
            $res[$key]['attach_user_text'] = '';
            if(!empty($_text)){
                foreach($_text as $k => $v){
                    $res[$key]['attach_user_text'] .= $k . "：" . $v . "；";
                }
            }

            $res[$key]['amount'] = number_format($val['amount'] / 100, 2);
            $res[$key]['goods_url'] = Url::goods($val['goods_id']);
            $res[$key]['url'] = Url::goods($val['goods_id']);
            $res[$key]['pay_time_text'] = empty($val['pay_time']) ? '未付款' : date('Y-m-d H:i:s', $val['pay_time']);
            $res[$key]['status_text'] = orderStatusText($val['status']);
        }

        return $res;
    }

    /**
     * 根据本地标识获取订单数量
     * @param string $local 本地标识
     * @return int
     */
    public function getOrdersCountByLocal($local) {
        $local = $this->db->escape_string($local);
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE delete_time IS NULL AND em_local = '{$local}'";
        $data = $this->db->once_fetch_array($sql);
        return empty($data['total']) ? 0 : $data['total'];
    }

}
