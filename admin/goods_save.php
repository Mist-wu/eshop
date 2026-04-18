<?php

require_once 'globals.php';
LoginAuth::checkToken();
$goodsModel = new Goods_Model();

$goods_id = Input::postIntVar('goods_id', 0);

$post = [
    'goods_id' => $goods_id,
    'type' => Input::postStrVar('type'),
    'sort_id' => Input::postStrVar('sort_id', -1),
    'title' => Input::postStrVar('title'),
    'cover' => Input::postStrVar('cover'),
    'is_sku' => Input::postStrVar('is_sku'),
    'group_id' => Input::postIntVar('group_id', 0),
    'skus' => Input::postStrArray('skus', []),
    'des' => Input::postStrVar('des'),
    'content' => Input::postStrVar('content'),
    'pay_content' => Input::postStrVar('pay_content'),
    'is_on_shelf' => Input::postIntVar('is_on_shelf', 0),
    'index_top' => Input::postIntVar('index_top', 0),
    'sort_top' => Input::postIntVar('sort_top', 0),
    'sort_num' => Input::postIntVar('sort_num'),
    'min_qty' => Input::postIntVar('min_qty', 1),
    'max_qty' => Input::postIntVar('max_qty', 0),
    'link' => Input::postStrVar('link'),

];
$goods_payment = Input::postStrArray('payment', []);
$post['payment'] = implode(',', $goods_payment);

$_config = Input::postStrArray('config', []);
$config = [];
if(!empty($_config['input'])){
    foreach($_config['input'] as $val){
        if(empty($val['name']) || empty($val['name_en'])){
            continue;
        }
        $config['input'][] = $val;
    }
}
if(!empty($_config['qty_discount'])){
    foreach($_config['qty_discount'] as $val){
        if(empty($val['qty']) || empty($val['value'])){
            continue;
        }
        $config['qty_discount'][] = $val;
    }
}
if(!empty($_config['tier_price'])){
    foreach($_config['tier_price'] as $tier => $val){
        // d($val);
        $tmp = [];
        foreach($val as $k => $v){
            if(!isEmpty($v)) $tmp[$k] = $v;
        }
        $config['tier_price'][$tier] = $tmp;
    }
}
$post['config'] = json_encode($config, JSON_UNESCAPED_UNICODE);

if ($post['min_qty'] < 1) {
    $post['min_qty'] = 1;
}
if ($post['max_qty'] < 0) {
    $post['max_qty'] = 0;
}
if ($post['max_qty'] > 0 && $post['max_qty'] < $post['min_qty']) {
    Ret::error('最大购买数量不能小于最小购买数量');
}

// d($post);die;
if(empty($post['type'])) Ret::error('请选择商品类型');
if($post['sort_id'] == -1) Ret::error('请选择商品分类');
if(empty($post['title'])) Ret::error('请输入商品名称');
if(empty($post['is_sku'])) Ret::error('请选择规格类型');
if($post['is_sku'] == 'n'){
    if(isEmpty($post['skus']['guest_price'])) Ret::error('请输入游客访问价格');
    $post['skus']['user_price'] = isEmpty($post['skus']['user_price']) ? $post['skus']['guest_price'] : $post['skus']['user_price'];
    $post['skus']['market_price'] = isEmpty($post['skus']['market_price']) ? $post['skus']['guest_price'] : $post['skus']['market_price'];
    $post['skus']['cost_price'] = isEmpty($post['skus']['cost_price']) ? 0 : $post['skus']['cost_price'];
    $post['skus']['sales'] = empty($post['skus']['sales']) ? 0 : $post['skus']['sales'];
    $post['skus']['member'] = empty($post['skus']['member']) ? [] : $post['skus']['member'];
    foreach($post['skus']['member'] as $key => $val){
        if(isEmpty($val)){
            unset($post['skus']['member'][$key]);
        }else{
            $post['skus']['member'][$key] = $val;
        }
    }
}
if($post['is_sku'] == 'y'){
    // 对接商品（group_id = -1）跳过规格模板验证
    if($post['group_id'] != -1 && empty($post['group_id'])) Ret::error('请选择规格模板');
    if(empty($post['skus'])) Ret::error('请设置规格属性');
    foreach($post['skus'] as $key => $val){
        if(empty($val['guest_price'])) Ret::error('请输入游客访问价格');
        $post['skus'][$key]['user_price'] = isEmpty($val['user_price']) ? $val['guest_price'] : $val['user_price'];
        $post['skus'][$key]['market_price'] = isEmpty($val['market_price']) ? $val['guest_price'] : $val['market_price'];
        $post['skus'][$key]['cost_price'] = isEmpty($val['cost_price']) ? 0 : $val['cost_price'];
        $post['skus'][$key]['sales'] = empty($val['sales']) ? 0 : $val['sales'];
        $post['skus'][$key]['member'] = empty($val['member']) ? [] : $val['member'];
        foreach($post['skus'][$key]['member'] as $k => $v){
            if(isEmpty($v)){
                unset($post['skus'][$key]['member'][$k]);
            }else{
                $post['skus'][$key]['member'][$k] = $v;
            }
        }
    }
}

doAction('save_goods_after');

if(empty($post['goods_id'])){
    $goodsModel->addGoods($post);
    die(json_encode(['msg' => '商品已添加', 'type' => 'add']));
}else{
    $goodsModel->editGoods($post['goods_id'], $post);
    die(json_encode(['msg' => '商品已更新', 'type' => 'edit']));
}

