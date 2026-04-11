<?php
defined('EM_ROOT') || exit('access denied!');

if(!User::isAdmin()) exit('access denied!');

$type = Input::getStrVar('type');

$db = Database::getInstance();
$db_prefix = DB_PREFIX;


if(empty($type)){
    $asql = <<<sql
SELECT 
  date_list.day,
  COALESCE(SUM(ol.price), 0) AS daily_sales  -- 空值转为0
FROM (
  -- 生成最近7天的所有日期（包含无数据的日期）
  SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS day UNION ALL
  SELECT CURDATE() AS day
) AS date_list
-- 左连接销售数据（确保所有日期都被保留）
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL  -- 只关联已支付的订单
LEFT JOIN {$db_prefix}order_list ol 
  ON o.id = ol.order_id
GROUP BY date_list.day  -- 按生成的日期分组
ORDER BY date_list.day ASC;
sql;


    $bsql = <<<sql
SELECT 
  date_list.day,
  -- 销售额总和 - 成本总和（空值用0填充）
  COALESCE(SUM(ol.price), 0) - COALESCE(SUM(ol.cost_price), 0) AS daily_profit
FROM (
  -- 生成最近7天的所有日期（包含无数据的日期）
  SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS day UNION ALL
  SELECT CURDATE() AS day
) AS date_list
-- 左连接订单表（关联日期和支付条件）
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL  -- 只关联已支付的订单
  AND o.pay_time >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)
  AND o.pay_time < UNIX_TIMESTAMP(NOW())
-- 左连接订单明细表（直接使用子订单的成本价）
LEFT JOIN {$db_prefix}order_list ol 
  ON o.id = ol.order_id
GROUP BY date_list.day  -- 按生成的日期分组
ORDER BY date_list.day ASC;  -- 按日期正序（最早的在前）
sql;

    $csql = <<<sql
SELECT 
  date_list.day,
  COALESCE(COUNT(DISTINCT o.id), 0) AS daily_order_count  -- 主订单数量，无数据则为0
FROM (
  -- 生成最近7天的完整日期列表（含今天）
  SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS day UNION ALL
  SELECT CURDATE() AS day
) AS date_list
-- 左连接订单表，匹配日期和已支付条件
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day  -- 日期匹配
  AND o.pay_time IS NOT NULL  -- 仅统计已支付订单
  AND o.pay_time >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)  -- 限制最近7天
  AND o.pay_time < UNIX_TIMESTAMP(NOW())
GROUP BY date_list.day  -- 按生成的日期分组（确保所有日期都显示）
ORDER BY date_list.day ASC;  -- 按日期正序排列（从最早到最近）
sql;

    $dsql = <<<sql
SELECT 
  date_list.day,
  COALESCE(COUNT(DISTINCT o.client_ip), 0) AS daily_user_count  -- 无数据时显示0
FROM (
  -- 生成最近7天的完整日期列表（含今天）
  SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS day UNION ALL
  SELECT CURDATE() AS day
) AS date_list
-- 左连接订单表，匹配日期和已支付条件
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day  -- 日期匹配
  AND o.pay_time IS NOT NULL  -- 仅统计已支付订单
  AND o.pay_time >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)  -- 限制最近7天
  AND o.pay_time < UNIX_TIMESTAMP(NOW())
GROUP BY date_list.day  -- 按生成的日期分组（确保所有日期都显示）
ORDER BY date_list.day ASC;
sql;

}

if($type == 'week'){
    // 销售额（本周每天，显示周一至周日）
    $asql = <<<sql
SELECT 
  -- 将日期转换为中文星期（周一至周日）
  CASE DATE_FORMAT(date_list.day, '%W')
    WHEN 'Monday' THEN '周一'
    WHEN 'Tuesday' THEN '周二'
    WHEN 'Wednesday' THEN '周三'
    WHEN 'Thursday' THEN '周四'
    WHEN 'Friday' THEN '周五'
    WHEN 'Saturday' THEN '周六'
    WHEN 'Sunday' THEN '周日'
  END AS day,
  COALESCE(SUM(ol.price), 0) AS daily_sales
FROM (
  -- 生成本周所有日期（周一到周日）
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS day UNION ALL  -- 本周一
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 1 DAY) AS day UNION ALL  -- 本周二
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 2 DAY) AS day UNION ALL  -- 本周三
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 3 DAY) AS day UNION ALL  -- 本周四
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 4 DAY) AS day UNION ALL  -- 本周五
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 5 DAY) AS day UNION ALL  -- 本周六
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY) AS day  -- 本周日
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND o.pay_time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY))
  AND o.pay_time < UNIX_TIMESTAMP(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY), INTERVAL 1 DAY))
LEFT JOIN {$db_prefix}order_list ol ON o.id = ol.order_id
GROUP BY date_list.day
ORDER BY date_list.day ASC;  -- 按周一到周日排序
sql;


    // 利润（本周每天，显示周一至周日）
    $bsql = <<<sql
SELECT 
  CASE DATE_FORMAT(date_list.day, '%W')
    WHEN 'Monday' THEN '周一'
    WHEN 'Tuesday' THEN '周二'
    WHEN 'Wednesday' THEN '周三'
    WHEN 'Thursday' THEN '周四'
    WHEN 'Friday' THEN '周五'
    WHEN 'Saturday' THEN '周六'
    WHEN 'Sunday' THEN '周日'
  END AS day,
  COALESCE(SUM(ol.price), 0) - COALESCE(SUM(ol.cost_price), 0) AS daily_profit
FROM (
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 1 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY) AS day
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND o.pay_time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY))
  AND o.pay_time < UNIX_TIMESTAMP(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY), INTERVAL 1 DAY))
LEFT JOIN {$db_prefix}order_list ol ON o.id = ol.order_id
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;

    // 订单数量（本周每天，显示周一至周日）
    $csql = <<<sql
SELECT 
  CASE DATE_FORMAT(date_list.day, '%W')
    WHEN 'Monday' THEN '周一'
    WHEN 'Tuesday' THEN '周二'
    WHEN 'Wednesday' THEN '周三'
    WHEN 'Thursday' THEN '周四'
    WHEN 'Friday' THEN '周五'
    WHEN 'Saturday' THEN '周六'
    WHEN 'Sunday' THEN '周日'
  END AS day,
  COALESCE(COUNT(DISTINCT o.id), 0) AS daily_order_count
FROM (
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 1 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY) AS day
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND o.pay_time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY))
  AND o.pay_time < UNIX_TIMESTAMP(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY), INTERVAL 1 DAY))
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;

    // 下单用户数（本周每天，显示周一至周日）
    $dsql = <<<sql
SELECT 
  CASE DATE_FORMAT(date_list.day, '%W')
    WHEN 'Monday' THEN '周一'
    WHEN 'Tuesday' THEN '周二'
    WHEN 'Wednesday' THEN '周三'
    WHEN 'Thursday' THEN '周四'
    WHEN 'Friday' THEN '周五'
    WHEN 'Saturday' THEN '周六'
    WHEN 'Sunday' THEN '周日'
  END AS day,
  COALESCE(COUNT(DISTINCT o.client_ip), 0) AS daily_user_count
FROM (
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 1 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 2 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 3 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 4 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 5 DAY) AS day UNION ALL
  SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY) AS day
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND o.pay_time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY))
  AND o.pay_time < UNIX_TIMESTAMP(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 6 DAY), INTERVAL 1 DAY))
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;

}

if($type == 'month'){  // 假设type为month时查询本月数据
    // 1. 本月每日销售额（无数据显示0）
    $asql = <<<sql
SELECT 
  date_list.day,
  COALESCE(SUM(ol.price), 0) AS daily_sales
FROM (
  -- 生成本月所有日期（自动适配28-31天）
  SELECT DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m-%d') AS day
  FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS b  -- 最多支持31天（10+3*10）
  WHERE DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  ORDER BY day ASC
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL  -- 已支付订单
  AND FROM_UNIXTIME(o.pay_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')  -- 限定本月
LEFT JOIN {$db_prefix}order_list ol 
  ON o.id = ol.order_id
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;


    // 2. 本月每日利润（无数据显示0）
    $bsql = <<<sql
SELECT 
  date_list.day,
  COALESCE(SUM(ol.price), 0) - COALESCE(SUM(ol.cost_price), 0) AS daily_profit
FROM (
  -- 生成本月所有日期
  SELECT DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m-%d') AS day
  FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS b
  WHERE DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  ORDER BY day ASC
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND FROM_UNIXTIME(o.pay_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
LEFT JOIN {$db_prefix}order_list ol 
  ON o.id = ol.order_id
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;


    // 3. 本月每日订单数（无数据显示0）
    $csql = <<<sql
SELECT 
  date_list.day,
  COALESCE(COUNT(DISTINCT o.id), 0) AS daily_order_count
FROM (
  -- 生成本月所有日期
  SELECT DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m-%d') AS day
  FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS b
  WHERE DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  ORDER BY day ASC
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND FROM_UNIXTIME(o.pay_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;


    // 4. 本月每日用户数（按client_ip去重，无数据显示0）
    $dsql = <<<sql
SELECT 
  date_list.day,
  COALESCE(COUNT(DISTINCT o.client_ip), 0) AS daily_user_count
FROM (
  -- 生成本月所有日期
  SELECT DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m-%d') AS day
  FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) AS b
  WHERE DATE_FORMAT(DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL (a.a + 10*b.a) DAY), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  ORDER BY day ASC
) AS date_list
LEFT JOIN {$db_prefix}order o 
  ON FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') = date_list.day
  AND o.pay_time IS NOT NULL
  AND FROM_UNIXTIME(o.pay_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
GROUP BY date_list.day
ORDER BY date_list.day ASC;
sql;

}

if($type == 'year'){
    // 本年度每月销售额
    $asql = <<<sql
SELECT 
  CONCAT(month_list.month, '月') AS day,  -- 显示“1月、2月”格式
  COALESCE(SUM(ol.price), 0) AS daily_sales
FROM (
  -- 生成本年度1-12月
  SELECT 1 AS month UNION ALL
  SELECT 2 AS month UNION ALL
  SELECT 3 AS month UNION ALL
  SELECT 4 AS month UNION ALL
  SELECT 5 AS month UNION ALL
  SELECT 6 AS month UNION ALL
  SELECT 7 AS month UNION ALL
  SELECT 8 AS month UNION ALL
  SELECT 9 AS month UNION ALL
  SELECT 10 AS month UNION ALL
  SELECT 11 AS month UNION ALL
  SELECT 12 AS month
) AS month_list
-- 左连接订单表（限定本年度已支付订单）
LEFT JOIN {$db_prefix}order o 
  ON MONTH(FROM_UNIXTIME(o.pay_time)) = month_list.month  -- 月份匹配
  AND YEAR(FROM_UNIXTIME(o.pay_time)) = YEAR(CURDATE())  -- 限定本年度
  AND o.pay_time IS NOT NULL  -- 已支付订单
LEFT JOIN {$db_prefix}order_list ol ON o.id = ol.order_id
GROUP BY month_list.month  -- 按月份分组
ORDER BY month_list.month ASC;  -- 按1-12月排序
sql;


    // 本年度每月利润
    $bsql = <<<sql
SELECT 
  CONCAT(month_list.month, '月') AS day,
  COALESCE(SUM(ol.price), 0) - COALESCE(SUM(ol.cost_price), 0) AS daily_profit
FROM (
  SELECT 1 AS month UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
  SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
  SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
  SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) AS month_list
LEFT JOIN {$db_prefix}order o 
  ON MONTH(FROM_UNIXTIME(o.pay_time)) = month_list.month
  AND YEAR(FROM_UNIXTIME(o.pay_time)) = YEAR(CURDATE())
  AND o.pay_time IS NOT NULL
LEFT JOIN {$db_prefix}order_list ol ON o.id = ol.order_id
GROUP BY month_list.month
ORDER BY month_list.month ASC;
sql;


    // 本年度每月订单数量（主订单）
    $csql = <<<sql
SELECT 
  CONCAT(month_list.month, '月') AS day,
  COALESCE(COUNT(DISTINCT o.id), 0) AS daily_order_count
FROM (
  SELECT 1 AS month UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
  SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
  SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
  SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) AS month_list
LEFT JOIN {$db_prefix}order o 
  ON MONTH(FROM_UNIXTIME(o.pay_time)) = month_list.month
  AND YEAR(FROM_UNIXTIME(o.pay_time)) = YEAR(CURDATE())
  AND o.pay_time IS NOT NULL
GROUP BY month_list.month
ORDER BY month_list.month ASC;
sql;


    // 本年度每月下单用户数（IP去重）
    $dsql = <<<sql
SELECT 
  CONCAT(month_list.month, '月') AS day,
  COALESCE(COUNT(DISTINCT o.client_ip), 0) AS daily_user_count
FROM (
  SELECT 1 AS month UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
  SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
  SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
  SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) AS month_list
LEFT JOIN {$db_prefix}order o 
  ON MONTH(FROM_UNIXTIME(o.pay_time)) = month_list.month
  AND YEAR(FROM_UNIXTIME(o.pay_time)) = YEAR(CURDATE())
  AND o.pay_time IS NOT NULL
GROUP BY month_list.month
ORDER BY month_list.month ASC;
sql;

}

$a = $db->fetch_all($asql);
$b = $db->fetch_all($bsql);
$c = $db->fetch_all($csql);
$d = $db->fetch_all($dsql);

//d($a);die;

$oneTitle = [];
$oneValue = [];
foreach($a as $val){
    $oneTitle[] = $val['day'];
    $twoTitle[] = $val['day'];
    $oneValue[0][] = $val['daily_sales'] / 100;
}
foreach($b as $val){
    $oneValue[1][] = $val['daily_profit'] / 100;
}
foreach($c as $val){
    $twoValue[0][] = $val['daily_order_count'];
}
foreach($d as $val){
    $twoValue[1][] = $val['daily_user_count'];
}

output::data([
    'oneTitle' => $oneTitle,
    'oneValue' => $oneValue,
    'twoValue' => $twoValue,
]);

d($a);die;

?>
