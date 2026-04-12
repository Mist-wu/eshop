<?php
/**
 * control panel
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

function clearCacheDirectory($dir) {
    $entries = scandir($dir);
    if ($entries === false) {
        return false;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            if (!clearCacheDirectory($path) || !@rmdir($path)) {
                return false;
            }
            continue;
        }

        if (!@unlink($path)) {
            return false;
        }
    }

    return true;
}

if ($action === 'add_shortcut') {
    $shortcut = Input::postStrArray('shortcut');
    $shortcutSet = [];
    foreach ($shortcut as $item) {
        $item = explode('||', $item);
        $shortcutSet[] = [
            'name' => $item[0],
            'url'  => $item[1]
        ];
    }
    Option::updateOption('shortcut', json_encode($shortcutSet, JSON_UNESCAPED_UNICODE));
    $CACHE->updateCache('options');
    emDirect('./index.php?add_shortcut_suc=1');
}

if ($action === 'delete_cache') {
    $dir = EM_ROOT . '/content/cache';
    if (!is_dir($dir)) {
        Output::error('缓存目录不存在');
    }

    if (!clearCacheDirectory($dir)) {
        Output::error('清理缓存失败，请检查目录权限');
    }

    Output::ok();
}

if (empty($action)) {
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $br = '<a><cite>控制台</cite></a>';

    // 销售额
    $sql = "SELECT SUM(amount) AS today_sales_amount FROM `" . DB_PREFIX . "order` WHERE delete_time IS NULL AND pay_time IS NOT NULL AND create_time >= UNIX_TIMESTAMP(CURRENT_DATE) AND create_time < UNIX_TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY));";
    $row = $db->once_fetch_array($sql);
    $today_sales_amount = $row['today_sales_amount'] / 100;
    $sql = "SELECT SUM(amount) AS yesterday_sales_amount FROM `" . DB_PREFIX . "order` WHERE delete_time IS NULL AND pay_time IS NOT NULL AND create_time >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)) AND create_time < UNIX_TIMESTAMP(CURRENT_DATE);";
    $row = $db->once_fetch_array($sql);
    $yesterday_sales_amount = $row['yesterday_sales_amount'] / 100;
    $sql = "SELECT SUM(amount) AS current_month_sales_amount FROM `" . DB_PREFIX . "order` WHERE delete_time IS NULL AND pay_time IS NOT NULL AND create_time >= UNIX_TIMESTAMP(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')) AND create_time < UNIX_TIMESTAMP(DATE_ADD(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH));";
    $row = $db->once_fetch_array($sql);
    $current_month_sales_amount = number_format($row['current_month_sales_amount'] / 100, 2);
    // 用户数量
    $sql = <<<SQL
SELECT
    -- 今日注册量
    SUM(CASE WHEN FROM_UNIXTIME(create_time) >= CURDATE() 
             AND FROM_UNIXTIME(create_time) < CURDATE() + INTERVAL 1 DAY 
             THEN 1 ELSE 0 END) AS today_registrations,
             
    -- 昨日注册量
    SUM(CASE WHEN FROM_UNIXTIME(create_time) >= CURDATE() - INTERVAL 1 DAY 
             AND FROM_UNIXTIME(create_time) < CURDATE() 
             THEN 1 ELSE 0 END) AS yesterday_registrations,
             
    -- 本月注册量
    SUM(CASE WHEN FROM_UNIXTIME(create_time) >= DATE_FORMAT(CURDATE(), '%Y-%m-01') 
             AND FROM_UNIXTIME(create_time) < CURDATE() + INTERVAL 1 DAY 
             THEN 1 ELSE 0 END) AS month_registrations
             
FROM {$db_prefix}user;
SQL;
    $user_panel = $db->once_fetch_array($sql);

    // 订单数量
    $sql = <<<SQL
SELECT
    -- 今日有效订单量
    IFNULL(SUM(CASE WHEN create_time >= UNIX_TIMESTAMP(CURDATE()) 
             AND create_time < UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY) 
             AND pay_time IS NOT NULL AND delete_time IS NULL
             THEN 1 ELSE 0 END), 0) AS today_orders,
             
    -- 昨日有效订单量
    IFNULL(SUM(CASE WHEN create_time >= UNIX_TIMESTAMP(CURDATE() - INTERVAL 1 DAY) 
             AND create_time < UNIX_TIMESTAMP(CURDATE()) 
             AND pay_time IS NOT NULL AND delete_time IS NULL
             THEN 1 ELSE 0 END), 0) AS yesterday_orders,
             
    -- 本月有效订单量
    IFNULL(SUM(CASE WHEN create_time >= UNIX_TIMESTAMP(DATE_FORMAT(CURDATE(), '%Y-%m-01')) 
             AND create_time < UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY) 
             AND pay_time IS NOT NULL AND delete_time IS NULL
             THEN 1 ELSE 0 END), 0) AS month_orders
             
FROM {$db_prefix}order;
SQL;
    $order_panel = $db->once_fetch_array($sql);
//    d($order_panel);die;

    include View::getAdmView('header');
    require_once(View::getAdmView('templates/default/index/index'));
    include View::getAdmView('footer');
    View::output();
}

