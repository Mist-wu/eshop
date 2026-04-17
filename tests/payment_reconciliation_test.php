<?php

declare(strict_types=1);

define('EM_ROOT', dirname(__DIR__));
define('EM_URL', 'https://example.test/');
define('DB_PREFIX', 'em_');
define('TIMESTAMP', 1710000000);
define('ISLOGIN', false);
define('UID', 0);

$GLOBALS['test_env'] = [];
$GLOBALS['test_gateway_calls'] = [];
$GLOBALS['test_gateway_handlers'] = [];
$GLOBALS['test_db_orders'] = [];
$GLOBALS['test_logs'] = [];
$GLOBALS['test_cleanup_queries'] = 0;

function addAction($hook, $callback) {}
function doAction(...$args) {}
function getUA() { return $_SERVER['HTTP_USER_AGENT'] ?? ''; }
function getIp() { return '127.0.0.1'; }
function emMsg($msg, $url = '') {
    throw new RuntimeException($url === '' ? $msg : $msg . ' | ' . $url);
}

function emEnv($key, $default = '') {
    return array_key_exists($key, $GLOBALS['test_env']) ? $GLOBALS['test_env'][$key] : $default;
}

function emEnvBool($key, $default = false) {
    $value = emEnv($key, $default);
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string)$value));
    if (in_array($normalized, ['1', 'true', 'yes', 'on', 'y'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off', 'n', ''], true)) {
        return false;
    }

    return (bool)$value;
}

function emBcComp($left, $right, $scale = 2) {
    $factor = pow(10, (int)$scale);
    $leftScaled = (int)round((float)$left * $factor);
    $rightScaled = (int)round((float)$right * $factor);
    if ($leftScaled === $rightScaled) {
        return 0;
    }
    return $leftScaled > $rightScaled ? 1 : -1;
}

function isMobile() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    return strpos($ua, 'iphone') !== false
        || (strpos($ua, 'android') !== false && strpos($ua, 'tablet') === false)
        || strpos($ua, 'mobile') !== false;
}

function orderStatusText($status) {
    $text = '未知状态';
    if ($status == 0) $text = '未支付';
    if ($status == 1) $text = '待发货';
    if ($status == 2) $text = '已完成';
    if ($status == -1) $text = '部分发货';
    if ($status == -2) $text = '已取消';
    if ($status == -3) $text = '已过期';
    return $text;
}

function orderPaymentReferenceList($order) {
    $order = is_array($order) ? $order : [];
    $refs = [];
    if (!empty($order['out_trade_no'])) {
        $refs['站内订单号'] = (string)$order['out_trade_no'];
    }
    if (!empty($order['trade_no'])) {
        $refs['易付通订单号'] = (string)$order['trade_no'];
    }
    if (!empty($order['api_trade_no'])) {
        $refs['渠道订单号'] = (string)$order['api_trade_no'];
    } elseif (!empty($order['up_no']) && (($order['trade_no'] ?? '') !== ($order['up_no'] ?? ''))) {
        $refs['支付单号'] = (string)$order['up_no'];
    }
    return $refs;
}

function testBuildSignContent($params) {
    $filtered = [];
    foreach ((array)$params as $key => $value) {
        if ($key === 'sign' || $key === 'sign_type') {
            continue;
        }
        if (is_array($value) || is_object($value) || $value === null || $value === '') {
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

function testSignPayloadWithKey($payload, $privateKey) {
    $signature = '';
    $ok = openssl_sign(testBuildSignContent($payload), $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if ($ok !== true) {
        throw new RuntimeException('Unable to sign payload');
    }
    return base64_encode($signature);
}

function emCurl($url, $postData = '', $isPost = 1, $json = false, $timeout = 15) {
    parse_str((string)$postData, $request);
    $GLOBALS['test_gateway_calls'][] = [
        'url' => $url,
        'request' => $request,
    ];

    $merchantPublicKey = $GLOBALS['test_merchant_public_key'] ?? '';
    if ($merchantPublicKey !== '') {
        $verified = openssl_verify(
            testBuildSignContent($request),
            base64_decode((string)($request['sign'] ?? ''), true) ?: '',
            $merchantPublicKey,
            OPENSSL_ALGO_SHA256
        );
        if ($verified !== 1) {
            throw new RuntimeException('Request signature verification failed');
        }
    }

    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $handler = $GLOBALS['test_gateway_handlers'][$path] ?? null;
    if (!is_callable($handler)) {
        return json_encode(['code' => 404, 'msg' => 'No handler for ' . $path], JSON_UNESCAPED_UNICODE);
    }

    $response = $handler($request);
    if (!is_array($response)) {
        throw new RuntimeException('Gateway handler must return an array');
    }

    if (empty($response['_unsigned'])) {
        $response['sign'] = testSignPayloadWithKey($response, $GLOBALS['test_platform_private_key']);
        $response['sign_type'] = 'RSA';
    } else {
        unset($response['_unsigned']);
    }

    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

class Log {
    public static function info($message) {
        $GLOBALS['test_logs'][] = ['level' => 'info', 'message' => $message];
    }

    public static function warning($message) {
        $GLOBALS['test_logs'][] = ['level' => 'warning', 'message' => $message];
    }
}

class Parsedown {
    public function setBreaksEnabled($enabled) {}
}

class Database {
    private static $instance;
    private $lastResult = [];
    private $affectedRows = 0;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function escape_string($value) {
        return addslashes((string)$value);
    }

    public function fetch_all($sql) {
        if (stripos($sql, 'SELECT id, out_trade_no, pay_plugin FROM') !== false) {
            $GLOBALS['test_cleanup_queries']++;
            return [];
        }
        return [];
    }

    public function once_fetch_array($sql) {
        $result = $this->matchOrderQuery($sql);
        return empty($result) ? [] : $result;
    }

    public function query($sql) {
        $this->affectedRows = 0;
        $this->lastResult = [];

        if (stripos($sql, 'SELECT * FROM') === 0) {
            $row = $this->matchOrderQuery($sql);
            $this->lastResult = empty($row) ? [] : [$row];
        }

        return $sql;
    }

    public function fetch_array($result) {
        if (empty($this->lastResult)) {
            return false;
        }
        return array_shift($this->lastResult);
    }

    public function affected_rows() {
        return $this->affectedRows;
    }

    public function beginTransaction() {}
    public function commit() {}
    public function rollback() {}
    public function add($table, $data) {}
    public function insert_id() { return 0; }
    public function num_rows($res) { return 0; }

    private function matchOrderQuery($sql) {
        if (preg_match("/out_trade_no\\s*=\\s*'([^']+)'/i", $sql, $matches)) {
            return $GLOBALS['test_db_orders'][$matches[1]] ?? [];
        }
        if (preg_match('/\\bid\\s*=\\s*(\\d+)/i', $sql, $matches)) {
            foreach ($GLOBALS['test_db_orders'] as $row) {
                if ((int)($row['id'] ?? 0) === (int)$matches[1]) {
                    return $row;
                }
            }
        }
        return [];
    }
}

class Option {
    public static function get($key) {
        return null;
    }
}

class Url {
    public static function goods($goodsId) {
        return '/goods/' . $goodsId;
    }

    public static function log($goodsId) {
        return '/goods/' . $goodsId;
    }
}

require_once EM_ROOT . '/include/model/order_model.php';
require_once EM_ROOT . '/include/lib/yifut.php';
require_once EM_ROOT . '/include/controller/pay_controller.php';

class TestableOrderModel extends Order_Model {
    public $orders = [];
    public $couponConfirmed = [];
    public $deliverCalls = [];

    public function __construct($bootstrapBase = false) {
        if ($bootstrapBase) {
            parent::__construct();
        }
    }

    public function getOrderInfoRaw($out_trade_no, $includeDeleted = false) {
        return $this->orders[$out_trade_no] ?? [];
    }

    public function getOrderInfoId($id, $includeDeleted = false) {
        foreach ($this->orders as $order) {
            if ((int)($order['id'] ?? 0) === (int)$id) {
                return $order;
            }
        }
        return [];
    }

    public function getOrderByReference($reference, $includeDeleted = false) {
        foreach ($this->orders as $order) {
            foreach (['out_trade_no', 'up_no', 'trade_no', 'api_trade_no'] as $field) {
                if (($order[$field] ?? '') === $reference) {
                    return $order;
                }
            }
        }
        return [];
    }

    public function markOrderPaid($order_id, $data = [], $options = []) {
        foreach ($this->orders as &$order) {
            if ((int)($order['id'] ?? 0) !== (int)$order_id) {
                continue;
            }
            if ((int)($order['pay_status'] ?? 0) === 1) {
                return false;
            }
            foreach ($data as $key => $value) {
                $order[$key] = $value;
            }
            if (!isset($data['pay_status'])) {
                $order['pay_status'] = 1;
            }
            return true;
        }
        return false;
    }

    public function updateOrderInfoById($order_id, $data) {
        foreach ($this->orders as &$order) {
            if ((int)($order['id'] ?? 0) !== (int)$order_id) {
                continue;
            }
            foreach ($data as $key => $value) {
                $order[$key] = $value;
            }
            return true;
        }
        return false;
    }

    public function confirmCouponUsage($order_id) {
        $this->couponConfirmed[] = (int)$order_id;
        return 1;
    }

    public function deliver($order_id) {
        $this->deliverCalls[] = (int)$order_id;
        foreach ($this->orders as &$order) {
            if ((int)($order['id'] ?? 0) === (int)$order_id) {
                $order['status'] = 2;
            }
        }
        return ['code' => 200, 'content' => ['ok']];
    }

    public function buildUpdateAssignmentsPublic($data) {
        return $this->buildUpdateAssignments($data);
    }
}

final class CallbackTrackingOrderModel extends TestableOrderModel {
    public $forcedProcessResult = null;
    public $reconcileCalls = [];

    public function processConfirmedPayment($out_trade_no, $paymentData = [], $options = []) {
        if ($this->forcedProcessResult !== null) {
            return $this->forcedProcessResult;
        }
        return parent::processConfirmedPayment($out_trade_no, $paymentData, $options);
    }

    public function reconcileOrderPayment($reference) {
        $this->reconcileCalls[] = (string)$reference;
        return ['ok' => true, 'msg' => 'unexpected reconcile'];
    }
}

final class TestablePayController extends Pay_Controller {
    private $orderModel;

    public function __construct($orderModel) {
        $this->orderModel = $orderModel;
    }

    protected function createOrderModel() {
        return $this->orderModel;
    }

    public function processPaymentCallbackPublic($checkSign, $source, $options = []) {
        return $this->processPaymentCallback($checkSign, $source, $options);
    }
}

function testGenerateKeyPair() {
    $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    if ($resource === false) {
        throw new RuntimeException('Unable to generate RSA key pair');
    }

    $privateKey = '';
    if (!openssl_pkey_export($resource, $privateKey)) {
        throw new RuntimeException('Unable to export private key');
    }

    $details = openssl_pkey_get_details($resource);
    if (!is_array($details) || empty($details['key'])) {
        throw new RuntimeException('Unable to export public key');
    }

    return [
        'private' => $privateKey,
        'public' => $details['key'],
    ];
}

function testResetGateway() {
    $GLOBALS['test_gateway_calls'] = [];
    $GLOBALS['test_gateway_handlers'] = [];
    $GLOBALS['test_db_orders'] = [];
    $GLOBALS['test_logs'] = [];
    $GLOBALS['test_cleanup_queries'] = 0;
    $GLOBALS['test_env'] = $GLOBALS['test_env_base'];
    $_GET = [];
    $_POST = [];
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
    $_SERVER['HTTP_ACCEPT'] = 'text/html';
}

function testAssert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testAssertSame($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' | expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
    }
}

function testAssertCount($expected, $actual, $message) {
    $count = is_countable($actual) ? count($actual) : 0;
    if ($expected !== $count) {
        throw new RuntimeException($message . ' | expected_count=' . $expected . ' actual_count=' . $count);
    }
}

function testCase($name, callable $callback) {
    static $count = 0;
    $count++;
    testResetGateway();
    $callback();
    echo '[OK] ' . $name . PHP_EOL;
    return $count;
}

$merchantKeys = testGenerateKeyPair();
$platformKeys = testGenerateKeyPair();
$GLOBALS['test_merchant_public_key'] = $merchantKeys['public'];
$GLOBALS['test_platform_private_key'] = $platformKeys['private'];
$GLOBALS['test_env'] = [
    'EM_YIFUT_ENABLE' => '1',
    'EM_YIFUT_PID' => '10001',
    'EM_YIFUT_PRIVATE_KEY' => $merchantKeys['private'],
    'EM_YIFUT_PUBLIC_KEY' => $platformKeys['public'],
];
$GLOBALS['test_env_base'] = $GLOBALS['test_env'];

$testsRun = 0;

$testsRun = testCase('order status text and payment reference labels', function () {
    testAssertSame('已过期', orderStatusText(-3), 'Expired status text should be exposed');

    $refs = orderPaymentReferenceList([
        'out_trade_no' => 'LOCAL123',
        'trade_no' => 'YIFUT456',
        'api_trade_no' => 'CHANNEL789',
    ]);
    testAssertSame([
        '站内订单号' => 'LOCAL123',
        '易付通订单号' => 'YIFUT456',
        '渠道订单号' => 'CHANNEL789',
    ], $refs, 'Payment reference list should preserve all identifiers');
});

$testsRun = testCase('build update assignments keeps null bool number and escaped string semantics', function () {
    $model = new TestableOrderModel(true);
    $sql = $model->buildUpdateAssignmentsPublic([
        'delete_time' => null,
        'pay_status' => true,
        'pay_time' => 123456,
        'payment' => "O'Reilly",
    ]);

    testAssert(strpos($sql, 'delete_time=NULL') !== false, 'Null should map to SQL NULL');
    testAssert(strpos($sql, 'pay_status=1') !== false, 'Bool true should map to 1');
    testAssert(strpos($sql, 'pay_time=123456') !== false, 'Numbers should stay unquoted');
    testAssert(strpos($sql, "payment='O\\'Reilly'") !== false, 'Strings should be escaped and quoted');
});

$testsRun = testCase('order model bootstrap no longer scans expired orders eagerly', function () {
    new TestableOrderModel(true);
    testAssertSame(0, $GLOBALS['test_cleanup_queries'], 'Order model constructor should stay lightweight');
});

$testsRun = testCase('yifut helper functions normalize device lookup payload and snapshots', function () {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) MicroMessenger';
    testAssertSame('wechat', yifutDetermineDevice(), 'WeChat UA should map to wechat');

    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14) AlipayClient';
    testAssertSame('alipay', yifutDetermineDevice(), 'Alipay UA should map to alipay');

    $lookup = yifutBuildOrderLookupPayload([
        'trade_no' => 'TRADE-1',
        'out_trade_no' => 'LOCAL-1',
    ]);
    testAssertSame(['trade_no' => 'TRADE-1'], $lookup, 'Trade number should take precedence when both are available');

    $snapshot = yifutBuildPaymentSnapshotFromRemoteOrder([
        'out_trade_no' => 'LOCAL-1',
        'trade_no' => 'YFT-1',
        'api_trade_no' => 'ALI-1',
        'endtime' => '2026-04-15 09:23:19',
        'type' => 'alipay',
    ]);
    testAssertSame('支付宝', $snapshot['payment'], 'Alipay type should map to localized payment label');
    testAssertSame('ALI-1', $snapshot['up_no'], 'Channel trade number should be preferred as up_no');
    testAssert($snapshot['pay_time'] > 0, 'Paid timestamp should be parsed');
});

$testsRun = testCase('yifut api base url and signature helpers follow documented rules', function () {
    $GLOBALS['test_env']['EM_YIFUT_API_BASE'] = 'https://gateway.example.com/api/';
    testAssertSame('https://gateway.example.com/api', yifutApiBaseUrl(), 'Explicit API base should win');

    unset($GLOBALS['test_env']['EM_YIFUT_API_BASE']);
    $GLOBALS['test_env']['EM_YIFUT_GATEWAY'] = 'https://legacy.example.com/api/pay/submit';
    testAssertSame('https://legacy.example.com/api', yifutApiBaseUrl(), 'Legacy submit gateway should be normalized to /api');
    unset($GLOBALS['test_env']['EM_YIFUT_GATEWAY']);

    $content = yifutBuildSignContent([
        'money' => '20.00',
        'sign' => 'ignored',
        'empty' => '',
        'sign_type' => 'RSA',
        'out_trade_no' => 'LOCAL-2',
        'pid' => '10001',
    ]);
    testAssertSame('money=20.00&out_trade_no=LOCAL-2&pid=10001', $content, 'Sign content should sort keys and skip sign/sign_type/empty values');

    $payload = yifutSignablePayload([
        'out_trade_no' => 'LOCAL-2',
        'money' => '20.00',
    ]);
    testAssertSame('10001', $payload['pid'], 'Merchant pid should be injected');
    testAssertSame('RSA', $payload['sign_type'], 'Sign type should be RSA');
    testAssert(!empty($payload['sign']), 'Request should be signed');
    testAssert(yifutVerifyPayloadSignature([
        'out_trade_no' => 'LOCAL-2',
        'trade_no' => 'YFT-2',
        'sign' => testSignPayloadWithKey([
            'out_trade_no' => 'LOCAL-2',
            'trade_no' => 'YFT-2',
        ], $GLOBALS['test_platform_private_key']),
    ]), 'Platform-signed payload should verify successfully');
});

$testsRun = testCase('callback verification accepts signed late payment payloads from get and post', function () {
    $GLOBALS['test_db_orders'] = [
        'LOCAL-3' => [
            'id' => 3,
            'out_trade_no' => 'LOCAL-3',
            'amount' => 2000,
            'payment' => '支付宝',
        ],
    ];

    $params = [
        'pid' => '10001',
        'out_trade_no' => 'LOCAL-3',
        'trade_no' => 'YFT-3',
        'api_trade_no' => 'ALI-3',
        'type' => 'alipay',
        'money' => '20.00',
        'trade_status' => 'TRADE_SUCCESS',
        'timestamp' => '2026-04-15 09:23:19',
    ];
    $params['sign'] = testSignPayloadWithKey($params, $GLOBALS['test_platform_private_key']);
    $params['sign_type'] = 'RSA';

    $_POST = $params;
    $snapshot = yifutCheckSign('notify', 'alipay');

    testAssertSame('LOCAL-3', $snapshot['out_trade_no'], 'Callback snapshot should contain local order number');
    testAssertSame('YFT-3', $snapshot['trade_no'], 'Callback snapshot should contain upstream order number');
    testAssertSame('ALI-3', $snapshot['api_trade_no'], 'Callback snapshot should contain channel order number');
    testAssertSame(false, $snapshot['order_missing'], 'Existing local order should not be marked missing');
});

$testsRun = testCase('process confirmed payment recovers expired unpaid orders and avoids duplicate delivery', function () {
    $model = new TestableOrderModel();
    $model->orders['LOCAL-4'] = [
        'id' => 4,
        'out_trade_no' => 'LOCAL-4',
        'status' => -3,
        'pay_status' => 0,
        'delete_time' => 1710000100,
        'payment' => '支付宝',
        'trade_no' => '',
        'api_trade_no' => '',
        'up_no' => '',
    ];

    $result = $model->processConfirmedPayment('LOCAL-4', [
        'pay_time' => 1710000200,
        'payment' => '支付宝',
        'trade_no' => 'YFT-4',
        'api_trade_no' => 'ALI-4',
        'up_no' => 'ALI-4',
    ], ['source' => 'test']);

    testAssert(!empty($result['ok']), 'Expired unpaid order should be recoverable');
    testAssertSame(1, $model->orders['LOCAL-4']['pay_status'], 'Recovered order should be marked paid');
    testAssertSame(null, $model->orders['LOCAL-4']['delete_time'], 'Recovered order should clear delete_time');
    testAssertSame('YFT-4', $model->orders['LOCAL-4']['trade_no'], 'Recovered order should persist upstream trade number');
    testAssertSame([4], $model->couponConfirmed, 'Coupon usage should be confirmed exactly once');
    testAssertSame([4], $model->deliverCalls, 'Recovered unpaid order should trigger delivery exactly once');

    $model->deliverCalls = [];
    $model->orders['LOCAL-5'] = [
        'id' => 5,
        'out_trade_no' => 'LOCAL-5',
        'status' => 2,
        'pay_status' => 1,
        'delete_time' => null,
        'payment' => '支付宝',
        'trade_no' => 'YFT-5',
        'api_trade_no' => '',
        'up_no' => 'YFT-5',
    ];

    $second = $model->processConfirmedPayment('LOCAL-5', [
        'api_trade_no' => 'ALI-5',
        'up_no' => 'ALI-5',
    ], ['source' => 'test_repeat']);

    testAssert(!empty($second['ok']), 'Already paid order should still accept identifier patching');
    testAssertSame('ALI-5', $model->orders['LOCAL-5']['api_trade_no'], 'Already paid order should patch missing channel trade number');
    testAssertCount(0, $model->deliverCalls, 'Already delivered order should not deliver again');

    $model->deliverCalls = [];
    $model->orders['LOCAL-5P'] = [
        'id' => 51,
        'out_trade_no' => 'LOCAL-5P',
        'status' => -1,
        'pay_status' => 1,
        'delete_time' => null,
        'payment' => '支付宝',
        'trade_no' => 'YFT-5P',
        'api_trade_no' => '',
        'up_no' => 'YFT-5P',
    ];

    $partial = $model->processConfirmedPayment('LOCAL-5P', [
        'api_trade_no' => 'ALI-5P',
        'up_no' => 'ALI-5P',
    ], ['source' => 'partial_guard']);

    testAssert(!empty($partial['ok']), 'Partially delivered order should still accept identifier patching');
    testAssertSame(-1, $model->orders['LOCAL-5P']['status'], 'Partially delivered order should keep its partial status');
    testAssertSame('ALI-5P', $model->orders['LOCAL-5P']['api_trade_no'], 'Partially delivered order should patch missing identifiers');
    testAssertCount(0, $model->deliverCalls, 'Partially delivered order must not be fulfilled again');
});

$testsRun = testCase('payment callback failure no longer retries active reconcile in the same request', function () {
    $model = new CallbackTrackingOrderModel();
    $model->forcedProcessResult = ['ok' => false, 'msg' => 'deliver failed'];
    $controller = new TestablePayController($model);

    $result = $controller->processPaymentCallbackPublic([
        'out_trade_no' => 'LOCAL-CB-1',
        'trade_no' => 'YFT-CB-1',
    ], 'notify');

    testAssertSame(false, $result['ok'], 'Callback failure should be returned to the caller');
    testAssertCount(0, $model->reconcileCalls, 'Callback failure should not immediately trigger a second reconcile request');
});

$testsRun = testCase('manual reconcile and recent paid reconcile use yifut query interfaces to fix local state', function () {
    $model = new TestableOrderModel();
    $model->orders['LOCAL-6'] = [
        'id' => 6,
        'out_trade_no' => 'LOCAL-6',
        'trade_no' => 'YFT-6',
        'api_trade_no' => '',
        'up_no' => '',
        'payment' => '支付宝',
        'pay_plugin' => 'yifut_alipay',
        'pay_status' => 0,
        'status' => -3,
        'delete_time' => 1710000300,
        'expire_time' => 1710000200,
    ];
    $model->orders['LOCAL-7'] = [
        'id' => 7,
        'out_trade_no' => 'LOCAL-7',
        'trade_no' => '',
        'api_trade_no' => '',
        'up_no' => '',
        'payment' => '支付宝',
        'pay_plugin' => 'yifut_alipay',
        'pay_status' => 0,
        'status' => -3,
        'delete_time' => 1710000300,
        'expire_time' => 1710000200,
    ];
    $model->orders['LOCAL-8'] = [
        'id' => 8,
        'out_trade_no' => 'LOCAL-8',
        'trade_no' => 'YFT-8',
        'api_trade_no' => 'ALI-8',
        'up_no' => 'ALI-8',
        'payment' => '支付宝',
        'pay_plugin' => 'yifut_alipay',
        'pay_status' => 1,
        'status' => 2,
        'delete_time' => null,
    ];

    $GLOBALS['test_gateway_handlers']['/api/pay/query'] = function ($request) {
        if (($request['trade_no'] ?? '') === 'YFT-6') {
            return [
                'code' => 0,
                'msg' => 'success',
                'out_trade_no' => 'LOCAL-6',
                'trade_no' => 'YFT-6',
                'api_trade_no' => 'ALI-6',
                'status' => 1,
                'type' => 'alipay',
                'endtime' => '2026-04-15 09:23:19',
            ];
        }

        return [
            'code' => 0,
            'msg' => 'success',
            'out_trade_no' => $request['out_trade_no'] ?? '',
            'trade_no' => 'YFT-UNPAID',
            'status' => 0,
            'type' => 'alipay',
        ];
    };

    $GLOBALS['test_gateway_handlers']['/api/pay/close'] = function ($request) {
        return [
            'code' => 0,
            'msg' => 'closed',
            'out_trade_no' => $request['out_trade_no'] ?? '',
            'trade_no' => $request['trade_no'] ?? '',
        ];
    };

    $GLOBALS['test_gateway_handlers']['/api/merchant/orders'] = function ($request) {
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                [
                    'out_trade_no' => 'LOCAL-7',
                    'trade_no' => 'YFT-7',
                    'api_trade_no' => 'ALI-7',
                    'status' => 1,
                    'type' => 'alipay',
                    'endtime' => '2026-04-15 10:05:51',
                ],
                [
                    'out_trade_no' => 'LOCAL-8',
                    'trade_no' => 'YFT-8',
                    'api_trade_no' => 'ALI-8',
                    'status' => 1,
                    'type' => 'alipay',
                    'endtime' => '2026-04-15 10:10:00',
                ],
            ],
        ];
    };

    $manual = $model->reconcileOrderPayment('LOCAL-6');
    testAssert(!empty($manual['ok']), 'Manual reconcile should fix a remotely paid order');
    testAssertSame(1, $model->orders['LOCAL-6']['pay_status'], 'Manual reconcile should mark the order paid');
    testAssertSame('ALI-6', $model->orders['LOCAL-6']['api_trade_no'], 'Manual reconcile should persist the channel trade number');

    $recent = $model->reconcileRecentYifutPaidOrders(20, 0);
    testAssert(!empty($recent['ok']), 'Recent paid reconcile should succeed');
    testAssertSame(2, $recent['checked'], 'Recent paid reconcile should inspect all returned rows');
    testAssertSame(1, $recent['fixed'], 'Recent paid reconcile should only fix unhealthy local orders');
    testAssertSame(1, $recent['skipped'], 'Recent paid reconcile should skip already healthy orders');
    testAssertSame(1, $model->orders['LOCAL-7']['pay_status'], 'Recent paid reconcile should recover the second order');
    testAssertSame('YFT-7', $model->orders['LOCAL-7']['trade_no'], 'Recent paid reconcile should store upstream trade number');
    testAssert(count($GLOBALS['test_gateway_calls']) >= 2, 'Gateway integration should have been exercised');
});

echo 'All tests passed. Total: ' . $testsRun . PHP_EOL;
