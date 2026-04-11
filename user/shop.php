<?php

require_once '../init.php';

$action = Input::getStrVar('action');

if($action == 'getGoodsInfo'){
    $goods_id = Input::postIntVar('goods_id');
    $selected_sku = Input::postStrArray('sku_ids');
    $quantity = Input::postIntVar('quantity');
    $quantity = max(1, (int)ceil($quantity));
    $coupon_code = Input::postStrVar('coupon_code');

    $goodsModel = new Goods_Model();
    $goods = $goodsModel->getOneGoodsForHome($goods_id, UID, LEVEL, $selected_sku, $quantity, $coupon_code);
    Ret::success('', $goods);
}

if($action == 'xiadan'){
    doAction('xiadan');
    $goods_id = Input::postIntVar('goods_id');
    $sku_ids = Input::postStrArray('sku_ids');
    $quantity = Input::postIntVar('quantity');
    $quantity = max(1, (int)ceil($quantity));
    $payment_plugin = Input::postStrVar('payment_plugin');
    $payment_title = Input::postStrVar('payment_title');
    $payment_name = Input::postStrVar('payment_name');
    $config = Input::postStrArray('config');
    $visitor_input = Input::postStrArray('visitor_input', 'error');
    $coupon_code = Input::postStrVar('coupon_code');

    if (empty($payment_plugin)) {
        Ret::error('请选择支付方式');
    }
    if ($payment_plugin == 'balance') {
        Ret::error('余额支付已下线，请选择支付宝或其他外部支付方式');
    }

    $allowedPayments = array_column(getPayment(false), 'plugin_name');
    if (!in_array($payment_plugin, $allowedPayments, true)) {
        Ret::error('当前支付方式未启用，请刷新页面后重试');
    }

    if(!ISLOGIN){

        if($visitor_input == 'error'){
            Ret::error('请联系网站管理员更新模板');
        }

        $visitor_required = new Goods_Controller();
        $visitor_required = $visitor_required->getVisitorRequired();
        
        if(isset($visitor_required['contact']) && empty($visitor_input['contact'])){
            Ret::error('请填写' . $visitor_required['contact']['title']);
        }
        if(isset($visitor_required['password']) && empty($visitor_input['password'])){
            Ret::error('请设置订单密码');
        }
    }

    $orderModel = new Order_Model();
    $res = $orderModel->createOrder([
        'payment_plugin' => $payment_plugin,
        'payment_name' => $payment_name,
        'coupon_code' => $coupon_code,
    ], $goods_id, $quantity, UID, LEVEL, $config, $visitor_input, $sku_ids);

    
    // d($res);die;
    

    // 统一的返回格式处理
    if(is_array($res)){
        // 有错误
        if(isset($res['code']) && $res['code'] !== 0 && $res['code'] != 200){
            Ret::error($res['msg'] ?? '下单失败');
        }

        // 写入本地标识
        if(isset($_COOKIE['EM_LOCAL'])){
            $local = strip_tags($_COOKIE['EM_LOCAL']);
            $orderModel->setLocal($res['out_trade_no'], $local);
        }

        // 其他支付方式（返回订单号）
        if(isset($res['out_trade_no'])){
            Ret::success('ok', ['out_trade_no' => $res['out_trade_no']]);
        }
    }

    // 兜底错误
    Ret::error('下单失败，请稍后重试');
}
