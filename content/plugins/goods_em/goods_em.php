<?php
/*
Plugin Name: 商品对接 【EMSHOP】
Version: 1.1.1
Plugin URL:
Description: 对接另一个 EMSHOP 系统的商品
Author: 驳手
Author URL:
Ui: Layui
*/

defined('EM_ROOT') || exit('access denied!');

// 引入 API 封装类
require_once __DIR__ . '/lib/EmApi.php';

// ==================== 插件入口标识 ====================

/**
 * 插件入口函数（用于检测插件是否启用）
 */
function goodsEmPlugin() {
    return true;
}

/**
 * 是否为自动发货商品
 */
function getIsAutoEm_auto() {
    return 1;
}
function getIsAutoEm_manual() {
    return 0;
}


/**
 * 发货操作
 */
function goodsDeliverEm_auto($goods, $order, $child_order){
    return goodsDeliverEm($goods, $order, $child_order);
}
function goodsDeliverEm_manual($goods, $order, $child_order){
    return goodsDeliverEm($goods, $order, $child_order);
}
function goodsDeliverEm($goods, $order, $child_order){
    $db = Database::getInstance();
    $timestamp = time();
    $quantity = (int)$child_order['quantity'];

    $result = [
        'code' => 200,
        'status' => 1,
        'content' => [],
        'deliver_count' => 0
    ];


    // 获取对接信息
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);

    $site = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_site WHERE id = " . (int)$emGoods['site_id']);
    

    $api = EmApi::fromSite($site);


    $skuIds = $child_order['sku'] ?? '0';
    if ($skuIds === '') {
        $skuIds = '0';
    }
    $skuIds = preg_replace('/[^0-9\\-]/', '', (string)$skuIds);
    if ($skuIds === '') {
        $skuIds = '0';
    }

    $orderNoBase = $order['out_trade_no'] ?? ($order['order_no'] ?? '');
    if ($orderNoBase === '') {
        $orderNoBase = date('YmdHis', $timestamp) . $order['id'];
    }
    $requestNo = $orderNoBase . '_' . $child_order['id'];


    $attach = empty($child_order['attach_user']) ? [] : json_decode($child_order['attach_user'], true);
    $config = [];
    if(!empty($goods['config']['input'])){
        foreach($goods['config']['input'] as $val){
            foreach($attach as $k => $v){
                if($k == $val['name']){
                    $config['input'][$val['name_en']] = $v;
                }
            }
        }
    }
    $tradeResult = $api->trade($emGoods['remote_goods_id'], $quantity, $requestNo, $skuIds, $config);
    if (!$tradeResult) {
        $db->query("UPDATE " . DB_PREFIX . "order SET status = 1, pay_time = {$timestamp} WHERE id = {$order['id']}");
        $db->query("UPDATE " . DB_PREFIX . "order_list SET status = 1 WHERE id = {$child_order['id']}");
        $result['msg'] = $api->getLastError();
        $result['code'] = 400;
        return $result;
    }

    $remoteTradeNo = addslashes((string)($tradeResult['trade_no'] ?? ''));

    if ($goods['type'] === 'em_auto') {
        $secrets = EmApi::parseSecrets($tradeResult['secret'] ?? '');
        if (!empty($secrets)) {
            foreach ($secrets as $secret) {
                    $secretSql = addslashes((string)$secret);
                    $db->query("INSERT INTO " . DB_PREFIX . "stock_usage
                    (stock_id, order_id, order_list_id, content, create_time)
                    VALUES (
                        0,
                        {$order['id']},
                        {$child_order['id']},
                        '{$secretSql}',
                        {$timestamp}
                    )");
                }
            $result['content'] = $secrets;
            $result['deliver_count'] = count($secrets);
            $result['status'] = 2;
        } else {
            $result['status'] = isset($tradeResult['status']) && (int)$tradeResult['status'] === 2 ? 2 : 1;
        }

        $db->query("UPDATE " . DB_PREFIX . "product_sku SET
            stock = stock - {$quantity},
            sales = sales + {$quantity}
            WHERE goods_id = {$goods['id']} AND option_ids = '{$skuIds}'");

        $db->query("UPDATE " . DB_PREFIX . "order SET status = {$result['status']}, pay_time = {$timestamp} WHERE id = {$order['id']}");
        $db->query("UPDATE " . DB_PREFIX . "order_list SET status = {$result['status']}, remote_trade_no = '{$remoteTradeNo}' WHERE id = {$child_order['id']}");
    } else {
        $db->query("UPDATE " . DB_PREFIX . "order SET status = 1, pay_time = {$timestamp} WHERE id = {$order['id']}");
        $db->query("UPDATE " . DB_PREFIX . "order_list SET status = 1, remote_trade_no = '{$remoteTradeNo}' WHERE id = {$child_order['id']}");

        $db->query("UPDATE " . DB_PREFIX . "product_sku SET
            stock = stock - {$quantity},
            sales = sales + {$quantity}
            WHERE goods_id = {$goods['id']} AND option_ids = '{$skuIds}'");
        $result['status'] = 1;
    }

    $result['trade_no'] = $tradeResult['trade_no'] ?? '';
    return $result;
}

/**
 * 前台 - 商品详情页
 */
function getOneGoodsForHomeEm($goods){
    // 同步远程库存到本地（用于详情页展示最新库存）
    emSyncSku($goods);

    $db = Database::getInstance();
    $sql = "SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} ORDER BY user_price ASC";
    $product_sku = $db->fetch_all($sql);

    $skus = [
        'option_name' => [],
        'option_value' => [],
        'option_ids' => []
    ];

    foreach ($product_sku as $val) {
        $skus['option_value'][$val['option_ids']] = $val;
        if ($goods['is_sku'] == 'y') {
            $option_ids = explode('-', $val['option_ids']);
            foreach ($option_ids as $v) {
                if ($v !== '' && $v !== '0') {
                    $skus['option_ids'][] = $v;
                }
            }
        }
    }

    if ($goods['is_sku'] == 'y') {
        $skus['option_ids'] = array_values(array_unique($skus['option_ids']));
        $specInfo = emGetRemoteSpecInfo($goods['id']);
        // d($specInfo);die;
        if (!empty($specInfo) && !empty($specInfo['spec'])) {
            foreach ($specInfo['spec'] as $group) {
                $values = [];
                $skuValues = $group['sku_values'] ?? [];
                if (!empty($skuValues) && is_array($skuValues)) {
                    foreach ($skuValues as $val) {
                        if (!isset($val['id'])) continue;
                        $values[] = [
                            'option_id' => $val['id'],
                            'option_name' => $val['name'] ?? $val['id']
                        ];
                    }
                }
                $skus['option_name'][] = [
                    'title' => $group['title'] ?? '规格',
                    'sku_values' => $values
                ];
            }
        } else {
            $values = [];
            foreach ($skus['option_ids'] as $optId) {
                $values[] = [
                    'option_id' => $optId,
                    'option_name' => $optId
                ];
            }
            if (!empty($values)) {
                $skus['option_name'][] = [
                    'title' => '规格',
                    'sku_values' => $values
                ];
            }
        }
    }

    unset($skus['option_ids']);
    $goods['skus'] = $skus;
    $goods['is_auto'] = ($goods['type'] ?? '') === 'em_auto';

    return $goods;
}
function getOneGoodsForHomeEm_manual($goods){
    return getOneGoodsForHomeEm($goods);
}
function getOneGoodsForHomeEm_auto($goods){
    return getOneGoodsForHomeEm($goods);
}

// ==================== 站点管理 ====================

/**
 * 获取站点列表
 *
 * @param bool $refresh 是否刷新余额
 * @return array
 */
function emGetSiteList($refresh = false)
{
    $db = Database::getInstance();
    $sites = $db->fetch_all("SELECT * FROM " . DB_PREFIX . "em_site ORDER BY id DESC");

    if ($refresh && !empty($sites)) {
        foreach ($sites as &$site) {
            $api = EmApi::fromSite($site);
            $result = $api->connect();
            if ($result) {
                $site['balance'] = $result['balance'] ?? 0;
                $db->query("UPDATE " . DB_PREFIX . "em_site SET balance = {$site['balance']}, update_time = " . time() . " WHERE id = {$site['id']}");
            }
        }
    }

    return $sites ?: [];
}

/**
 * 获取单个站点
 *
 * @param int $siteId
 * @return array|null
 */
function emGetSite($siteId)
{
    $db = Database::getInstance();
    return $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_site WHERE id = " . (int)$siteId);
}

/**
 * 保存站点（新增或更新）
 *
 * @param array $data 站点数据
 * @return array ['success' => bool, 'message' => string, 'id' => int]
 */
function emSaveSite($data)
{
    $db = Database::getInstance();

    $domain = trim($data['domain'] ?? '');
    $appId = trim($data['app_id'] ?? '');
    $appKey = trim($data['app_key'] ?? '');
    $siteId = (int)($data['id'] ?? 0);

    if (empty($domain) || empty($appId) || empty($appKey)) {
        return ['success' => false, 'message' => '请填写完整信息'];
    }

    // 确保域名格式正确
    if (strpos($domain, 'http') !== 0) {
        $domain = 'https://' . $domain;
    }

    // 验证连接
    $api = new EmApi($domain, $appId, $appKey);
    $result = $api->connect();

    if (!$result) {
        return ['success' => false, 'message' => '连接失败：' . $api->getLastError()];
    }

    // d($result);die;

    $siteName = $result['site_name'] ?? '未知店铺';
    $balance = $result['balance'] ?? 0;
    $timestamp = time();

    if ($siteId > 0) {
        // 更新
        $sql = "UPDATE " . DB_PREFIX . "em_site SET
                domain = '{$db->escape_string($domain)}',
                app_id = '{$db->escape_string($appId)}',
                app_key = '{$db->escape_string($appKey)}',
                sitename = '{$db->escape_string($siteName)}',
                balance = {$balance},
                update_time = {$timestamp}
                WHERE id = {$siteId}";
        $db->query($sql);
    } else {
        // 新增
        $sql = "INSERT INTO " . DB_PREFIX . "em_site
                (domain, app_id, app_key, sitename, balance, create_time, update_time)
                VALUES (
                    '{$db->escape_string($domain)}',
                    '{$db->escape_string($appId)}',
                    '{$db->escape_string($appKey)}',
                    '{$db->escape_string($siteName)}',
                    {$balance},
                    {$timestamp},
                    {$timestamp}
                )";
        $db->query($sql);
        $siteId = $db->insert_id();
    }

    return ['success' => true, 'message' => '保存成功', 'id' => $siteId];
}

/**
 * 删除站点
 *
 * @param int $siteId
 * @return bool
 */
function emDeleteSite($siteId)
{
    $db = Database::getInstance();
    $siteId = (int)$siteId;
    $timestamp = time();

    // 获取关联商品ID并做软删除
    $rows = $db->fetch_all("SELECT goods_id FROM " . DB_PREFIX . "em_goods WHERE site_id = {$siteId}");
    $goodsIds = [];
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $goodsId = (int)($row['goods_id'] ?? 0);
            if ($goodsId > 0) {
                $goodsIds[] = $goodsId;
            }
        }
    }
    if (!empty($goodsIds)) {
        $goodsIds = array_values(array_unique($goodsIds));
        $ids = implode(',', $goodsIds);
        $db->query("UPDATE " . DB_PREFIX . "goods SET delete_time = {$timestamp} WHERE id IN ({$ids})");
    }

    // 删除映射记录
    $db->query("DELETE FROM " . DB_PREFIX . "em_goods WHERE site_id = {$siteId}");
    $db->query("DELETE FROM " . DB_PREFIX . "em_site WHERE id = {$siteId}");
    return true;
}

// ==================== 商品导入 ====================

/**
 * 下载远程图片到本地
 *
 * @param string $url 远程图片URL
 * @param string $domain 站点域名（用于补全相对路径）
 * @return string|false 成功返回本地路径，失败返回false
 */
function emDownloadImage($url, $domain = '')
{
    if (empty($url)) return false;

    // 补全URL
    $url = EmApi::fixImageUrl($url, $domain);
    if (empty($url)) return false;

    // 下载图片
    $imageData = emCurl($url, false, 0, false, 30);
    if (empty($imageData)) return false;

    // 从URL或内容判断扩展名
    $extension = '';
    $urlPath = parse_url($url, PHP_URL_PATH);
    if ($urlPath) {
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
    }

    // 验证扩展名
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    if (!in_array($extension, $allowedExts)) {
        // 尝试从图片数据判断类型
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
        ];
        $extension = $mimeMap[$mimeType] ?? '';
    }

    if (empty($extension)) return false;

    // 生成文件名
    $fileName = substr(md5($url . time()), 0, 4) . time() . '.' . $extension;

    // 创建上传目录
    $uploadFullPath = Option::UPLOADFILE_FULL_PATH . gmdate('Ym') . '/';
    if (!createDirectoryIfNeeded($uploadFullPath)) return false;

    // 保存文件
    $fullFilePath = $uploadFullPath . $fileName;
    if (file_put_contents($fullFilePath, $imageData) === false) return false;

    // 返回相对路径
    return Option::UPLOADFILE_PATH . gmdate('Ym') . '/' . $fileName;
}

/**
 * 下载内容中的所有远程图片到本地，并替换URL
 *
 * @param string $content HTML内容
 * @param string $domain 站点域名
 * @return string 替换后的内容
 */
function emDownloadContentImages($content, $domain = '')
{
    if (empty($content)) return $content;

    // 匹配所有img标签的src属性
    $pattern = '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i';
    preg_match_all($pattern, $content, $matches);

    if (empty($matches[1])) return $content;

    $replacements = [];
    foreach ($matches[1] as $imgUrl) {
        // 跳过已经是本地的图片
        if (strpos($imgUrl, 'uploadfile/') !== false) continue;
        if (strpos($imgUrl, 'data:image') === 0) continue;

        // 下载并替换
        $localPath = emDownloadImage($imgUrl, $domain);
        if ($localPath) {
            $replacements[$imgUrl] = $localPath;
        }
    }

    // 替换所有远程URL为本地路径
    foreach ($replacements as $remoteUrl => $localPath) {
        $content = str_replace($remoteUrl, $localPath, $content);
    }

    return $content;
}

/**
 * 导入对接商品
 *
 * @param array $params 导入参数
 * @return array ['success' => int, 'fail' => int, 'messages' => array]
 */
function emImportGoods($params)
{
    $db = Database::getInstance();
    $siteId = (int)($params['site_id'] ?? 0);
    $goodsIds = $params['goods_ids'] ?? []; // 远程商品ID列表
    $sortId = (int)($params['sort_id'] ?? 0);
    $raiseType = $params['raise_type'] ?? 'percent'; // percent / fixed
    $raiseValue = floatval($params['raise_value'] ?? 10);

    $site = emGetSite($siteId);
    if (!$site) {
        return ['success' => 0, 'fail' => count($goodsIds), 'messages' => ['站点不存在']];
    }

    $api = EmApi::fromSite($site);
    $result = ['success' => 0, 'fail' => 0, 'messages' => []];
    foreach ($goodsIds as $remoteGoodsId) {
        $remoteGoodsId = (int)$remoteGoodsId;
        if ($remoteGoodsId <= 0) continue;

        // 检查是否已导入
        $existsRow = $db->once_fetch_array("SELECT COUNT(*) as cnt FROM " . DB_PREFIX . "em_goods WHERE site_id = {$siteId} AND remote_goods_id = {$remoteGoodsId}");
        if ($existsRow && $existsRow['cnt'] > 0) {
            $result['fail']++;
            $result['messages'][] = "[ID:{$remoteGoodsId}] 已存在，跳过";
            continue;
        }
        // 获取远程商品信息
        $item = $api->getItem($remoteGoodsId);

        if (!$item) {
            $result['fail']++;
            $result['messages'][] = "[ID:{$remoteGoodsId}] 获取失败：" . $api->getLastError();
            continue;
        }
        $config = [
            'visitor_required' => 'any'
        ];
        if(!empty($item['config']['input'])){
            $config['input'] = $item['config']['input'];
        }

        // 根据远程商品类型判断发货方式
        $deliveryWay = (int)($item['delivery_way'] ?? 0);
        if (!isset($item['delivery_way']) && array_key_exists('is_auto', $item)) {
            $deliveryWay = $item['is_auto'] ? 0 : 1;
        }
        $goodsType = $deliveryWay === 1 ? 'em_manual' : 'em_auto';

        // 判断是否多规格
        $isSkuFlag = $item['is_sku'] ?? 'n';
        $isMultiSku = ($isSkuFlag === 'y' || $isSkuFlag === 'Y' || $isSkuFlag === 1 || $isSkuFlag === true);
        $skus = $item['skus'] ?? [];
        if (isset($skus['option_value']) && is_array($skus['option_value'])) {
            $skus = $skus['option_value'];
        }


        // 下载封面图到本地
        $coverUrl = $item['cover'] ?? '';
        $localCover = '';
        if (!empty($coverUrl)) {
            $localCover = emDownloadImage($coverUrl, $site['domain']);
            if (!$localCover) {
                $localCover = EmApi::fixImageUrl($coverUrl, $site['domain']);
            }
        }

        // 下载商品详情中的图片到本地
        $content = $item['description'] ?? ($item['content'] ?? '');
        if (!empty($content)) {
            $content = emDownloadContentImages($content, $site['domain']);
        }

        $itemName = $item['name'] ?? ($item['title'] ?? "商品{$remoteGoodsId}");

        // 创建本地商品
        $goodsData = [
            'title' => $db->escape_string($itemName),
            'type' => $goodsType,
            'group_id' => -1, // 对接商品标识
            'sort_id' => $sortId,
            'is_sku' => $isMultiSku ? 'y' : 'n',
            'cover' => $db->escape_string($localCover),
            'content' => $db->escape_string($content),
            'create_time' => time(),
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE),
        ];

        $fields = implode(', ', array_keys($goodsData));
        $values = "'" . implode("', '", array_values($goodsData)) . "'";
        $db->query("INSERT INTO " . DB_PREFIX . "goods ({$fields}) VALUES ({$values})");
        $localGoodsId = $db->insert_id();

        if (!$localGoodsId) {
            $result['fail']++;
            $result['messages'][] = "[ID:{$remoteGoodsId}] 创建商品失败";
            continue;
        }

        // 创建 SKU
        if ($isMultiSku && !empty($skus)) {
            foreach ($skus as $skuKey => $sku) {
                if ($skuKey === '0' || $skuKey === 0) continue;

                $costPrice = emGetRemoteSkuCostPrice($sku);
                // var_dump($costPrice);die;
                // 计算售价
                if ($raiseType == 'percent') {
                    $sellPrice = (int)($costPrice * (1 + $raiseValue / 100));
                } else {
                    $sellPrice = $costPrice + (int)($raiseValue * 100);
                }
                $stock = (int)($sku['stock'] ?? 0);

                $db->query("INSERT INTO " . DB_PREFIX . "product_sku
                    (goods_id, option_ids, guest_price, user_price, cost_price, stock, sales)
                    VALUES (
                        {$localGoodsId},
                        '{$db->escape_string($skuKey)}',
                        {$sellPrice},
                        {$sellPrice},
                        {$costPrice},
                        {$stock},
                        0
                    )");
            }
        } else {
            // 单规格
            $sku = reset($skus) ?: [];
            $costPrice = emGetRemoteSkuCostPrice($sku);
            if ($raiseType == 'percent') {
                $sellPrice = (int)($costPrice * (1 + $raiseValue / 100));
            } else {
                $sellPrice = $costPrice + (int)($raiseValue * 100);
            }
            $stock = (int)($sku['stock'] ?? 0);

            $db->query("INSERT INTO " . DB_PREFIX . "product_sku
                (goods_id, option_ids, guest_price, user_price, cost_price, stock, sales)
                VALUES (
                    {$localGoodsId},
                    '0',
                    {$sellPrice},
                    {$sellPrice},
                    {$costPrice},
                    {$stock},
                    0
                )");
        }

        $db->query("INSERT INTO " . DB_PREFIX . "em_goods
            (site_id, goods_id, remote_goods_id, name, create_time)
            VALUES (
                {$siteId},
                {$localGoodsId},
                {$remoteGoodsId},
                '{$db->escape_string($itemName)}',
                " . time() . "
            )");

        $deliveryText = $goodsType === 'em_manual' ? '人工发货' : '自动发货';
        $result['success']++;
        $result['messages'][] = "[ID:{$remoteGoodsId}] {$itemName} 导入成功（{$deliveryText}）";
    }
    return $result;
}

// ==================== SKU 同步 ====================

/**
 * 同步远程 SKU 到本地
 *
 * @param array $goods 商品信息
 * @return bool
 */
function emSyncSku($goods)
{
    if (!in_array($goods['type'], ['em_auto', 'em_manual'])) {
        return false;
    }

    $db = Database::getInstance();

    // 获取对接映射信息
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
    if (!$emGoods) return false;
    // 获取远程商品信息
    $remoteItem = emGetRemoteItemByGoodsId($goods['id']);
    if (!$remoteItem) return false;

    $remoteSkus = $remoteItem['skus'] ?? [];
    if (isset($remoteSkus['option_value']) && is_array($remoteSkus['option_value'])) {
        $remoteSkus = $remoteSkus['option_value'];
    }
    $isSkuFlag = $remoteItem['is_sku'] ?? 'n';
    $isRemoteMulti = ($isSkuFlag === 'y' || $isSkuFlag === 'Y' || $isSkuFlag === 1 || $isSkuFlag === true);

    // 获取本地 SKU
    $localSkus = $db->fetch_all("SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = " . (int)$goods['id']);
    $localOptionIds = array_column($localSkus ?: [], 'option_ids');

    // 判断本地是单规格还是多规格
    $isLocalMulti = !(count($localOptionIds) == 1 && $localOptionIds[0] == '0');

    if ($isRemoteMulti) {
        // 远程是多规格
        if (!$isLocalMulti) {
            // 本地是单规格 → 删除单规格
            $db->query("DELETE FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} AND option_ids = '0'");
        }

        $remoteOptionIds = [];
        foreach ($remoteSkus as $skuKey => $sku) {
            if ($skuKey === '0' || $skuKey === 0) continue;

            $remoteOptionIds[] = $skuKey;
            $stock = (int)($sku['stock'] ?? 0);
            $skuKeyEscaped = $db->escape_string($skuKey);

            if (in_array($skuKey, $localOptionIds)) {
                // 更新库存
                $db->query("UPDATE " . DB_PREFIX . "product_sku SET stock = {$stock} WHERE goods_id = {$goods['id']} AND option_ids = '{$skuKeyEscaped}'");
            } else {
                // 新增规格
                $costPrice = emGetRemoteSkuCostPrice($sku);
                $sellPrice = (int)($costPrice * 1.1);
                $db->query("INSERT INTO " . DB_PREFIX . "product_sku
                    (goods_id, option_ids, guest_price, user_price, cost_price, stock, sales)
                    VALUES ({$goods['id']}, '{$skuKeyEscaped}', {$sellPrice}, {$sellPrice}, {$costPrice}, {$stock}, 0)");
            }
        }

        // 删除远程不存在的规格
        foreach ($localOptionIds as $optionId) {
            if ($optionId != '0' && !in_array($optionId, $remoteOptionIds)) {
                $skuRow = $db->once_fetch_array("SELECT id FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} AND option_ids = '{$db->escape_string($optionId)}'");
                if ($skuRow && $skuRow['id']) {
                    $db->query("DELETE FROM " . DB_PREFIX . "tier_price WHERE sku_id = {$skuRow['id']}");
                }
                $db->query("DELETE FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']} AND option_ids = '{$db->escape_string($optionId)}'");
            }
        }

        // 更新商品为多规格
        $db->query("UPDATE " . DB_PREFIX . "goods SET is_sku = 'y' WHERE id = {$goods['id']}");

    } else {
        // 远程是单规格
        if ($isLocalMulti) {
            // 本地是多规格 → 删除多规格
            foreach ($localSkus as $sku) {
                $db->query("DELETE FROM " . DB_PREFIX . "tier_price WHERE sku_id = {$sku['id']}");
            }
            $db->query("DELETE FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods['id']}");
        }

        $sku = reset($remoteSkus) ?: [];
        $stock = (int)($sku['stock'] ?? 0);

        if (in_array('0', $localOptionIds)) {
            $db->query("UPDATE " . DB_PREFIX . "product_sku SET stock = {$stock} WHERE goods_id = {$goods['id']} AND option_ids = '0'");
        } else {
            $costPrice = emGetRemoteSkuCostPrice($sku);
            $sellPrice = (int)($costPrice * 1.1);
            $db->query("INSERT INTO " . DB_PREFIX . "product_sku
                (goods_id, option_ids, guest_price, user_price, cost_price, stock, sales)
                VALUES ({$goods['id']}, '0', {$sellPrice}, {$sellPrice}, {$costPrice}, {$stock}, 0)");
        }

        // 更新商品为单规格
        $db->query("UPDATE " . DB_PREFIX . "goods SET is_sku = 'n' WHERE id = {$goods['id']}");
    }

    return true;
}

// ==================== 发货处理 ====================



/**
 * 获取订单卡密
 *
 * @param int $orderListId 子订单ID
 * @return array
 */
function emGetOrderSecrets($orderListId)
{
    $db = Database::getInstance();
    $sql = "SELECT * FROM " . DB_PREFIX . "stock_usage WHERE order_list_id = " . (int)$orderListId . " ORDER BY id ASC";
    $records = $db->fetch_all($sql);
    return $records ?: [];
}

/**
 * 获取远程商品信息（带缓存）
 *
 * @param int $goodsId 本地商品ID
 * @return array|null
 */
function emGetRemoteItemByGoodsId($goodsId)
{
    static $cache = [];
    $goodsId = (int)$goodsId;
    if ($goodsId <= 0) return null;
    if (array_key_exists($goodsId, $cache)) {
        return $cache[$goodsId];
    }

    $db = Database::getInstance();
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = {$goodsId}");
    if (!$emGoods) {
        $cache[$goodsId] = null;
        return null;
    }
    $site = emGetSite($emGoods['site_id']);
    if (!$site) {
        $cache[$goodsId] = null;
        return null;
    }

    $api = EmApi::fromSite($site);
    $item = $api->getItem($emGoods['remote_goods_id']);
    if (!$item) {
        $cache[$goodsId] = null;
        return null;
    }

    $cache[$goodsId] = $item;
    return $item;
}

/**
 * 规范化规格结构（从 option_name 转换）
 *
 * @param array $optionName
 * @return array
 */
function emNormalizeSpecFromOptionName($optionName)
{
    $spec = [];
    if (empty($optionName) || !is_array($optionName)) {
        return $spec;
    }

    foreach ($optionName as $index => $group) {
        $values = [];
        $skuValues = $group['sku_values'] ?? [];
        if (!empty($skuValues) && is_array($skuValues)) {
            foreach ($skuValues as $val) {
                if (!isset($val['option_id'])) {
                    continue;
                }
                $values[] = [
                    'id' => $val['option_id'],
                    'name' => $val['option_name'] ?? $val['option_id']
                ];
            }
        }
        $spec[] = [
            'sku_attr_id' => $index,
            'title' => $group['title'] ?? '规格',
            'sku_values' => $values
        ];
    }

    return $spec;
}

/**
 * 获取远程规格信息（带缓存）
 *
 * @param int $goodsId 本地商品ID
 * @return array|null
 */
function emGetRemoteSpecInfo($goodsId)
{
    static $cache = [];
    $goodsId = (int)$goodsId;
    if ($goodsId <= 0) return null;
    if (array_key_exists($goodsId, $cache)) {
        return $cache[$goodsId];
    }

    $item = emGetRemoteItemByGoodsId($goodsId);
    $spec = $item['spec'] ?? null;
    if ((empty($spec) || !is_array($spec)) && !empty($item['skus']['option_name'])) {
        $spec = emNormalizeSpecFromOptionName($item['skus']['option_name']);
    }
    if (empty($item) || empty($spec) || !is_array($spec)) {
        $cache[$goodsId] = null;
        return null;
    }

    $spec = $spec;
    $specNames = [];
    $specAttr = [];
    $optionNames = [];
    $optionSpecId = [];
    $specIndex = [];

    foreach ($spec as $index => $group) {
        $specId = $group['sku_attr_id'] ?? $group['id'] ?? $index;
        $specTitle = $group['title'] ?? '规格';

        $specNames[] = $specTitle;
        $specIndex[$specId] = $index;
        $specAttr[$index] = [];

        $values = $group['sku_values'] ?? [];
        if (!empty($values) && is_array($values)) {
            foreach ($values as $val) {
                if (!isset($val['id'])) continue;
                $optId = (string)$val['id'];
                $optionNames[$optId] = $val['name'] ?? $optId;
                $optionSpecId[$optId] = $specId;
                $specAttr[$index][] = $val['id'];
            }
        }
    }

    $cache[$goodsId] = [
        'spec' => $spec,
        'spec_names' => $specNames,
        'spec_attr' => $specAttr,
        'option_names' => $optionNames,
        'option_spec_id' => $optionSpecId,
        'spec_index' => $specIndex
    ];

    return $cache[$goodsId];
}

/**
 * 将 SKU 组合映射为规格名称数组
 *
 * @param string $optionIds SKU 组合（如 "1-3"）
 * @param array $specInfo 规格信息
 * @return array
 */
function emMapSkuValuesToNames($optionIds, $specInfo)
{
    $optionIds = trim((string)$optionIds);
    if ($optionIds === '' || $optionIds === '0') {
        return [];
    }

    $parts = array_filter(explode('-', $optionIds), 'strlen');
    $specNames = $specInfo['spec_names'] ?? [];
    $specCount = count($specNames);
    $optionNames = $specInfo['option_names'] ?? [];
    $optionSpecId = $specInfo['option_spec_id'] ?? [];
    $specIndex = $specInfo['spec_index'] ?? [];

    if ($specCount === 0) {
        $values = [];
        foreach ($parts as $id) {
            $idStr = (string)$id;
            $values[] = $optionNames[$idStr] ?? $idStr;
        }
        return $values;
    }

    $values = array_fill(0, $specCount, '');
    $emptyIndexes = range(0, $specCount - 1);

    foreach ($parts as $id) {
        $idStr = (string)$id;
        $name = $optionNames[$idStr] ?? $idStr;
        $index = null;

        if (isset($optionSpecId[$idStr]) && isset($specIndex[$optionSpecId[$idStr]])) {
            $index = $specIndex[$optionSpecId[$idStr]];
        } elseif (!empty($emptyIndexes)) {
            $index = array_shift($emptyIndexes);
        }

        if ($index !== null) {
            $values[$index] = $name;
            $emptyIndexes = array_values(array_diff($emptyIndexes, [$index]));
        }
    }

    return $values;
}

/**
 * 获取远程 SKU 成本价（分）
 *
 * @param array $sku
 * @return int
 */
function emGetRemoteSkuCostPrice($sku) {

    // d($sku);

    $raw = 0;
    if (isset($sku['price'])) {
        $raw = $sku['price'];
    } elseif (isset($sku['user_price'])) {
        $raw = $sku['user_price'];
    } elseif (isset($sku['guest_price'])) {
        $raw = $sku['guest_price'];
    }

    // var_dump($raw);die;

    $raw = is_numeric($raw) ? (float)$raw : 0;
    if ($raw < 0) $raw = 0;

    return (int)round($raw * 100);
}

/**
 * 将 SKU 组合格式化为可读规格文本
 *
 * @param int $goodsId 本地商品ID
 * @param string $optionIds SKU 组合
 * @param bool $withTitle 是否包含规格标题
 * @return string
 */
function emFormatSkuOptionIds($goodsId, $optionIds, $withTitle = true)
{
    $optionIds = trim((string)$optionIds);
    if ($optionIds === '' || $optionIds === '0') {
        return '默认规格';
    }

    $specInfo = emGetRemoteSpecInfo($goodsId);
    if (empty($specInfo)) {
        return $optionIds;
    }

    $values = emMapSkuValuesToNames($optionIds, $specInfo);
    if (empty($values)) {
        return $optionIds;
    }

    $parts = [];
    if ($withTitle && !empty($specInfo['spec_names'])) {
        foreach ($values as $index => $value) {
            if ($value === '') continue;
            $title = $specInfo['spec_names'][$index] ?? '规格';
            $parts[] = $title . '：' . $value;
        }
    } else {
        foreach ($values as $value) {
            if ($value === '') continue;
            $parts[] = $value;
        }
    }

    if (empty($parts)) {
        return $optionIds;
    }

    return implode('；', $parts) . '；';
}


/**
 * Hook: SKU 组件获取远程规格
 */
function plugin_em_get_remote_spec($input, &$ret)
{
    if (!in_array($input['goods_type'], ['em_auto', 'em_manual'])) {
        return;
    }

    $goodsId = (int)($input['goods_id'] ?? 0);
    if ($goodsId <= 0) return;

    $specInfo = emGetRemoteSpecInfo($goodsId);
    $specNames = (!empty($specInfo) && !empty($specInfo['spec_names'])) ? $specInfo['spec_names'] : [];

    $db = Database::getInstance();
    $skus = $db->fetch_all("SELECT option_ids FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goodsId}");
    if (empty($skus)) return;

    $optionIds = array_column($skus, 'option_ids');

    if (count($optionIds) == 1 && $optionIds[0] == '0') {
        $ret = [
            'sku_combinations' => [],
            'sku_data' => []
        ];
    } else {
        $combinations = [];
        foreach ($optionIds as $optionId) {
            if ($optionId === '0' || $optionId === 0) continue;
            $values = [];
            if (!empty($specInfo)) {
                $values = emMapSkuValuesToNames($optionId, $specInfo);
            }
            if (empty($values)) {
                $values = [$optionId];
            }
            $combinations[] = [
                'sku' => $optionId,
                'values' => $values
            ];
        }

        $ret = [
            'sku_combinations' => $combinations,
            'sku_data' => []
        ];
        if (!empty($specNames)) {
            $ret['spec_names'] = $specNames;
        }
    }
}

/**
 * Hook: 后台商品列表类型徽章
 */
function plugin_em_goods_list_type($type)
{
    echo "{{#  if(d.type == 'em_auto'){ }}<span class=\"layui-badge layui-bg-green\">EM对接(自动)</span>{{#  } }}";
    echo "{{#  if(d.type == 'em_manual'){ }}<span class=\"layui-badge layui-bg-orange\">EM对接(人工)</span>{{#  } }}";
}

/**
 * Hook: 库存页面重定向（未售）
 */
function plugin_em_stock_ws($goods)
{
    if (in_array($goods['type'], ['em_auto', 'em_manual'])) {
        include __DIR__ . '/views/stock.php';
        exit;
    }
}

/**
 * Hook: 库存页面重定向（已售）
 */
function plugin_em_stock_ys($goods)
{
    if (in_array($goods['type'], ['em_auto', 'em_manual'])) {
        include __DIR__ . '/views/stock_sold.php';
        exit;
    }
}

/**
 * Hook: 用户订单详情
 */
function plugin_em_order_detail($db, $db_prefix, $goods, $order, $child_order)
{
    if (!in_array($goods['type'], ['em_auto', 'em_manual'])) {
        return;
    }

    include View::getUserView('_header');
    include __DIR__ . '/views/order_detail.php';
    include View::getUserView('_footer');
    View::output();
}

/**
 * Hook: 后台发货视图
 */
function plugin_em_adm_deliver_view($db, $db_prefix, $goods, $order, $child_order)
{
    if (in_array($goods['type'], ['em_auto', 'em_manual'])) {
        include View::getAdmView('open_head');
        include __DIR__ . '/views/adm_deliver.php';
        include View::getAdmView('open_foot');
        View::output();
    }
}

/**
 * 后台订单详情 - 获取发货内容（自动发货）
 */
function plugin_goods_em_auto_adm_order_detail($db, $db_prefix, $goods, $child_order)
{
    if ($goods['type'] != 'em_auto') {
        return '';
    }

    $sql = "SELECT content FROM {$db_prefix}stock_usage
            WHERE order_list_id = {$child_order['id']} AND stock_id = 0
            ORDER BY id ASC
            LIMIT 5";
    $list = $db->fetch_all($sql);

    $str = "";
    foreach ($list as $val) {
        $str .= htmlspecialchars($val['content']) . "<hr />";
    }
    return $str ?: '暂无发货内容';
}

/**
 * 后台订单详情 - 获取发货内容（人工发货）
 */
function plugin_goods_em_manual_adm_order_detail($db, $db_prefix, $goods, $child_order)
{
    if ($goods['type'] != 'em_manual') {
        return '';
    }

    $sql = "SELECT content FROM {$db_prefix}stock_usage
            WHERE order_list_id = {$child_order['id']} AND stock_id = 0
            ORDER BY id ASC
            LIMIT 5";
    $list = $db->fetch_all($sql);

    $str = "";
    foreach ($list as $val) {
        $str .= htmlspecialchars($val['content']) . "<hr />";
    }
    return $str ?: '待发货';
}

  /**
   * 订单模型直连验证（库存 + 余额）
   */
  function remoteGoodsVerifyEm_auto($goods, $quantity, $sku = '0'){
      return remoteGoodsVerifyEm($goods, $quantity, $sku);
  }
  function remoteGoodsVerifyEm_manual($goods, $quantity, $sku = '0'){
      return remoteGoodsVerifyEm($goods, $quantity, $sku);
  }
  function remoteGoodsVerifyEm($goods, $quantity, $sku = '0'){
      $input = [
          'goods' => $goods,
          'quantity' => $quantity,
          'sku' => $sku
      ];
      $result = [];
      plugin_em_check_stock($input, $result);
      if (!empty($result['error'])) {
          return ['code' => 400, 'msg' => $result['error']];
      }
      return ['code' => 0];
  }

  /**
   * Hook: 下单前验证
   */
  function plugin_em_check_stock($input, &$result)
  {
    $goods = $input['goods'];


    if (!in_array($goods['type'], ['em_auto', 'em_manual'])) {
        return;
    }

    $db = Database::getInstance();
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
    if (!$emGoods) {
        $result['error'] = '对接信息不存在';
        return;
    }

    $site = emGetSite($emGoods['site_id']);
    if (!$site) {
        $result['error'] = '对接站点不存在';
        return;
    }

    $api = EmApi::fromSite($site);


    $skuIds = empty($input['sku']) ? '0' : $input['sku'];


    $quantity = (int)$input['quantity'];

    // 检查远程库存
    if (!$api->checkStock($emGoods['remote_goods_id'], $quantity, $skuIds)) {
        $result['error'] = '上游库存不足';
        return;
    }


    // 获取成本价
    $sku = $db->once_fetch_array("SELECT cost_price FROM " . DB_PREFIX . "product_sku WHERE goods_id = " . (int)$goods['id'] . " AND option_ids = '{$db->escape_string($skuIds)}'");
    if (!$sku) {
        $result['error'] = '请选择商品规格';
        return;
    }

    // 检查余额
    $totalCost = ($sku['cost_price'] / 100) * $quantity;

    $connectResult = $api->connect();
    if (!$connectResult) {
        $result['error'] = '无法获取上游站点信息：' . $api->getLastError();
        return;
    }

    $balance = floatval($connectResult['balance'] ?? 0);

    // 更新本地缓存余额
    $db->query("UPDATE " . DB_PREFIX . "em_site SET balance = {$balance}, update_time = " . time() . " WHERE id = {$site['id']}");

    if ($balance < $totalCost) {
        $result['error'] = "站长余额不足，无法下单。请联系客服人员处理";
        return;
    }
}

/**
 * Hook: 库存管理首页
 */
function adm_stock_em($goods, $skus)
{
    $db = Database::getInstance();

    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
    $site = $emGoods ? emGetSite($emGoods['site_id']) : null;

    include View::getAdmView('open_head');
    include __DIR__ . '/views/stock_index.php';
    include View::getAdmView('open_foot');
    View::output();
}

// ==================== 注册 Hook ====================

// 商品展示
addAction('goods_content_echo', 'plugin_em_goods_content_echo');

// SKU 组件
addAction('sku_get_remote_spec', 'plugin_em_get_remote_spec');

// 发货处理
addAction('deliver_goods', 'emDeliver');
addAction('deliver_goods', 'emDeliverManual');

// 后台商品列表
addAction('adm_goods_list_type', 'plugin_em_goods_list_type');

// 库存页面
addAction('adm_stock_page_ws', 'plugin_em_stock_ws');
addAction('adm_stock_page_ys', 'plugin_em_stock_ys');

/**
 * Hook: 构建订单规格描述（对接商品）
 */
function plugin_em_build_order_attr_spec($input, &$result)
{
    $goods = $input['goods'] ?? [];
    if (!in_array($goods['type'] ?? '', ['em_auto', 'em_manual'])) {
        return;
    }

    $skuStr = $input['sku_str'] ?? '';
    if (empty($skuStr) || $skuStr === '0') {
        return;
    }

    if (function_exists('emFormatSkuOptionIds')) {
        $result['attr_spec'] = emFormatSkuOptionIds($goods['id'], $skuStr);
    } else {
        $result['attr_spec'] = $skuStr . '；';
    }
}
addAction('build_order_attr_spec', 'plugin_em_build_order_attr_spec');


// 库存管理首页
addAction('adm_stock_em_auto', 'adm_stock_em');
addAction('adm_stock_em_manual', 'adm_stock_em');



// 订单详情
addAction('view_order_detail', 'plugin_em_order_detail');
addAction('adm_deliver_view', 'plugin_em_adm_deliver_view');

// 下单验证
addAction('pay_order_check', 'plugin_em_check_stock');

// 添加商品类型
function plugin_em_type($goods, &$result)
{
    $workingGoods = !empty($result) && is_array($result) ? $result : $goods;
    $workingGoods['goods_type_all'][] = ['name' => 'EM对接(自动发货)', 'value' => 'em_auto', 'is_remote' => true];
    $workingGoods['goods_type_all'][] = ['name' => 'EM对接(人工发货)', 'value' => 'em_manual', 'is_remote' => true];
    $result = $workingGoods;
}
addAction('adm_add_goods_goodsinfo', 'plugin_em_type');

/**
 * Hook: 获取对接商品来源信息
 */
function plugin_em_get_remote_source($input, &$ret)
{
    $goods = $input['goods'] ?? [];

    if (!in_array($goods['type'] ?? '', ['em_auto', 'em_manual'])) {
        return;
    }

    $db = Database::getInstance();
    $emGoods = $db->once_fetch_array("SELECT * FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$goods['id']);
    if (!$emGoods) {
        $ret = '映射信息丢失';
        return;
    }

    $site = emGetSite($emGoods['site_id']);
    if (!$site) {
        $ret = '站点已删除';
        return;
    }

    $deliveryType = $goods['type'] == 'em_manual' ? '人工发货' : '自动发货';
    $ret = "来自 <b>{$site['sitename']}</b>（{$deliveryType}）<span style=\"color:#999; margin-left:10px;\">远程商品ID: {$emGoods['remote_goods_id']}</span>";
}
addAction('get_remote_goods_source', 'plugin_em_get_remote_source');

/**
 * Hook: 商品列表扩展数据
 */
function plugin_em_goods_list_extend($row, &$ret)
{
    if (!in_array($row['type'] ?? '', ['em_auto', 'em_manual'])) {
        return;
    }

    $db = Database::getInstance();
    $emGoods = $db->once_fetch_array("SELECT site_id FROM " . DB_PREFIX . "em_goods WHERE goods_id = " . (int)$row['id']);
    if (!$emGoods) {
        $ret['remote_source'] = '映射丢失';
        return;
    }

    $site = emGetSite($emGoods['site_id']);
    if (!$site) {
        $ret['remote_source'] = '站点已删除';
        return;
    }

    $ret['remote_source'] = $site['sitename'];
}
addAction('adm_goods_list_extend', 'plugin_em_goods_list_extend');

/**
 * Hook: 商品删除时同步删除映射记录
 */
function plugin_em_delete_goods($goods)
{
    if($goods['type'] == 'em_auto' || $goods['type'] == 'em_manual'){
        $db = Database::getInstance();
        $db->query("DELETE FROM " . DB_PREFIX . "em_goods WHERE goods_id = {$goods['id']}");
    }
}
addAction('del_product', 'plugin_em_delete_goods');


/**
 * 后台管理 - 订单详情页
 */
function adminOrderDetailEm($goods, $order, $child_order, $user){
    include_once EM_ROOT . '/content/plugins/goods_em/views/admin_order_detail.php';
}
function adminOrderDetailEm_auto($goods, $order, $child_order, $user){
    adminOrderDetailEm($goods, $order, $child_order, $user);
}
function adminOrderDetailEm_manual($goods, $order, $child_order, $user){
    adminOrderDetailEm($goods, $order, $child_order, $user);
}

/**
 * 个人中心 - 订单详情页
 */
function orderDetailEm($goods, $order, $child_order){
    // Normalize parameter order (user/order.php passes $order, $child_order, $goods)
    if (isset($goods['out_trade_no']) && !isset($order['out_trade_no'])) {
        $tmpOrder = $goods;
        $tmpChild = $order;
        $tmpGoods = $child_order;
        $order = $tmpOrder;
        $child_order = $tmpChild;
        $goods = $tmpGoods;
    }

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    include_once EM_ROOT . '/content/plugins/goods_em/views/order_detail.php';
}
function orderDetailEm_auto($goods, $order, $child_order){
    orderDetailEm($goods, $order, $child_order);
}
function orderDetailEm_manual($goods, $order, $child_order){
    orderDetailEm($goods, $order, $child_order);
}
/**
 * 后台管理 - 库存管理
 */
function adminStockEm($goods){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $goodsId = (int)$goods['id'];
    $page = max(1, (int)Input::getIntVar('page', 1));
    $pageSize = (int)Option::get('admin_article_perpage_num');
    if ($pageSize <= 0) {
        $pageSize = 20;
    }
    $keyword = trim((string)Input::getStrVar('keyword', ''));

    $where = "ol.goods_id = {$goodsId} AND su.stock_id = 0";
    if ($keyword !== '') {
        $keywordEsc = $db->escape_string($keyword);
        $like = "%{$keywordEsc}%";
        $where .= " AND (su.content LIKE '{$like}' OR o.up_no LIKE '{$like}' OR o.out_trade_no LIKE '{$like}' OR o.trade_no LIKE '{$like}' OR o.api_trade_no LIKE '{$like}')";
    }

    $countRow = $db->once_fetch_array("SELECT COUNT(*) as total
        FROM {$db_prefix}stock_usage su
        INNER JOIN {$db_prefix}order_list ol ON su.order_list_id = ol.id
        INNER JOIN {$db_prefix}order o ON su.order_id = o.id
        WHERE {$where}");
    $total = (int)($countRow['total'] ?? 0);

    $offset = ($page - 1) * $pageSize;
    $records = $db->fetch_all("SELECT su.*, ol.sku, ol.quantity, o.up_no, o.out_trade_no
        FROM {$db_prefix}stock_usage su
        INNER JOIN {$db_prefix}order_list ol ON su.order_list_id = ol.id
        INNER JOIN {$db_prefix}order o ON su.order_id = o.id
        WHERE {$where}
        ORDER BY su.id DESC
        LIMIT {$offset}, {$pageSize}");

    $baseUrl = "stock.php?action=index&goods_id={$goodsId}";
    if ($keyword !== '') {
        $baseUrl .= "&keyword=" . urlencode($keyword);
    }

    include_once EM_ROOT . '/content/plugins/goods_em/views/admin_stock.php';
}
function adminStockEm_auto($goods){
    adminStockEm($goods);
}
function adminStockEm_manual($goods){
    adminStockEm($goods);
}
