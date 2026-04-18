<?php
/**
 * SKU API - 商品规格统一接口
 * @package ESHOP
 */

require_once 'globals.php';

// sleep(2); // 模拟网络延迟，测试加载效果

$action = Input::requestStrVar('action');
$db = Database::getInstance();

switch ($action) {
    /**
     * 初始化 - 返回 SKU 组件所需的所有数据
     */
    case 'init':
        $goods_id = Input::getIntVar('goods_id', 0);
        $group_id = Input::getIntVar('type_id', 0);
        $goods_type = Input::getStrVar('goods_type', '');
        $goods_config = [];

        // 获取所有规格模板
        $templates = $db->fetch_all(
            "SELECT id, group_name as title FROM " . DB_PREFIX . "attribute_group
             WHERE hide='n' AND delete_time IS NULL ORDER BY id DESC"
        );

        // 获取所有会员等级
        $userTierModel = new User_Tier_Model();
        $members = $userTierModel->getTiersAll();

        // 构建价格字段
        $price_fields = getPriceFields($members);

        // 默认值
        $mode = 'single';
        $spec = [];
        $sku_data = [];
        $remote_skus = []; // 对接商品的 SKU 组合
        $remote_spec_names = [];

        // 如果是编辑已有商品
        if ($goods_id > 0) {
            $goods = $db->once_fetch_array(
                "SELECT is_sku, group_id, type, config FROM " . DB_PREFIX . "goods WHERE id = {$goods_id}"
            );
            if ($goods) {
                $group_id = (int)$goods['group_id'];
                $goods_type = $goods['type'];
                if (!empty($goods['config'])) {
                    $decoded_config = json_decode($goods['config'], true);
                    if (is_array($decoded_config)) {
                        $goods_config = $decoded_config;
                    }
                }

                // 对接商品 (group_id = -1)
                if ($group_id == -1) {
                    $mode = 'remote';

                    // 通过 Hook 让插件提供远程规格数据
                    // 插件应填充 $remote_spec_result 中的:
                    // - sku_combinations: 规格组合数组，如 ['月费', '季费', '年费'] 或空数组(单规格)
                    // - sku_data: SKU 价格数据，如 ['月费' => [...], '季费' => [...]]
                    $hook_input = [
                        'goods_id' => $goods_id,
                        'goods_type' => $goods_type
                    ];
                    $remote_spec_result = [];
                    doMultiAction('sku_get_remote_spec', $hook_input, $remote_spec_result);

                    // 如果 Hook 返回了远程规格数据
                    if (!empty($remote_spec_result)) {
                        // 远程返回的 SKU 组合
                        if (!empty($remote_spec_result['sku_combinations'])) {
                            $remote_skus = $remote_spec_result['sku_combinations'];
                        }
                        // 远程返回的规格标题
                        if (!empty($remote_spec_result['spec_names']) && is_array($remote_spec_result['spec_names'])) {
                            $remote_spec_names = $remote_spec_result['spec_names'];
                        }
                        // 远程返回的价格数据（作为默认值，本地数据优先）
                        if (!empty($remote_spec_result['sku_data'])) {
                            foreach ($remote_spec_result['sku_data'] as $sku_key => $remote_prices) {
                                if (!isset($sku_data[$sku_key])) {
                                    $sku_data[$sku_key] = $remote_prices;
                                }
                            }
                        }
                    }
                } else {
                    $mode = $goods['is_sku'] === 'y' ? 'multi' : 'single';
                }
            }

            // 获取本地已有的 SKU 数据
            $local_sku_data = getSkuDataForGoods($goods_id, $goods_config);
            // 本地数据优先，合并到 sku_data
            $sku_data = array_merge($sku_data, $local_sku_data);
        }

        // 如果有规格模板，获取规格数据（对接商品不需要）
        if ($group_id > 0) {
            $spec = getSpecDataForTemplate($group_id, $goods_id);
        }

        Output::ok([
            'mode' => $mode,
            'type_id' => $group_id,
            'templates' => $templates,
            'members' => $members,
            'price_fields' => $price_fields,
            'spec' => $spec,
            'sku_data' => $sku_data,
            'remote_skus' => $remote_skus, // 对接商品的 SKU 组合
            'remote_spec_names' => $remote_spec_names
        ]);
        break;

    /**
     * 加载规格数据 - 切换模板时调用
     */
    case 'load_spec':
        $group_id = Input::getIntVar('type_id', 0);
        $goods_id = Input::getIntVar('goods_id', 0);

        if ($group_id <= 0) {
            Output::ok(['spec' => []]);
            break;
        }

        $spec = getSpecDataForTemplate($group_id, $goods_id);
        Output::ok(['spec' => $spec]);
        break;

    /**
     * 渲染单规格表格 HTML
     */
    case 'render_single':
        $price_fields = json_decode(stripslashes(Input::getStrVar('price_fields', '[]')), true);
        $sku_data = json_decode(stripslashes(Input::getStrVar('sku_data', '{}')), true);

        ob_start();
        include dirname(__FILE__) . '/views/components/sku/_single.php';
        $html = ob_get_clean();

        echo $html;
        exit;

    /**
     * 渲染多规格表格 HTML
     */
    case 'render_multi':
        $combinations = json_decode(stripslashes(Input::requestStrVar('combinations', '[]')), true);
        $price_fields = json_decode(stripslashes(Input::requestStrVar('price_fields', '[]')), true);
        $sku_data = json_decode(stripslashes(Input::requestStrVar('sku_data', '{}')), true);
        $spec_names = json_decode(stripslashes(Input::requestStrVar('spec_names', '[]')), true);

        // 如果 sku_data 是索引数组（键是0,1,2...），根据 combinations 顺序重新映射为关联数组
        if (!empty($sku_data) && !empty($combinations) && isset($sku_data[0])) {
            $remapped_sku_data = [];
            foreach ($combinations as $index => $combo) {
                if (isset($sku_data[$index])) {
                    $remapped_sku_data[$combo['sku']] = $sku_data[$index];
                }
            }
            $sku_data = $remapped_sku_data;
        }

        $sku_combinations = $combinations;

        ob_start();
        include dirname(__FILE__) . '/views/components/sku/_multi.php';
        $html = ob_get_clean();

        echo $html;
        exit;

    /**
     * 渲染规格选择表格 HTML
     */
    case 'render_spec':
        $specs = json_decode(stripslashes(Input::getStrVar('specs', '[]')), true);

        ob_start();
        include dirname(__FILE__) . '/views/components/sku/_spec_table.php';
        $html = ob_get_clean();

        echo $html;
        exit;

    /**
     * 创建规格属性
     */
    case 'create_spec':
        $title = Input::requestStrVar('title');
        $group_id = Input::getStrVar('goods_type_id');

        if (empty($title)) {
            Output::error('请输入规格名称');
        }

        // 检查是否已存在
        $exists = $db->once_fetch_array(
            "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "specification
             WHERE group_id='{$group_id}' AND spec_name='{$title}' AND delete_time IS NULL"
        );
        if ($exists['total'] > 0) {
            Output::error('该规格已存在');
        }

        $db->query(
            "INSERT INTO " . DB_PREFIX . "specification (group_id, spec_name)
             VALUES ('{$group_id}', '{$title}')"
        );
        $spec_id = $db->insert_id();

        Output::ok(['id' => $spec_id]);
        break;

    /**
     * 创建规格值
     */
    case 'create_value':
        $name = Input::requestStrVar('title');
        $spec_id = Input::requestIntVar('spec_id');

        if (empty($name)) {
            Output::error('请输入规格值名称');
        }

        // 检查是否已存在
        $exists = $db->once_fetch_array(
            "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "spec_option
             WHERE spec_id='{$spec_id}' AND option_name='{$name}' AND delete_time IS NULL"
        );
        if ($exists['total'] > 0) {
            Output::error('该规格值已存在');
        }

        $db->query(
            "INSERT INTO " . DB_PREFIX . "spec_option (spec_id, option_name)
             VALUES ('{$spec_id}', '{$name}')"
        );
        $value_id = $db->insert_id();

        Output::ok(['id' => $value_id]);
        break;

    default:
        Output::error('无效的操作');
}

/**
 * 根据会员等级构建价格字段数组
 */
function getPriceFields($members) {
    $fields = [
        ['name' => 'guest_price', 'label' => '游客访问(元)', 'required' => true],
        ['name' => 'user_price', 'label' => '登录用户(元)', 'required' => false],
    ];

    // 添加会员等级字段
    foreach ($members as $m) {
        $fields[] = [
            'name' => 'member_' . $m['id'],
            'label' => $m['tier_name'] . '(元)',
            'required' => false,
            'member_id' => $m['id']
        ];
    }

    $fields[] = ['name' => 'market_price', 'label' => '市场价(元)', 'required' => false];
    $fields[] = ['name' => 'cost_price', 'label' => '成本价(元)', 'required' => false];
    $fields[] = ['name' => 'sales', 'label' => '销量', 'required' => false, 'type' => 'number'];

    return $fields;
}

/**
 * 获取已有商品的 SKU 数据
 */
function getSkuDataForGoods($goods_id, $goods_config = []) {
    $db = Database::getInstance();
    $sku_data = [];

    // 获取 SKU 记录
    $skus = $db->fetch_all(
        "SELECT * FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods_id}"
    );

    foreach ($skus as $sku) {
        $sku_key = $sku['option_ids'];
        $sku_data[$sku_key] = [
            'guest_price' => $sku['guest_price'] / 100,
            'user_price' => $sku['user_price'] / 100,
            'market_price' => $sku['market_price'] / 100,
            'cost_price' => $sku['cost_price'] / 100,
            'sales' => $sku['sales'],
            'stock' => $sku['stock']
        ];
    }

    // 从 goods.config 读取会员价格（支持 tier_price 和 member_price 两种结构）
    $tier_price_config = [];
    if (!empty($goods_config['tier_price'])) {
        $tier_price_config = $goods_config['tier_price'];
    } elseif (!empty($goods_config['member_price'])) {
        $tier_price_config = convertMemberPriceToTierPrice($goods_config['member_price']);
    }
    if (!empty($tier_price_config)) {
        $tier_price_config = normalizeTierPriceConfig($tier_price_config);
        foreach ($tier_price_config as $tier_id => $prices) {
            foreach ($prices as $sku_key => $price) {
                if (!isset($sku_data[$sku_key])) {
                    $sku_data[$sku_key] = [];
                }
                $sku_data[$sku_key]['member_' . $tier_id] = $price;
            }
        }
    }

    return $sku_data;
}

/**
 * 将 member_price 结构（sku_key => tier_id => price）转换为 tier_price（tier_id => sku_key => price）
 */
function convertMemberPriceToTierPrice($member_price) {
    $tier_price = [];
    if (!is_array($member_price)) {
        return $tier_price;
    }
    foreach ($member_price as $sku_key => $tiers) {
        if (!is_array($tiers)) {
            continue;
        }
        foreach ($tiers as $tier_id => $price) {
            if (isEmpty($price)) {
                continue;
            }
            $tier_price[(string)$tier_id][(string)$sku_key] = $price;
        }
    }
    return $tier_price;
}

/**
 * 规范化 tier_price 结构为 tier_id => sku_key => price
 */
function normalizeTierPriceConfig($tier_price) {
    $normalized = [];
    if (!is_array($tier_price)) {
        return $normalized;
    }
    foreach ($tier_price as $tier_id => $prices) {
        if (!is_array($prices)) {
            if (isEmpty($prices)) {
                continue;
            }
            $normalized[(string)$tier_id]['0'] = $prices;
            continue;
        }
        $keys = array_keys($prices);
        $is_assoc = $keys !== range(0, count($keys) - 1);
        if ($is_assoc) {
            foreach ($prices as $sku_key => $price) {
                if (isEmpty($price)) {
                    continue;
                }
                $normalized[(string)$tier_id][(string)$sku_key] = $price;
            }
        } else {
            if (!empty($prices)) {
                $normalized[(string)$tier_id]['0'] = $prices[0];
            }
        }
    }
    return $normalized;
}

/**
 * 获取规格模板的规格数据
 */
function getSpecDataForTemplate($group_id, $goods_id = 0) {
    $db = Database::getInstance();

    // 获取规格属性
    $specs = $db->fetch_all(
        "SELECT id, spec_name as title FROM " . DB_PREFIX . "specification
         WHERE group_id = {$group_id} AND delete_time IS NULL ORDER BY id ASC"
    );

    if (empty($specs)) {
        return [];
    }

    $spec_ids = array_column($specs, 'id');

    // 获取规格值
    $options = $db->fetch_all(
        "SELECT id, spec_id, option_name as title FROM " . DB_PREFIX . "spec_option
         WHERE spec_id IN (" . implode(',', $spec_ids) . ") AND delete_time IS NULL ORDER BY id ASC"
    );

    // 获取该商品已选中的规格值
    $selected_values = [];
    if ($goods_id > 0) {
        $skus = $db->fetch_all(
            "SELECT option_ids FROM " . DB_PREFIX . "product_sku WHERE goods_id = {$goods_id}"
        );
        foreach ($skus as $sku) {
            $ids = explode('-', $sku['option_ids']);
            $selected_values = array_merge($selected_values, $ids);
        }
        $selected_values = array_unique($selected_values);
    }

    // 构建规格结构
    $spec = [];
    foreach ($specs as $s) {
        $spec_options = [];
        $selected = [];

        foreach ($options as $opt) {
            if ($opt['spec_id'] == $s['id']) {
                $spec_options[] = [
                    'id' => $opt['id'],
                    'title' => $opt['title']
                ];
                if (in_array($opt['id'], $selected_values)) {
                    $selected[] = $opt['id'];
                }
            }
        }

        $spec[] = [
            'id' => $s['id'],
            'title' => $s['title'],
            'options' => $spec_options,
            'value' => $selected
        ];
    }

    return $spec;
}
