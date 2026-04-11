<?php

defined('EM_ROOT') || exit('access denied!');

addAction('mode_payment', 'yifutRegisterPaymentMethods');

function yifutRegisterPaymentMethods() {
    if (!yifutIsConfigured()) {
        return;
    }

    if (emEnvBool('EM_YIFUT_ENABLE_ALIPAY', true)) {
        $GLOBALS['mode_payment'][] = [
            'plugin_name' => 'yifut_alipay',
            'icon' => yifutAssetUrl('content/payment-icons/alipay-official.png'),
            'title' => '支付宝',
            'unique' => 'yifut_alipay',
            'name' => '支付宝',
        ];
    }

    if (emEnvBool('EM_YIFUT_ENABLE_WXPAY', true)) {
        $GLOBALS['mode_payment'][] = [
            'plugin_name' => 'yifut_wxpay',
            'icon' => yifutAssetUrl('content/payment-icons/wechat.svg'),
            'title' => '微信支付',
            'unique' => 'yifut_wxpay',
            'name' => '微信支付',
        ];
    }
}

function yifutAssetUrl($relativePath) {
    $path = EM_ROOT . '/' . ltrim($relativePath, '/');
    $url = EM_URL . ltrim($relativePath, '/');

    if (is_file($path)) {
        return $url . '?v=' . filemtime($path);
    }

    return $url;
}

function pay_yifut_alipay($order_info, $order_list) {
    yifutSubmitPayment('alipay', $order_info, $order_list, 'yifut_alipay');
}

function pay_yifut_wxpay($order_info, $order_list) {
    yifutSubmitPayment('wxpay', $order_info, $order_list, 'yifut_wxpay');
}

function yifut_alipayCheckSign($notifyType) {
    return yifutCheckSign($notifyType, 'alipay');
}

function yifut_wxpayCheckSign($notifyType) {
    return yifutCheckSign($notifyType, 'wxpay');
}

function yifutSubmitPayment($payType, $orderInfo, $orderList, $callbackPlugin) {
    if (!yifutIsConfigured()) {
        emMsg('易付通支付参数未配置完整，请先填写 .env 后再发起支付');
    }
    if (!extension_loaded('openssl')) {
        emMsg('服务器未启用 OpenSSL，无法发起易付通支付');
    }
    if (($orderInfo['expire_time'] ?? 0) <= time()) {
        emMsg('订单已过期，请重新下单');
    }

    $pid = trim((string)emEnv('EM_YIFUT_PID', ''));
    $channelId = trim((string)emEnv('EM_YIFUT_CHANNEL_ID', ''));
    $gateway = trim((string)emEnv('EM_YIFUT_GATEWAY', 'https://www.yifut.com/api/pay/submit'));
    $payload = [
        'pid' => $pid,
        'type' => $payType,
        'out_trade_no' => $orderInfo['out_trade_no'],
        'notify_url' => EM_URL . 'action/notify/' . $callbackPlugin,
        'return_url' => EM_URL . 'action/return/' . $callbackPlugin . '/' . rawurlencode($orderInfo['out_trade_no']),
        'name' => yifutBuildOrderName($orderInfo, $orderList),
        'money' => number_format(((float)$orderInfo['amount']) / 100, 2, '.', ''),
        'param' => $orderInfo['out_trade_no'],
        'timestamp' => (string)time(),
        'sign_type' => 'RSA',
    ];

    if ($channelId !== '') {
        $payload['channel_id'] = $channelId;
    }

    $payload['sign'] = yifutSign($payload);
    if ($payload['sign'] === '') {
        emMsg('易付通支付签名失败，请检查 .env 中的私钥格式');
    }

    yifutRenderAutoSubmitForm($gateway, $payload);
}

function yifutCheckSign($notifyType, $expectedType) {
    if (!yifutIsConfigured() || !extension_loaded('openssl')) {
        return false;
    }

    $params = $_GET;
    if (empty($params['sign']) || empty($params['out_trade_no'])) {
        return false;
    }

    if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
        Log::warning('易付通回调交易状态异常：' . ($params['trade_status'] ?? ''));
        return false;
    }

    $pid = trim((string)emEnv('EM_YIFUT_PID', ''));
    if ($pid !== '' && isset($params['pid']) && (string)$params['pid'] !== $pid) {
        Log::warning('易付通回调商户号不匹配');
        return false;
    }

    if (!empty($params['type']) && $params['type'] !== $expectedType) {
        Log::warning('易付通回调支付类型不匹配：' . $params['type']);
        return false;
    }

    $sign = (string)$params['sign'];
    unset($params['sign'], $params['sign_type']);

    $signContent = yifutBuildSignContent($params);
    $publicKey = yifutGetPublicKeyResource();
    if ($publicKey === false) {
        Log::warning('易付通平台公钥不可用');
        return false;
    }

    $verified = openssl_verify($signContent, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    if (is_object($publicKey) || is_resource($publicKey)) {
        openssl_free_key($publicKey);
    }

    if (!$verified) {
        Log::warning('易付通回调验签失败');
        return false;
    }

    $orderModel = new Order_Model();
    $order = $orderModel->getOrderInfo($params['out_trade_no']);
    if (empty($order)) {
        Log::warning('易付通回调订单不存在：' . $params['out_trade_no']);
        return false;
    }

    if (!isset($params['money']) || emBcComp((string)$order['amount'], (string)$params['money'], 2) !== 0) {
        Log::warning('易付通回调金额不匹配：' . $params['out_trade_no']);
        return false;
    }

    $paidAt = !empty($params['endtime']) ? strtotime($params['endtime']) : (int)($params['timestamp'] ?? time());
    if ($paidAt <= 0) {
        $paidAt = time();
    }

    return [
        'timestamp' => $paidAt,
        'out_trade_no' => $params['out_trade_no'],
        'up_no' => $params['api_trade_no'] ?? ($params['trade_no'] ?? ''),
    ];
}

function yifutIsConfigured() {
    if (!emEnvBool('EM_YIFUT_ENABLE', false)) {
        return false;
    }

    $required = [
        trim((string)emEnv('EM_YIFUT_PID', '')),
        trim((string)emEnv('EM_YIFUT_PRIVATE_KEY', '')),
        trim((string)emEnv('EM_YIFUT_PUBLIC_KEY', '')),
    ];

    foreach ($required as $value) {
        if ($value === '') {
            return false;
        }
    }

    return true;
}

function yifutBuildOrderName($orderInfo, $orderList) {
    $db = Database::getInstance();
    $titles = [];

    foreach ($orderList as $item) {
        $goodsId = (int)($item['goods_id'] ?? 0);
        if ($goodsId <= 0) {
            continue;
        }

        $goods = $db->once_fetch_array('SELECT title FROM ' . DB_PREFIX . 'goods WHERE id=' . $goodsId . ' LIMIT 1');
        if (!empty($goods['title'])) {
            $titles[] = trim((string)$goods['title']);
        }
    }

    $title = '商品订单 ' . $orderInfo['out_trade_no'];
    if (!empty($titles)) {
        $title = $titles[0];
        if (count($titles) > 1) {
            $title .= ' 等商品';
        }
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($title, 0, 120, '', 'UTF-8');
    }

    return substr($title, 0, 120);
}

function yifutRenderAutoSubmitForm($gateway, $payload) {
    $gateway = htmlspecialchars($gateway, ENT_QUOTES, 'UTF-8');
    $html = '<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>跳转支付中</title></head><body>';
    $html .= '<p style="font-family: sans-serif; padding: 24px; color: #334155;">正在跳转支付页面，请稍候...</p>';
    $html .= '<form id="yifut-pay-form" action="' . $gateway . '" method="post">';
    foreach ($payload as $key => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
    }
    $html .= '</form><script>document.getElementById("yifut-pay-form").submit();</script></body></html>';
    echo $html;
    exit;
}

function yifutSign($payload) {
    $privateKey = yifutGetPrivateKeyResource();
    if ($privateKey === false) {
        return '';
    }

    $sign = '';
    $signContent = yifutBuildSignContent($payload);
    $result = openssl_sign($signContent, $sign, $privateKey, OPENSSL_ALGO_SHA256);

    if (is_object($privateKey) || is_resource($privateKey)) {
        openssl_free_key($privateKey);
    }

    if ($result !== true) {
        return '';
    }

    return base64_encode($sign);
}

function yifutBuildSignContent($params) {
    $filtered = [];
    foreach ($params as $key => $value) {
        if ($key === 'sign' || $key === 'sign_type') {
            continue;
        }
        if (is_array($value) || is_object($value)) {
            continue;
        }
        if ($value === null || $value === '') {
            continue;
        }
        $filtered[(string)$key] = (string)$value;
    }

    ksort($filtered, SORT_STRING);

    $pairs = [];
    foreach ($filtered as $key => $value) {
        $pairs[] = $key . '=' . $value;
    }

    return implode('&', $pairs);
}

function yifutGetPrivateKeyResource() {
    return yifutGetKeyResource((string)emEnv('EM_YIFUT_PRIVATE_KEY', ''), true);
}

function yifutGetPublicKeyResource() {
    return yifutGetKeyResource((string)emEnv('EM_YIFUT_PUBLIC_KEY', ''), false);
}

function yifutGetKeyResource($rawKey, $isPrivate) {
    $rawKey = trim($rawKey);
    if ($rawKey === '') {
        return false;
    }

    $normalized = str_replace(["\r\n", "\r", '\\n'], ["\n", "\n", "\n"], $rawKey);
    $candidates = [];

    if (strpos($normalized, 'BEGIN ') !== false) {
        $candidates[] = $normalized;
    } else {
        $body = preg_replace('/\s+/', '', $normalized);
        if ($isPrivate) {
            $candidates[] = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----";
            $candidates[] = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END RSA PRIVATE KEY-----";
        } else {
            $candidates[] = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PUBLIC KEY-----";
        }
    }

    foreach ($candidates as $candidate) {
        $key = $isPrivate ? openssl_pkey_get_private($candidate) : openssl_pkey_get_public($candidate);
        if ($key !== false) {
            return $key;
        }
    }

    return false;
}
