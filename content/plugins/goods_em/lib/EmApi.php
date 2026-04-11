<?php
/**
 * EMSHOP 同系统对接 API 封装类
 *
 * 用于对接另一个 EMSHOP 系统的商品
 */

class EmApi
{
    private $domain;
    private $userId;
    private $token;
    private $lastError = '';

    /**
     * 构造函数
     *
     * @param string $domain 站点域名
     * @param string $userId 用户ID（app_id）
     * @param string $token 用户Token（app_key）
     */
    public function __construct($domain, $userId, $token)
    {
        $this->domain = rtrim($domain, '/');
        $this->userId = $userId;
        $this->token = $token;
    }

    /**
     * 从站点信息创建实例
     *
     * @param array $site 站点配置
     * @return EmApi
     */
    public static function fromSite($site)
    {
        return new self(
            $site['domain'],
            $site['app_id'],
            $site['app_key']
        );
    }

    /**
     * 获取最后的错误信息
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * 生成签名
     *
     * EMSHOP 签名规则：md5(req_time + req_token)
     *
     * @param int $timestamp 时间戳
     * @return string
     */
    private function sign($timestamp)
    {
        return md5($timestamp . $this->token);
    }

    /**
     * 发送请求
     *
     * @param string $action API 方法名
     * @param array $params 请求参数
     * @return array|false
     */
    private function request($action, $params = [])
    {
        $url = $this->domain . '/?rest-api=' . $action;

        $timestamp = time();
        $params = array_merge($params, [
            'user_id' => $this->userId,
            'req_time' => $timestamp,
            'req_token' => $this->token,
            'req_sign' => $this->sign($timestamp)
        ]);


        $postData = http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EMSHOP-GOODS-EM/1.0');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postData)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);


        if ($error) {
            $this->lastError = 'CURL错误: ' . $error;
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = 'HTTP错误: ' . $httpCode;
            return false;
        }

        $result = json_decode($response, true);
        if (!$result) {
            $this->lastError = '响应解析失败: ' . substr($response, 0, 200);
            return false;
        }
        

        // EMSHOP 返回格式：{"code": 200, "msg": "success", "data": {...}}
        // code = 200 表示成功
        if (isset($result['code']) && $result['code'] !== 200) {
            $this->lastError = $result['msg'] ?? '未知错误';
            Log::error("EMApi请求失败: action={$action}, code={$result['code']}, msg={$this->lastError}");
            return false;
        }


        return $result['data'] ?? $result;
    }

    /**
     * 连接验证 - 获取店铺信息
     *
     * @return array|false 成功返回店铺信息，失败返回 false
     */
    public function connect()
    {
        $data = $this->request('getEmInfo');
        if (!$data) return false;

        // 获取用户余额信息
        $userInfo = $this->request('getUserInfo');
        if ($userInfo) {
            $data['balance'] = $userInfo['money'] ?? 0;
            $data['nickname'] = $userInfo['nickname'] ?? '';
        }

        return $data;
    }

    /**
     * 获取商品分类
     *
     * @return array|false
     */
    public function getCategories()
    {
        return $this->request('getGoodsSort');
    }

    /**
     * 获取商品列表
     *
     * @return array|false
     */
    public function getItems()
    {
        $data = $this->request('getGoodsList');
        if (!$data) return false;


        // 获取分类信息用于组织商品
        $categories = $this->getCategories();
//        d($categories);die;
        $categoryMap = [];
        if ($categories) {
            foreach ($categories as $cat) {
                $categoryMap[$cat['sort_id']] = $cat['sortname'];
            }
        }

        // 组织返回格式
        $result = [];
        foreach ($data as $goods) {
            $goodsId = (int)($goods['goods_id'] ?? $goods['goods_Id'] ?? $goods['id'] ?? 0);
            if ($goodsId <= 0) {
                continue;
            }
            $sortId = $goods['sort_id'] ?? 0;
            if (!isset($result[$sortId])) {
                $result[$sortId] = [
                    'id' => $sortId,
                    'name' => $categoryMap[$sortId] ?? '未分类',
                    'commodity' => []
                ];
            }
            $deliveryWay = $this->resolveDeliveryWay($goods);
            if ($deliveryWay === null) {
                $deliveryWay = 0;
            }
            $result[$sortId]['commodity'][] = [
                'id' => $goodsId,
                'code' => $goodsId, // EMSHOP 使用商品 ID 作为 code
                'name' => $goods['title'] ?? ($goods['name'] ?? ''),
                'price' => $goods['price'] ?? 0,
                'cover' => $goods['cover'] ?? '',
                'delivery_way' => (int)$deliveryWay
            ];
        }

        return ['category' => array_values($result)];
    }

    /**
     * 获取商品详情
     *
     * @param int $goodsId 商品ID
     * @return array|false
     */
    public function getItem($goodsId)
    {
        $data = $this->request('getGoodsInfo', [
            'goods_id' => (int)$goodsId
        ]);

        if (!$data) return false;
        return $this->normalizeGoodsInfo($data);
    }

    /**
     * 获取规格模板信息
     *
     * @param int $goodsId 商品ID
     * @param int $groupId 规格模板ID
     * @return array|false
     */
    public function getSkuTemplate($goodsId, $groupId = 0)
    {
        return $this->request('getSkuTemplateInfo', [
            'goods_id' => (int)$goodsId,
            'goods_type_id' => (int)$groupId
        ]);
    }

    /**
     * 获取库存
     *
     * @param int $goodsId 商品ID
     * @param string $skuIds SKU 组合（如 "1-3"）
     * @return int
     */
    public function getStock($goodsId, $skuIds = '0')
    {
        $data = $this->request('getGoodsInfo', [
            'goods_id' => (int)$goodsId,
            'sku_ids' => $skuIds === '' ? '0' : $skuIds
        ]);

        if (!$data) return 0;

        $data = $this->normalizeGoodsInfo($data);
        if (!isset($data['skus']) || !is_array($data['skus'])) return 0;

        $key = $skuIds ?: '0';
        if (isset($data['skus'][$key])) {
            return (int)($data['skus'][$key]['stock'] ?? 0);
        }

        // 如果找不到对应的 SKU，返回第一个 SKU 的库存
        foreach ($data['skus'] as $sku) {
            return (int)($sku['stock'] ?? 0);
        }

        return 0;
    }

    /**
     * 检查库存是否足够
     *
     * @param int $goodsId 商品ID
     * @param int $quantity 购买数量
     * @param string $skuIds SKU 组合
     * @return bool
     */
    public function checkStock($goodsId, $quantity, $skuIds = '0')
    {
        $stock = $this->getStock($goodsId, $skuIds);
        return $stock >= $quantity;
    }

    /**
     * 下单/发货
     *
     * @param int $goodsId 商品ID
     * @param int $quantity 购买数量
     * @param string $requestNo 本地订单号（防重复）
     * @param string $skuIds SKU 组合
     * @param array $attach 附加信息
     * @return array|false
     */
    public function trade($goodsId, $quantity, $requestNo, $skuIds = '0', $config = [])
    {
        $params = [
            'goods_id' => (int)$goodsId,
            'quantity' => (int)$quantity,
            'config' => $config
        ];
        $params['sku_ids'] = empty($skuIds) ? '0' : explode('-', $skuIds);


        $result = $this->request('submitOrder', $params);

        if (!$result) return false;

        if (is_array($result) && array_key_exists('code', $result) && $result['code'] !== 0) {
            
            $this->lastError = $result['msg'] ?? '下单失败';
            return false;
        }

        $tradeNo = '';
        $orderId = 0;
        $secret = '';
        $status = 1;

        if (is_array($result)) {
            $tradeNo = $result['trade_no'] ?? $result['out_trade_no'] ?? '';
            $orderId = (int)($result['order_id'] ?? 0);

            if (array_key_exists('secret', $result)) {
                $secret = (string)$result['secret'];
            } elseif (array_key_exists('content', $result)) {
                if (is_array($result['content'])) {
                    $secret = implode("\r\n", $result['content']);
                } else {
                    $secret = (string)$result['content'];
                }
            }

            if (isset($result['status'])) {
                $status = (int)$result['status'];
            } else {
                $status = $secret !== '' ? 2 : 1;
            }
        } else {
            $tradeNo = (string)$result;
        }

        if ($tradeNo === '') {
            $tradeNo = $requestNo;
        }

        // 返回统一格式
        return [
            'trade_no' => $tradeNo,
            'order_id' => $orderId,
            'secret' => $secret,
            'status' => $status
        ];
    }

    /**
     * 查询订单
     *
     * @param string $tradeNo 订单号
     * @return array|false
     */
    public function query($tradeNo)
    {
        // EMSHOP 暂未实现订单查询 API，后续可扩展
        $this->lastError = '订单查询功能暂未实现';
        return false;
    }

    /**
     * 判断商品类型是否自动发货
     *
     * @param string $type 商品类型
     * @return bool
     */
    private function isAutoDelivery($type)
    {
        $autoTypes = ['once', 'general'];
        return in_array($type, $autoTypes);
    }

    /**
     * 规范化商品详情结构，兼容原始数据返回
     *
     * @param array $data
     * @return array
     */
    private function normalizeGoodsInfo($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $normalized = $data;

        if (!isset($normalized['name']) && isset($data['title'])) {
            $normalized['name'] = $data['title'];
        }
        if (!isset($normalized['description']) && isset($data['content'])) {
            $normalized['description'] = $data['content'];
        }

        if (isset($data['skus']) && is_array($data['skus']) && array_key_exists('option_value', $data['skus'])) {
            $normalized['skus_raw'] = $data['skus'];
            $normalized['skus'] = $data['skus']['option_value'] ?? [];
            if (empty($normalized['spec']) && !empty($data['skus']['option_name'])) {
                $normalized['spec'] = $this->normalizeSpecFromOptionName($data['skus']['option_name']);
            }

            if (!empty($normalized['skus']) && is_array($normalized['skus'])) {
                foreach ($normalized['skus'] as $key => $sku) {
                    if (is_array($sku)) {
                        $normalized['skus'][$key] = $this->normalizeSkuPrices($sku);
                    }
                }
            }
        }

        $deliveryWay = $normalized['delivery_way'] ?? $this->resolveDeliveryWay($normalized);
        if ($deliveryWay !== null) {
            $normalized['delivery_way'] = (int)$deliveryWay;
        }

        if (array_key_exists('is_auto', $normalized)) {
            $normalized['is_auto'] = $normalized['is_auto'] ? 1 : 0;
        } elseif ($deliveryWay !== null) {
            $normalized['is_auto'] = $deliveryWay === 1 ? 0 : 1;
        }

        return $normalized;
    }

    /**
     * 规范化规格结构
     *
     * @param array $optionName
     * @return array
     */
    private function normalizeSpecFromOptionName($optionName)
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
     * 规格价格字段单位统一为元
     *
     * @param array $sku
     * @return array
     */
    private function normalizeSkuPrices($sku)
    {
        $priceFields = ['guest_price', 'user_price', 'market_price', 'cost_price'];
        foreach ($priceFields as $field) {
            if (isset($sku[$field]) && is_numeric($sku[$field])) {
                $sku[$field] = $sku[$field] / 100;
            }
        }
        return $sku;
    }

    /**
     * 解析发货方式（0=自动，1=人工）
     *
     * @param array $goods
     * @return int|null
     */
    private function resolveDeliveryWay($goods)
    {
        if (!is_array($goods)) {
            return null;
        }

        if (array_key_exists('is_auto', $goods)) {
            $isAuto = $goods['is_auto'];
            if (is_string($isAuto)) {
                $flag = strtolower($isAuto);
                if (in_array($flag, ['y', 'yes', 'true', '1'], true)) {
                    $isAuto = true;
                } elseif (in_array($flag, ['n', 'no', 'false', '0'], true)) {
                    $isAuto = false;
                }
            }
            return $isAuto ? 0 : 1;
        }

        if (isset($goods['type'])) {
            $manualTypes = ['service', 'em_manual'];
            return in_array($goods['type'], $manualTypes, true) ? 1 : 0;
        }

        return null;
    }

    /**
     * 解析 SKU 规格
     *
     * @param array $skus SKU 数据
     * @param array $spec 规格数据
     * @return array 规格组合列表
     */
    public static function parseSpecs($skus, $spec = [])
    {
        if (empty($skus)) return [];

        $races = [];
        foreach ($skus as $key => $sku) {
            if ($key === '0' || $key === 0) continue;

            $races[] = [
                'sku' => $key,
                'price' => $sku['cost_price'] ?? $sku['user_price'] ?? 0,
                'stock' => $sku['stock'] ?? 0
            ];
        }

        return $races;
    }

    /**
     * 解析卡密内容
     *
     * @param string $secret 卡密字符串
     * @return array 卡密数组
     */
    public static function parseSecrets($secret)
    {
        if (empty($secret)) return [];

        // 按换行符分割
        $lines = array_filter(preg_split('/[\r\n]+/', $secret));
        return array_map('trim', $lines);
    }

    /**
     * 修复图片URL
     *
     * @param string $url 图片URL
     * @param string $domain 站点域名
     * @return string
     */
    public static function fixImageUrl($url, $domain)
    {
        if (empty($url)) return '';

        if (strpos($url, 'http') === 0) {
            return $url;
        }

        return rtrim($domain, '/') . '/' . ltrim($url, '/');
    }
}
