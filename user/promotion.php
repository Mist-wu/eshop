<?php
/**
 * 推广中心
 */

require_once 'globals.php';

$couponModel = new Coupon_Model();
$action = Input::getStrVar('action');

if ($action === 'index') {
    loginAuth::checkLogin(NULL, 'user');

    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);

    $list = $couponModel->getPromoterCoupons(UID, $page, $limit);
    $total = $couponModel->getPromoterCouponCount(UID);
    Output::data($list, $total);
}

if ($action === 'create') {
    loginAuth::checkLogin(NULL, 'user');
    LoginAuth::checkToken();

    $prefix = Input::postStrVar('prefix');
    $defaultDiscount = Option::get('promoter_coupon_default_discount');
    if ($defaultDiscount === '' || $defaultDiscount === null) {
        $defaultDiscount = '2.00';
    }

    $result = $couponModel->createPromoterCoupon(UID, [
        'prefix' => $prefix,
        'discount_value' => $defaultDiscount,
    ]);
    if (empty($result['ok'])) {
        Output::error($result['msg'] ?? '创建失败');
    }

    Output::ok([
        'id' => (int)$result['id'],
        'code' => (string)$result['code'],
    ]);
}

if (empty($action)) {
    loginAuth::checkLogin(NULL, 'user');

    $promoterStats = $couponModel->getPromoterCouponStats(UID);
    $defaultDiscount = Option::get('promoter_coupon_default_discount');
    if ($defaultDiscount === '' || $defaultDiscount === null) {
        $defaultDiscount = '2.00';
    }
    $defaultDiscount = number_format((float)$defaultDiscount, 2, '.', '');
    if ((float)$defaultDiscount <= 0) {
        $defaultDiscount = '2.00';
    }

    include View::getUserView('_header');
    require_once View::getUserView('promotion');
    include View::getUserView('_footer');
    View::output();
}

