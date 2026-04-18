<?php
defined('EM_ROOT') || exit('access denied!');

$action = Input::getStrVar('action');
if ($action === 'download') {
    $out_trade_no = Input::getStrVar('out_trade_no');

    if (empty($out_trade_no)) {
        die('订单号为空');
    }

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $out_trade_no_sql = $db->escape_string($out_trade_no);
    $order = $db->once_fetch_array("SELECT * FROM {$db_prefix}order WHERE out_trade_no = '{$out_trade_no_sql}'");

    if (empty($order)) {
        die('订单不存在');
    }

    $child_order = $db->once_fetch_array("SELECT * FROM {$db_prefix}order_list WHERE order_id = {$order['id']}");
    if (empty($child_order)) {
        die('子订单不存在');
    }

    $goods = $db->once_fetch_array("SELECT * FROM {$db_prefix}goods WHERE id = {$child_order['goods_id']}");
    if (empty($goods) || ($goods['type'] ?? '') !== 'once') {
        die('商品类型不支持下载');
    }

    $sql = "SELECT s.content
            FROM {$db_prefix}stock_usage u
            INNER JOIN {$db_prefix}stock s ON u.stock_id = s.id
            WHERE u.order_list_id = {$child_order['id']}
            ORDER BY u.id ASC";
    $rows = $db->fetch_all($sql);
    if (empty($rows)) {
        die('暂无发货内容');
    }

    $content = '';
    foreach ($rows as $row) {
        $content .= $row['content'] . "\r\n";
    }

    $filenameSeed = !empty($order['out_trade_no']) ? $order['out_trade_no'] : date('YmdHis');
    $filename = '卡密_' . $filenameSeed . '.txt';

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}
