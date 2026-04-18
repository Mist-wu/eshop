<?php
/**
 * Coupon model
 */

class Coupon_Model {

    private $db;
    private $table;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'coupon';
    }

    private function normalizeCode($code) {
        return strtoupper(trim((string)$code));
    }

    private function normalizeAmount($amount) {
        return number_format((float)$amount, 2, '.', '');
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
