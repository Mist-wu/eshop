<?php

declare(strict_types=1);

define('EM_ROOT', dirname(__DIR__));
define('DB_PREFIX', 'em_');
define('TIMESTAMP', 1710000000);

$GLOBALS['test_env'] = [];
$GLOBALS['test_actions'] = [];

function addAction($hook, $callback) {}

function doAction($hook, ...$args) {
    $GLOBALS['test_actions'][] = [$hook, $args];
}

function getClientIP() {
    return '127.0.0.1';
}

function emEnv($key, $default = '') {
    return array_key_exists($key, $GLOBALS['test_env']) ? $GLOBALS['test_env'][$key] : $default;
}

function emBcNormalize($number, $scale = 2) {
    return number_format((float)$number, (int)$scale, '.', '');
}

function emBcAdd($left, $right, $scale = 2) {
    return emBcNormalize((float)$left + (float)$right, $scale);
}

function emBcSub($left, $right, $scale = 2) {
    return emBcNormalize((float)$left - (float)$right, $scale);
}

function emBcMul($left, $right, $scale = 2) {
    return emBcNormalize((float)$left * (float)$right, $scale);
}

function emBcDiv($left, $right, $scale = 2) {
    if ((float)$right == 0.0) {
        return emBcNormalize(0, $scale);
    }
    return emBcNormalize((float)$left / (float)$right, $scale);
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

class Parsedown {
    public function setBreaksEnabled($enabled) {}
}

class Log {
    public static $messages = [];

    public static function info($message) {
        self::$messages[] = ['level' => 'info', 'message' => $message];
    }

    public static function warning($message) {
        self::$messages[] = ['level' => 'warning', 'message' => $message];
    }
}

class Database {
    private static $instance;
    private $lastInsertId = 0;

    public $adds = [];
    public $queries = [];
    public $transactions = [];
    public $couponRows = [];

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset() {
        self::$instance = new self();
        return self::$instance;
    }

    public function escape_string($value) {
        return addslashes((string)$value);
    }

    public function once_fetch_array($sql) {
        if (preg_match("/FROM `?em_coupon`? WHERE `?code`? = '([^']+)'/i", $sql, $matches)) {
            return $this->couponRows[$matches[1]] ?? null;
        }
        return [];
    }

    public function fetch_all($sql) {
        return [];
    }

    public function query($sql) {
        $this->queries[] = $sql;
        return $sql;
    }

    public function fetch_array($result) {
        return false;
    }

    public function add($table, $data) {
        $this->lastInsertId++;
        $this->adds[] = [
            'table' => $table,
            'data' => $data,
            'id' => $this->lastInsertId,
        ];
        return $this->lastInsertId;
    }

    public function insert_id() {
        return $this->lastInsertId;
    }

    public function beginTransaction() {
        $this->transactions[] = 'begin';
    }

    public function commit() {
        $this->transactions[] = 'commit';
    }

    public function rollback() {
        $this->transactions[] = 'rollback';
    }
}

class Goods_Model {
    public static $nextGoods = [];
    public static $calls = [];

    public function getOneGoodsForHome($goods_id, $user_id, $user_tier, $selected_sku, $quantity, $coupon_code = '') {
        self::$calls[] = [
            'goods_id' => $goods_id,
            'user_id' => $user_id,
            'user_tier' => $user_tier,
            'selected_sku' => $selected_sku,
            'quantity' => $quantity,
            'coupon_code' => $coupon_code,
        ];
        return self::$nextGoods;
    }
}

require_once EM_ROOT . '/include/model/coupon_model.php';
require_once EM_ROOT . '/include/model/order_model.php';

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

function testLastAddByTable($table) {
    $matches = array_values(array_filter(Database::getInstance()->adds, function ($row) use ($table) {
        return $row['table'] === $table;
    }));
    return empty($matches) ? null : $matches[count($matches) - 1];
}

function testDefaultGoods($overrides = []) {
    return array_replace_recursive([
        'id' => 42,
        'sort_id' => 3,
        'selected_sku_type' => 2,
        'price' => '16.50',
        'unit_price' => '5.50',
        'cost_price' => 120,
        'stock' => 8,
        'config' => [
            'input' => [],
        ],
        'skus' => [
            'option_name' => [
                [
                    'title' => '版本',
                    'sku_values' => [
                        [
                            'option_id' => '101',
                            'option_name' => 'Pro',
                        ],
                    ],
                ],
            ],
        ],
    ], $overrides);
}

function testReset() {
    Database::reset();
    Goods_Model::$nextGoods = testDefaultGoods();
    Goods_Model::$calls = [];
    Log::$messages = [];
    $GLOBALS['test_env'] = [];
    $GLOBALS['test_actions'] = [];
}

function testCase($name, callable $callback) {
    static $count = 0;
    $count++;
    testReset();
    $callback();
    echo '[OK] ' . $name . PHP_EOL;
    return $count;
}

$testsRun = 0;

$testsRun = testCase('checkout order uses refreshed server-side price and expiry floor', function () {
    $GLOBALS['test_env']['EM_ORDER_EXPIRE_SECONDS'] = '120';

    $model = new Order_Model();
    $result = $model->createOrder([
        'payment_plugin' => 'yifut_alipay',
        'payment_name' => '支付宝',
        'amount' => '999.99',
    ], 42, 3, 0, -1, ['input' => []], ['contact' => 'buyer@example.com', 'password' => 'order-pass'], ['101']);

    testAssertSame(0, $result['code'], 'Checkout should create an order');
    testAssertSame(42, Goods_Model::$calls[0]['goods_id'], 'Checkout should refresh goods by id');
    testAssertSame(3, Goods_Model::$calls[0]['quantity'], 'Checkout should refresh goods with requested quantity');

    $order = testLastAddByTable('order');
    $orderList = testLastAddByTable('order_list');

    testAssertSame(1650, $order['data']['amount'], 'Order amount should come from refreshed goods price');
    testAssertSame(1650, $order['data']['origin_amount'], 'Origin amount should match refreshed price without coupon');
    testAssertSame(TIMESTAMP + 300, $order['data']['expire_time'], 'Expire seconds below 300 should be floored');
    testAssertSame('buyer@example.com', $order['data']['contact'], 'Visitor contact should be stored');
    testAssertSame(550, $orderList['data']['unit_price'], 'Child order unit price should be refreshed server-side');
    testAssertSame(1650, $orderList['data']['price'], 'Child order total should match final amount');
    testAssertSame('版本：Pro；', $orderList['data']['attr_spec'], 'Selected SKU should be captured in order detail');
    testAssertSame(['begin', 'commit'], Database::getInstance()->transactions, 'Checkout write should commit exactly once');
});

$testsRun = testCase('checkout rejects incomplete sku selection before writing order rows', function () {
    Goods_Model::$nextGoods = testDefaultGoods([
        'selected_sku_type' => 1,
    ]);

    $model = new Order_Model();
    $result = $model->createOrder([
        'payment_plugin' => 'yifut_alipay',
        'payment_name' => '支付宝',
    ], 42, 1, 0, -1, ['input' => []], ['contact' => 'buyer@example.com'], ['101']);

    testAssertSame('400', $result['code'], 'Incomplete SKU should be rejected');
    testAssertSame('请完整选择规格', $result['msg'], 'Incomplete SKU message should be stable');
    testAssertSame([], Database::getInstance()->adds, 'Incomplete SKU should not write order rows');
    testAssertSame([], Database::getInstance()->transactions, 'Incomplete SKU should not open a transaction');
});

$testsRun = testCase('checkout applies coupon to refreshed amount and reserves coupon usage', function () {
    Goods_Model::$nextGoods = testDefaultGoods([
        'price' => '20.00',
        'unit_price' => '10.00',
    ]);

    Database::getInstance()->couponRows['SAVE10'] = [
        'id' => 7,
        'code' => 'SAVE10',
        'status' => 1,
        'end_time' => 0,
        'use_limit' => 0,
        'used_times' => 0,
        'type' => 'general',
        'threshold_type' => 'none',
        'discount_type' => 'percent',
        'discount_value' => '10',
    ];

    $model = new Order_Model();
    $result = $model->createOrder([
        'payment_plugin' => 'yifut_alipay',
        'payment_name' => '支付宝',
        'coupon_code' => 'save10',
    ], 42, 2, 0, -1, ['input' => []], ['contact' => 'buyer@example.com'], ['101']);

    testAssertSame(0, $result['code'], 'Coupon checkout should create an order');

    $order = testLastAddByTable('order');
    $usage = testLastAddByTable('coupon_usage');

    testAssertSame(1800, $order['data']['amount'], 'Final amount should subtract the refreshed coupon discount');
    testAssertSame(2000, $order['data']['origin_amount'], 'Origin amount should keep the refreshed pre-coupon amount');
    testAssertSame(200, $order['data']['coupon_amount'], 'Coupon amount should be stored in cents');
    testAssertSame(7, $order['data']['coupon_id'], 'Coupon id should be linked to the order');
    testAssertSame('SAVE10', $order['data']['coupon_code'], 'Coupon code should be normalized from the database row');
    testAssertSame(0, $usage['data']['status'], 'Coupon usage should be reserved until payment is confirmed');
    testAssertSame(2000, $usage['data']['amount_before'], 'Usage row should preserve pre-discount amount');
    testAssertSame(200, $usage['data']['discount_amount'], 'Usage row should preserve discount amount');

    $couponUpdate = implode("\n", Database::getInstance()->queries);
    testAssert(strpos($couponUpdate, 'used_times = used_times + 1') !== false, 'Coupon counter should be incremented on reservation');
});

echo 'All tests passed. Total: ' . $testsRun . PHP_EOL;
