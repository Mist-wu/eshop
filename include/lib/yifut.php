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

    $payload = [
        'method' => trim((string)emEnv('EM_YIFUT_CREATE_METHOD', 'jump')),
        'device' => yifutDetermineDevice(),
        'type' => $payType,
        'out_trade_no' => $orderInfo['out_trade_no'],
        'notify_url' => EM_URL . 'action/notify/' . $callbackPlugin,
        'return_url' => EM_URL . 'action/return/' . $callbackPlugin . '/' . rawurlencode($orderInfo['out_trade_no']),
        'name' => yifutBuildOrderName($orderInfo, $orderList),
        'money' => number_format(((float)$orderInfo['amount']) / 100, 2, '.', ''),
        'clientip' => getClientIP(),
        'param' => $orderInfo['out_trade_no'],
    ];

    $response = yifutRequest('/pay/create', $payload);
    if (!is_array($response) || (int)($response['code'] ?? -1) !== 0) {
        $message = trim((string)($response['msg'] ?? ''));
        if ($message === '') {
            $message = '易付通统一下单失败，请稍后重试';
        }
        emMsg($message);
    }

    $orderModel = new Order_Model();
    $tradeSnapshot = yifutBuildPaymentSnapshotFromRemoteOrder($response, $payType, $orderInfo['payment'] ?? '');
    $persist = [];
    foreach (['trade_no', 'api_trade_no', 'up_no'] as $field) {
        if (!empty($tradeSnapshot[$field])) {
            $persist[$field] = $tradeSnapshot[$field];
        }
    }
    if (!empty($persist)) {
        $orderModel->updateOrderInfo($orderInfo['out_trade_no'], $persist);
    }

    $payTypeValue = (string)($response['pay_type'] ?? '');
    $payInfo = (string)($response['pay_info'] ?? '');
    if ($payInfo === '') {
        emMsg('易付通返回的支付参数为空，请稍后重试');
    }

    if ($payTypeValue === 'html') {
        echo $payInfo;
        exit;
    }

    if ($payTypeValue !== '' && $payTypeValue !== 'jump') {
        Log::warning('易付通统一下单返回非 jump 模式：' . $payTypeValue . '，订单：' . $orderInfo['out_trade_no']);
    }

    header('location: ' . $payInfo);
    exit;
}

function yifutCheckSign($notifyType, $expectedType) {
    if (!yifutIsConfigured() || !extension_loaded('openssl')) {
        return false;
    }

    $params = array_merge($_POST ?? [], $_GET ?? []);
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

    if (!yifutVerifyPayloadSignature($params)) {
        Log::warning('易付通回调验签失败');
        return false;
    }

    $orderModel = new Order_Model();
    $order = $orderModel->getOrderInfoRaw((string)$params['out_trade_no'], true);
    if (!empty($order)) {
        $orderAmount = number_format(((int)($order['amount'] ?? 0)) / 100, 2, '.', '');
        if (!isset($params['money']) || emBcComp($orderAmount, (string)$params['money'], 2) !== 0) {
            Log::warning('易付通回调金额不匹配：' . $params['out_trade_no']);
            return false;
        }
    } else {
        Log::warning('易付通回调本地订单未命中，将进入异常补偿流程：' . $params['out_trade_no']);
    }

    $snapshot = yifutBuildPaymentSnapshotFromRemoteOrder($params, (string)($params['type'] ?? ''), !empty($order['payment']) ? (string)$order['payment'] : '');
    $snapshot['notify_type'] = (string)$notifyType;
    $snapshot['order_missing'] = empty($order);

    return $snapshot;
}

function yifutQueryOrder($criteria) {
    $payload = yifutBuildOrderLookupPayload($criteria);
    if (empty($payload)) {
        return [];
    }

    $response = yifutRequest('/pay/query', $payload);
    if (!is_array($response) || (int)($response['code'] ?? -1) !== 0) {
        return [];
    }

    return $response;
}

function yifutCloseOrder($criteria) {
    $payload = yifutBuildOrderLookupPayload($criteria);
    if (empty($payload)) {
        return [];
    }

    $response = yifutRequest('/pay/close', $payload);
    if (!is_array($response) || (int)($response['code'] ?? -1) !== 0) {
        return [];
    }

    return $response;
}

function yifutListMerchantOrders($offset = 0, $limit = 50, $status = null) {
    $limit = max(1, min(50, (int)$limit));
    $payload = [
        'offset' => max(0, (int)$offset),
        'limit' => $limit,
    ];
    if ($status !== null && $status !== '') {
        $payload['status'] = (int)$status;
    }

    $response = yifutRequest('/merchant/orders', $payload);
    if (!is_array($response) || (int)($response['code'] ?? -1) !== 0) {
        return [];
    }

    return $response;
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

function yifutDetermineDevice($userAgent = null) {
    $userAgent = strtolower((string)($userAgent !== null ? $userAgent : ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    if ($userAgent !== '') {
        if (strpos($userAgent, 'micromessenger') !== false) {
            return 'wechat';
        }
        if (strpos($userAgent, 'qq/') !== false || strpos($userAgent, 'qqbrowser') !== false) {
            return 'qq';
        }
        if (strpos($userAgent, 'alipayclient') !== false) {
            return 'alipay';
        }
    }

    return isMobile() ? 'mobile' : 'pc';
}

function yifutBuildOrderLookupPayload($criteria) {
    $criteria = is_array($criteria) ? $criteria : [];
    $payload = [];
    if (!empty($criteria['trade_no'])) {
        $payload['trade_no'] = (string)$criteria['trade_no'];
    } elseif (!empty($criteria['out_trade_no'])) {
        $payload['out_trade_no'] = (string)$criteria['out_trade_no'];
    } else {
        return [];
    }

    return $payload;
}

function yifutBuildPaymentSnapshotFromRemoteOrder($data, $payType = '', $fallbackPayment = '') {
    $data = is_array($data) ? $data : [];
    $payType = trim((string)($payType !== '' ? $payType : ($data['type'] ?? '')));
    $payment = trim((string)$fallbackPayment);
    if ($payment === '') {
        $payment = yifutPaymentLabel($payType);
    }

    $paidAt = yifutParsePaidTimestamp($data);
    $tradeNo = trim((string)($data['trade_no'] ?? ''));
    $apiTradeNo = trim((string)($data['api_trade_no'] ?? ''));
    $upNo = $apiTradeNo !== '' ? $apiTradeNo : $tradeNo;

    return [
        'out_trade_no' => trim((string)($data['out_trade_no'] ?? '')),
        'trade_no' => $tradeNo,
        'api_trade_no' => $apiTradeNo,
        'up_no' => $upNo,
        'payment' => $payment,
        'pay_time' => $paidAt,
        'type' => $payType,
    ];
}

function yifutPaymentLabel($payType) {
    $payType = strtolower(trim((string)$payType));
    if ($payType === 'wxpay') {
        return '微信支付';
    }
    if ($payType === 'alipay') {
        return '支付宝';
    }
    return '易付通支付';
}

function yifutParsePaidTimestamp($data) {
    $data = is_array($data) ? $data : [];
    $candidates = [
        $data['endtime'] ?? null,
        $data['timestamp'] ?? null,
        $data['addtime'] ?? null,
    ];

    foreach ($candidates as $value) {
        if ($value === null || $value === '') {
            continue;
        }
        if (is_numeric($value)) {
            $timestamp = (int)$value;
        } else {
            $timestamp = strtotime((string)$value);
        }
        if ($timestamp > 0) {
            return $timestamp;
        }
    }

    return time();
}

function yifutRequest($path, $payload) {
    if (!yifutIsConfigured()) {
        return ['code' => 1, 'msg' => '易付通支付参数未配置完整'];
    }
    if (!extension_loaded('openssl')) {
        return ['code' => 1, 'msg' => '服务器未启用 OpenSSL'];
    }

    $payload = yifutSignablePayload($payload);
    if ($payload['sign'] === '') {
        return ['code' => 1, 'msg' => '易付通请求签名失败'];
    }

    $url = rtrim(yifutApiBaseUrl(), '/') . '/' . ltrim($path, '/');
    $responseText = emCurl($url, http_build_query($payload), 1, false, 15);
    if ($responseText === false || $responseText === '') {
        return ['code' => 1, 'msg' => '易付通接口请求失败'];
    }

    $response = json_decode($responseText, true);
    if (!is_array($response)) {
        return ['code' => 1, 'msg' => '易付通接口返回格式错误'];
    }

    if (!empty($response['sign']) && !yifutVerifyPayloadSignature($response)) {
        Log::warning('易付通接口响应验签失败：' . $path);
        return ['code' => 1, 'msg' => '易付通接口响应验签失败'];
    }

    return $response;
}

function yifutSignablePayload($payload) {
    $payload = is_array($payload) ? $payload : [];
    $payload['pid'] = trim((string)emEnv('EM_YIFUT_PID', ''));
    if (empty($payload['timestamp'])) {
        $payload['timestamp'] = (string)time();
    }
    $payload['sign_type'] = 'RSA';

    if (!isset($payload['channel_id'])) {
        $channelId = trim((string)emEnv('EM_YIFUT_CHANNEL_ID', ''));
        if ($channelId !== '') {
            $payload['channel_id'] = $channelId;
        }
    }

    $payload['sign'] = yifutSign($payload);

    return $payload;
}

function yifutApiBaseUrl() {
    $configured = trim((string)emEnv('EM_YIFUT_API_BASE', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $legacyGateway = trim((string)emEnv('EM_YIFUT_GATEWAY', ''));
    if ($legacyGateway !== '' && preg_match('#^(https?://[^/]+)/api/pay/submit#i', $legacyGateway, $matches)) {
        return rtrim($matches[1], '/') . '/api';
    }

    return 'https://www.yifut.com/api';
}

function yifutVerifyPayloadSignature($payload) {
    $payload = is_array($payload) ? $payload : [];
    if (empty($payload['sign'])) {
        return false;
    }

    $sign = (string)$payload['sign'];
    $publicKey = yifutGetPublicKeyResource();
    if ($publicKey === false) {
        return false;
    }

    $signContent = yifutBuildSignContent($payload);
    $verified = openssl_verify($signContent, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    if (is_object($publicKey) || is_resource($publicKey)) {
        openssl_free_key($publicKey);
    }

    return $verified;
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
