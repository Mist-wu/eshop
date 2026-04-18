<?php
/**
 * Coupon model
 */

class Coupon_Model {

    private $db;
    private $table;
    private static $promotionSchemaChecked = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'coupon';
        $this->ensurePromotionSchema();
    }

    private function normalizeCode($code) {
        return strtoupper(trim((string)$code));
    }

    private function normalizeAmount($amount) {
        return number_format((float)$amount, 2, '.', '');
    }

    private function normalizePrefix($prefix, $max_len = 8) {
        $prefix = strtoupper(trim((string)$prefix));
        if ($prefix === '') {
            return '';
        }
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
        if ($prefix === '') {
            return '';
        }
        if ($max_len > 0 && strlen($prefix) > $max_len) {
            $prefix = substr($prefix, 0, $max_len);
        }
        return $prefix;
    }

    private function quoteIdentifier($identifier) {
        return '`' . str_replace('`', '``', (string)$identifier) . '`';
    }

    private function tableHasColumn($columnName) {
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

    private function tableHasIndex($indexName) {
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

    public function ensurePromotionSchema() {
        if (self::$promotionSchemaChecked) {
            return;
        }
        self::$promotionSchemaChecked = true;

        $alterClauses = [];
        if (!$this->tableHasColumn('owner_uid')) {
            $alterClauses[] = "ADD COLUMN `owner_uid` int(11) NOT NULL DEFAULT 0 COMMENT '所属推广者UID' AFTER `code`";
        }
        if (!$this->tableHasColumn('is_invite')) {
            $alterClauses[] = "ADD COLUMN `is_invite` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否推广邀请码' AFTER `owner_uid`";
        }
        if (!$this->tableHasIndex('idx_coupon_owner_uid')) {
            $alterClauses[] = "ADD INDEX `idx_coupon_owner_uid` (`owner_uid`)";
        }
        if (!$this->tableHasIndex('idx_coupon_is_invite')) {
            $alterClauses[] = "ADD INDEX `idx_coupon_is_invite` (`is_invite`)";
        }

        if (empty($alterClauses)) {
            return;
        }

        $this->db->query(
            'ALTER TABLE ' . $this->quoteIdentifier($this->table) . "\n            " . implode(",\n            ", $alterClauses)
        );
        Log::info('优惠券表已自动补齐推广字段迁移');
    }

    private function resolveCouponState($coupon, $now) {
        $status = (int)($coupon['status'] ?? 0);
        $end_time = (int)($coupon['end_time'] ?? 0);
        $use_limit = (int)($coupon['use_limit'] ?? 0);
        $used_times = (int)($coupon['used_times'] ?? 0);

        if ($status !== 1) {
            return ['key' => 'disabled', 'text' => '已禁用'];
        }
        if ($end_time > 0 && $end_time < $now) {
            return ['key' => 'expired', 'text' => '已过期'];
        }
        if ($use_limit > 0 && $used_times >= $use_limit) {
            return ['key' => 'used_up', 'text' => '已用尽'];
        }
        return ['key' => 'active', 'text' => '可用'];
    }

    private function generateCouponCode($prefix = '') {
        $random = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        $prefix = $this->normalizePrefix($prefix);
        if ($prefix !== '') {
            return $prefix . $random;
        }
        return $random;
    }

    public function createPromoterCoupon($ownerUid, $params = []) {
        $ownerUid = (int)$ownerUid;
        if ($ownerUid <= 0) {
            return ['ok' => false, 'msg' => '推广者ID不合法'];
        }

        $defaultDiscount = Option::get('promoter_coupon_default_discount');
        if ($defaultDiscount === '' || $defaultDiscount === null) {
            $defaultDiscount = '2.00';
        }

        $discountValue = $this->normalizeAmount($params['discount_value'] ?? $defaultDiscount);
        if (emBcComp($discountValue, '0.00', 2) <= 0) {
            $discountValue = '2.00';
        }

        $prefix = $this->normalizePrefix($params['prefix'] ?? ('U' . $ownerUid));
        if ($prefix === '') {
            $prefix = 'U' . $ownerUid;
        }

        $remark = trim((string)($params['remark'] ?? ''));
        if ($remark === '') {
            $remark = '推广者UID:' . $ownerUid;
        }

        $useLimit = isset($params['use_limit']) ? (int)$params['use_limit'] : 10000;
        if ($useLimit < 0) {
            $useLimit = 0;
        }

        $endTime = isset($params['end_time']) ? (int)$params['end_time'] : 0;
        if ($endTime < 0) {
            $endTime = 0;
        }

        $status = (isset($params['status']) && (int)$params['status'] === 0) ? 0 : 1;

        $code = '';
        $maxTry = 20;
        for ($i = 0; $i < $maxTry; $i++) {
            $tempCode = $this->generateCouponCode($prefix);
            $exists = $this->getByCode($tempCode, false);
            if (empty($exists)) {
                $code = $tempCode;
                break;
            }
        }

        if ($code === '') {
            return ['ok' => false, 'msg' => '邀请码生成失败，请重试'];
        }

        $now = time();
        $id = $this->db->add('coupon', [
            'type' => 'general',
            'category_id' => 0,
            'goods_id' => 0,
            'remark' => $remark,
            'threshold_type' => 'none',
            'min_amount' => '0.00',
            'discount_type' => 'amount',
            'discount_value' => $discountValue,
            'end_time' => $endTime,
            'use_limit' => $useLimit,
            'prefix' => $prefix,
            'code' => $code,
            'owner_uid' => $ownerUid,
            'is_invite' => 1,
            'used_times' => 0,
            'status' => $status,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        if ((int)$id <= 0) {
            return ['ok' => false, 'msg' => '邀请码创建失败'];
        }

        return [
            'ok' => true,
            'id' => (int)$id,
            'code' => $code,
        ];
    }

    public function getPromoterCouponCount($ownerUid) {
        $ownerUid = (int)$ownerUid;
        if ($ownerUid <= 0) {
            return 0;
        }
        $row = $this->db->once_fetch_array(
            "SELECT COUNT(*) AS total FROM `{$this->table}` WHERE owner_uid = {$ownerUid}"
        );
        return (int)($row['total'] ?? 0);
    }

    public function getPromoterCouponStats($ownerUid) {
        $ownerUid = (int)$ownerUid;
        if ($ownerUid <= 0) {
            return [
                'coupon_total' => 0,
                'used_total' => 0,
                'active_total' => 0,
            ];
        }

        $row = $this->db->once_fetch_array(
            "SELECT
                COUNT(*) AS coupon_total,
                IFNULL(SUM(used_times), 0) AS used_total,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_total
             FROM `{$this->table}`
             WHERE owner_uid = {$ownerUid}"
        );

        return [
            'coupon_total' => (int)($row['coupon_total'] ?? 0),
            'used_total' => (int)($row['used_total'] ?? 0),
            'active_total' => (int)($row['active_total'] ?? 0),
        ];
    }

    public function getPromoterCoupons($ownerUid, $page = 1, $limit = 20) {
        $ownerUid = (int)$ownerUid;
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        if ($ownerUid <= 0) {
            return [];
        }

        $rows = $this->db->fetch_all(
            "SELECT * FROM `{$this->table}`
             WHERE owner_uid = {$ownerUid}
             ORDER BY id DESC
             LIMIT {$offset}, {$limit}"
        );

        $now = time();
        foreach ($rows as &$row) {
            $state = $this->resolveCouponState($row, $now);
            $row['state_key'] = $state['key'];
            $row['state_text'] = $state['text'];
            $row['discount_text'] = ($row['discount_type'] ?? '') === 'percent'
                ? ('抵扣 ' . $row['discount_value'] . '%')
                : ('抵扣 ' . $row['discount_value'] . ' 元');
            $row['expire_text'] = empty($row['end_time']) ? '永久有效' : date('Y-m-d H:i:s', (int)$row['end_time']);
            $row['use_limit_text'] = ((int)($row['use_limit'] ?? 0) > 0) ? (string)$row['use_limit'] : '不限';
            $row['create_time_text'] = !empty($row['create_time']) ? date('Y-m-d H:i:s', (int)$row['create_time']) : '-';
        }
        unset($row);

        return $rows;
    }

    /**
     * 按券码获取优惠券
     * @param string $code
     * @param bool $forUpdate
     * @return array|null
     */
    public function getByCode($code, $forUpdate = false) {
        $code = $this->normalizeCode($code);
        if ($code === '') {
            return null;
        }
        $safe = $this->db->escape_string($code);
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        return $this->db->once_fetch_array("SELECT * FROM `{$this->table}` WHERE `code` = '{$safe}' LIMIT 1{$lock}");
    }

    /**
     * 应用优惠券（传入已获取的券记录）
     * @param array|null $coupon
     * @param array $goods
     * @param string|float $amount
     * @return array
     */
    public function applyCouponRow($coupon, $goods, $amount) {
        $amount = $this->normalizeAmount($amount);
        if (empty($coupon)) {
            return [
                'valid' => false,
                'msg' => '优惠券不存在',
                'coupon' => null,
                'amount_before' => $amount,
                'discount_amount' => '0.00',
                'final_amount' => $amount,
            ];
        }

        $now = time();
        if ((int)($coupon['status'] ?? 0) !== 1) {
            return $this->invalidResult('优惠券已停用', $coupon, $amount);
        }
        $endTime = (int)($coupon['end_time'] ?? 0);
        if ($endTime > 0 && $endTime < $now) {
            return $this->invalidResult('优惠券已过期', $coupon, $amount);
        }
        $useLimit = (int)($coupon['use_limit'] ?? 0);
        $usedTimes = (int)($coupon['used_times'] ?? 0);
        if ($useLimit > 0 && $usedTimes >= $useLimit) {
            return $this->invalidResult('优惠券已用尽', $coupon, $amount);
        }

        $goodsId = (int)($goods['id'] ?? 0);
        $sortId = (int)($goods['sort_id'] ?? 0);
        $type = $coupon['type'] ?? 'general';
        if ($type === 'goods' && (int)($coupon['goods_id'] ?? 0) !== $goodsId) {
            return $this->invalidResult('优惠券不适用于该商品', $coupon, $amount);
        }
        if ($type === 'category' && (int)($coupon['category_id'] ?? 0) !== $sortId) {
            return $this->invalidResult('优惠券不适用于该分类', $coupon, $amount);
        }

        $thresholdType = $coupon['threshold_type'] ?? 'none';
        if ($thresholdType === 'min') {
            $minAmount = $this->normalizeAmount($coupon['min_amount'] ?? 0);
            if (emBcComp($amount, $minAmount, 2) < 0) {
                return $this->invalidResult('未满足优惠券使用门槛', $coupon, $amount);
            }
        }

        $discountType = $coupon['discount_type'] ?? 'amount';
        $discountValue = $this->normalizeAmount($coupon['discount_value'] ?? 0);
        $discountAmount = '0.00';

        if ($discountType === 'percent') {
            $percent = emBcDiv($discountValue, '100', 4);
            $discountAmount = emBcMul($amount, $percent, 2);
        } else {
            $discountAmount = $discountValue;
        }

        if (emBcComp($discountAmount, '0', 2) <= 0) {
            return $this->invalidResult('优惠券优惠值不合法', $coupon, $amount);
        }

        if (emBcComp($discountAmount, $amount, 2) > 0) {
            $discountAmount = $amount;
        }

        $finalAmount = emBcSub($amount, $discountAmount, 2);

        return [
            'valid' => true,
            'msg' => 'ok',
            'coupon' => $coupon,
            'amount_before' => $amount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * 应用优惠券（按券码）
     */
    public function applyCouponCode($code, $goods, $amount, $forUpdate = false) {
        $coupon = $this->getByCode($code, $forUpdate);
        return $this->applyCouponRow($coupon, $goods, $amount);
    }

    private function invalidResult($msg, $coupon, $amount) {
        return [
            'valid' => false,
            'msg' => $msg,
            'coupon' => $coupon,
            'amount_before' => $amount,
            'discount_amount' => '0.00',
            'final_amount' => $amount,
        ];
    }
}
