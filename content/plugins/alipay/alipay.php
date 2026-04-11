<?php
/*
Plugin Name: 官方支付宝支付
Version: 1.1.6
Plugin URL:
Description: 官方支付宝支付。支持手机端、pc端及扫码支付3种模式
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');


/**
 * 初始化支付方式列表
 */
function init_alipay() {

    $plugin_storage = Storage::getInstance('alipay');
    $dm = $plugin_storage->getValue('dm');
    $mb = $plugin_storage->getValue('mb');
    $pc = $plugin_storage->getValue('pc');

    $cmd = '';
    if(isMobile()){
        if($mb){
            $cmd = 'mb';
        }else if($dm){
            $cmd = 'dm';
        }else {
            $cmd = 'error';
        }
    }else{
        if($pc){
            $cmd = 'pc';
        } else if($dm){
            $cmd = 'dm';
        }else {
            $cmd = 'error';
        }
    }



    if($cmd != 'error'){
        $GLOBALS['mode_payment'] = array_merge($GLOBALS['mode_payment'], [
            [
                'plugin_name' => 'alipay', // 插件名. 与插件目录名保持一致
                'icon' => EM_URL . 'content/plugins/alipay/icon-btn.png',
                'title' => '支付宝', // 当前支付方式名称
                'unique' => 'alipay', // 当前支付方式唯一标识，所有支付插件中此项禁止重复
                'name' => '支付宝'
            ]
        ]);
    }




}

addAction('mode_payment', 'init_alipay');

/**
 * 发起支付 (该方法命名规则：pay_插件名称)
 */
function pay_alipay($order_info, $order_list){
    //支付宝支付网关
    $gateway_url = "https://openapi.alipay.com/gateway.do";
    
    // $gateway_url = "https://openapi-sandbox.dl.alipaydev.com/gateway.do"; // 沙箱环境

    $plugin_storage = Storage::getInstance('alipay');
    $appid = $plugin_storage->getValue('appid');
    $public_key = $plugin_storage->getValue('public_key');
    $private_key = $plugin_storage->getValue('private_key');
    $dm = $plugin_storage->getValue('dm');
    $mb = $plugin_storage->getValue('mb');
    $pc = $plugin_storage->getValue('pc');

//    d($order_info);die;

    if($order_info['expire_time'] <= time()){
        emMsg('订单已过期，请重新发起支付');
    }

    $cmd = '';
    if(isMobile()){
        if($mb){
            $cmd = 'mb';
        }else if($dm){
            $cmd = 'dm';
        }else {
            $cmd = 'error';
        }
    }else{
        if($pc){
            $cmd = 'pc';
        } else if($dm){
            $cmd = 'dm';
        }else {
            $cmd = 'error';
        }
    }

    if($cmd == ''){
        emMsg('官方支付宝支付插件出现错误！请联系作者');
    }
    if($cmd == 'error'){
        emMsg('当前设备无法使用支付宝支付，请联系管理员或更换其他支付方式');
    }


    $data = [
        'app_id' => trim($appid), //应用id
        'format' => 'JSON', //返回数据类型
        'charset' => 'UTF-8',
        'sign_type' => 'RSA2', //加密方式
        'timestamp' => date('Y-m-d H:i:s', time()), //发送请求的时间
        'version' => '1.0', //api版本
//        'notify_url' => EM_URL . "action/notify/out_trade_no/" . $order_info['out_trade_no'] . ".html", //支付完成后的异步回调通知
        'notify_url' => EM_URL . "action/notify/alipay", //支付完成后的异步回调通知
    ];

    Log::debug('订单号：' . $order_info['out_trade_no'] . ' 异步回调地址：' . $data['notify_url']);


//  d($data);die;

    //$param['money'] = 0.01;

    $biz_content = [
        'subject' => $order_info['out_trade_no'], //商品名称
        'out_trade_no' => $order_info['out_trade_no'], //商户订单号
        'timeout_express' => '10m', //关闭订单时间
        'total_amount' => round($order_info['amount'] / 100, 2), //订单金额，单位/元
        'time_expire' => date('Y-m-d H:i:s', $order_info['expire_time'])
    ];
    
    // d($biz_content);die;
    
    $pay_name = '未知';



    if($cmd == 'mb'){
        $data['method'] = 'alipay.trade.wap.pay'; //接口名称 - 手机网站支付
        $biz_content['product_code'] = 'QUICK_WAP_WAY'; //销售产品码， 商家和支付宝签约的产品码
        $data['return_url'] = EM_URL . "action/return/alipay"; //付款完成后跳转的地址
        $biz_content['goods_type'] = 0; //商品主类型 0虚拟 1实物
        $biz_content['quit_url'] = EM_URL;
        $pay_name = '手机网站支付';
        $data['biz_content'] = json_encode($biz_content); //请求参数的集合
        $data['sign'] = getAlipaySign($data, ['private_key' => trim($private_key)]);

        if(empty($data['sign'])){
            emMsg('官方支付宝插件配置错误，签名生成失败！');
        }


        $html = "<p>提交支付中</p><form action='{$gateway_url}' method='post' id='alipay-form' style='display: none;'>";
        foreach($data as $key => $val){
            $html .= "<input type='text' name='" . $key . "' value='" . $val . "' />";
        }
        $html .= "</form><script>window.onload=function(){document.getElementById('alipay-form').submit()}</script>";

        echo $html; die;
    }
    if($cmd == 'pc'){
        $data['method'] = 'alipay.trade.page.pay'; //接口名称 - pc网站支付
        $biz_content['product_code'] = 'FAST_INSTANT_TRADE_PAY'; //销售产品码， 商家和支付宝签约的产品码
        $data['return_url'] = EM_URL . "action/return/alipay"; //付款完成后跳转的地址
        $biz_content['goods_type'] = 0; //商品主类型 0虚拟 1实物
        $biz_content['quit_url'] = EM_URL;

        $data['biz_content'] = json_encode($biz_content); //请求参数的集合
        $data['sign'] = getAlipaySign($data, ['private_key' => trim($private_key)]);

        if(empty($data['sign'])){
            emMsg('官方支付宝插件配置错误，签名生成失败！');
        }


        $html = "<p>提交支付中</p><form action='{$gateway_url}' method='post' id='alipay-form' style='display: none;'>";
        foreach($data as $key => $val){
            $html .= "<input type='text' name='" . $key . "' value='" . $val . "' />";
        }
        $html .= "</form><script>window.onload=function(){document.getElementById('alipay-form').submit()}</script>";
        echo $html; die;
    }
    if($cmd == 'dm'){
        $pay_name = '当面付';

        $data['method'] = 'alipay.trade.precreate'; //接口名称  - 当面付

        // d($data);die;

        $data['biz_content'] = json_encode($biz_content); //请求参数的集合
        $data['sign'] = getAlipaySign($data, ['private_key' => trim($private_key)]);



        if(empty($data['sign'])) emMsg('官方支付宝插件配置错误，签名生成失败！');

        $resultStr = ebCurl($gateway_url, http_build_query($data), true);
        $result = json_decode($resultStr, true);



        if(json_last_error() == 5){
            $resultStr = iconv('UTF-8', 'UTF-8//IGNORE', $resultStr);
            $result = json_decode($resultStr, true);
        }



        if (empty($result)){
            emMsg('支付发起失败，请重试刷新本页面重新发起支付');
        }
        $result = $result['alipay_trade_precreate_response'];

//        d($result);die;



        if ($result['code'] == 10000){

            header("location: ?plugin=alipay&out_trade_no={$order_info['out_trade_no']}&qr_code={$result['qr_code']}");
            die;

            return [
                'qr_code' => $result['qr_code']
            ];
        } else{

            if($result['sub_code'] == 'ACQ.ACCESS_FORBIDDEN'){
                emMsg('无权限使用【' . $pay_name . '】接口, 请前往支付宝进行签约');
            }

            if($result['code'] == 40004){
                emMsg('订单已过期，请从商品页重新发起支付');
            }

            if($result['code'] == 40003 && $result['sub_code'] == 'isv.app-unbind-partner'){
                emMsg('无效应用，或应用未绑定商户');
            }
            emMsg('错误的支付配置');

        }
    }

}


/**
 * 支付宝签名
 */
function getAlipaySign($data, $alipay){

    ksort($data);
    $data_str = "";
    foreach($data as $key => $val) {
        if ($key != "sign"){
            $data_str .= $key . "=" . $val . "&";
        }
    }
    $data_str = rtrim($data_str, "&");
    $sign = "";
    $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($alipay['private_key'], 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
    
    @openssl_sign($data_str, $sign, $private_key, OPENSSL_ALGO_SHA256);

    $sign = base64_encode($sign);
    return $sign;
}





function alipayCheckSign($notify_type) { 
    
    // d($notify_type);die;
    
    if($notify_type == 'return'){ // 同步
        // $get = $_GET;
        // d($get);die; 

        $params = [
            'out_trade_no' => Input::getStrVar('out_trade_no'),
            'charset' => Input::getStrVar('charset'),
            'method' => Input::getStrVar('method'),
            'total_amount' => Input::getStrVar('total_amount'),
            'sign' => Input::getStrVar('sign'),
            'trade_no' => Input::getStrVar('trade_no'),
            'auth_app_id' => Input::getStrVar('auth_app_id'),
            'version' => Input::getStrVar('version'),
            'app_id' => Input::getStrVar('app_id'),
            'sign_type' => Input::getStrVar('sign_type'),
            'seller_id' => Input::getStrVar('seller_id'),
            'timestamp' => Input::getStrVar('timestamp'),
        ];
        
        
    }else{ // 异步

        $content = file_get_contents('php://input');
        
        
        $content = urldecode($content);
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = explode('&', $content);
        $params = [];
        foreach ($content as $val) {
            $item = explode('=', $val, "2");
            $params[$item[0]] = $item[1];
        }
    
        if(!empty($params['trade_status']) && $params['trade_status'] != 'TRADE_SUCCESS') {
            Log::debug('支付宝支付 异步回调状态：未支付');
            return false;
        }
    }
    
    
    

    // var_dump($content);die;
    


    

    $sign = $params['sign'];

    if(!empty($params['sign'])) unset($params['sign']);
    if(!empty($params['sign_type'])) unset($params['sign_type']);

    ksort($params);
    $stringToBeSigned = "";
    $i = 0;
    foreach ($params as $k => $v) {
        if (false === checkEmpty($v) && "@" != substr($v, 0, 1)) {
            $v = mb_convert_encoding($v, 'gbk', 'utf-8');
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
    }
    unset ($k, $v);


    $plugin_storage = Storage::getInstance('alipay');
    $appid = $plugin_storage->getValue('appid');
    $public_key = $plugin_storage->getValue('public_key');
    $private_key = $plugin_storage->getValue('private_key');



    $public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($public_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    $result = (openssl_verify($stringToBeSigned, base64_decode($sign), $public_key, OPENSSL_ALGO_SHA256) === 1);

    
    if ($result) {
        
        if($notify_type == 'return'){ // 同步
            return [
                'timestamp' => strtotime($params['timestamp']),
                'out_trade_no' => $params['out_trade_no'],
                'up_no' => $params['trade_no']
            ];
        }else{
            return [
                'timestamp' => strtotime($params['notify_time']),
                'out_trade_no' => $params['out_trade_no'],
                'up_no' => $params['trade_no']
            ];
        }
        
        
    } else {
        Log::debug('支付宝支付 异步回调状态：验签失败');
        return false;
    }
}

/**
 * 校验$value是否非空
 *  if not set ,return true;
 *    if is null , return true;
 **/
function checkEmpty($value) {
    if (!isset($value)) return true;
    if ($value === null) return true;
    if (trim($value) === "") return true;

    return false;
}
