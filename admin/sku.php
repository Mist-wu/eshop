<?php
/**
 * sort manager
 * @package EMLOG
 * @link https://www.emlog.net
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$skuModel = new Sku_Model();

if (empty($action)) {

    $br = '<a href="./">控制台</a><a href="./goods.php">商品管理</a><a><cite>商品规格</cite></a>';

    include View::getAdmView('header');
    require_once View::getAdmView('templates/default/sku/index');
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $skus = $skuModel->getSkus();

//    d($skus);die;

    foreach($skus as $key => $val){
        $skus[$key]['sku_attrs_text'] = "";
        if(!empty($val['sku_attrs'])){
            foreach($val['sku_attrs'] as $v){
                $skus[$key]['sku_attrs_text'] .= $v['title'] . ",";
            }
            $skus[$key]['sku_attrs_text'] = trim($skus[$key]['sku_attrs_text'], ',');
        }
    }
//    d($skus);die;

    output::data($skus, count($skus));
}


if($action == 'add'){

    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/sku/add');
    include View::getAdmView('open_foot');
    View::output();
}

if($action == 'add_ajax'){
    $title = Input::postStrVar('title');
    $name = Input::postStrArray('name');
    $value = Input::postStrArray('value');
    if(empty($title)){
        Ret::error('请输入规格模板名称');
    }
    if(empty($name)){
        Ret::error('请添加规格名称');
    }
    foreach($name as $val){
        if(empty($val)){
            Ret::error('规格名称不能为空');
        }
    }
    if(empty($value)){
        Ret::error('请添加规格值');
    }
    foreach($value as $val){
        foreach($val as $v){
            if(empty($v)){
                Ret::error('规格值不能为空');
            }
        }
    }
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $sql = "INSERT INTO `{$db_prefix}attribute_group` (`group_name`) VALUES ('{$title}')";
    $db->query($sql);
    $type_id = $db->insert_id();

    foreach($name as $key => $val){
        foreach($value as $k => $v){
            if($key == $k){
                $sql = "INSERT INTO `{$db_prefix}specification` (`group_id`, `spec_name`) VALUES ('{$type_id}', '{$val}')";
                $db->query($sql);
                $attr_id = $db->insert_id();
                foreach($v as $skv){
                    $sql = "INSERT INTO `{$db_prefix}spec_option` (`spec_id`, `option_name`) VALUES ('{$attr_id}', '{$skv}')";
                    $db->query($sql);
                }
            }
        }
    }
    output::ok();

}

/**
 * 编辑规格模板
 */
if($action == 'edit_ajax'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $type_id = Input::postStrVar('type_id');
    $title = Input::postStrVar('title');
    $name = Input::postStrArray('name');
    $value = Input::postStrArray('value');

    $new_name = Input::postStrArray('new_name');
    $new_value = Input::postStrArray('new_value');


    if(empty($title)){
        Ret::error('请输入规格模板名称');
    }

    $sql = "UPDATE `{$db_prefix}attribute_group` SET `group_name` = '{$title}' WHERE `id` = {$type_id}";
    $db->query($sql);

    // 删除
    $sql = "select * from {$db_prefix}attribute_group where id={$type_id}";
    $tpl = $db->once_fetch_array($sql);

    $sql = "select * from {$db_prefix}specification where group_id={$type_id} and delete_time is null";
    $sku_attr = $db->fetch_all($sql);

    $attr_ids = [];
    foreach($sku_attr as $val){
        $attr_ids[] = $val['id'];
    }
    if(empty($attr_ids)){
        $sku_value = [];
    }else{
        $attr_ids = implode(',', $attr_ids);
        $sql = "select * from {$db_prefix}spec_option where spec_id in($attr_ids) and delete_time is null";
        $sku_value = $db->fetch_all($sql);
    }
    



    $attr_ids = array_column($sku_attr, 'id', 'id');
    $name_ids = array_column($name, 'id', 'id');

    $diff = array_filter($sku_attr, function($item) use ($name_ids) {
        // 检查当前元素的id是否在数组1的id列表中
        return !isset($name_ids[$item['id']]);
    });
    $diff_name = array_values($diff);

    $timestamp = time();
    foreach($diff_name as $val){
        $sql = "UPDATE `{$db_prefix}specification` SET `delete_time` = '{$timestamp}' WHERE `id` = {$val['id']}";
        $db->query($sql);
    }

    $ids1 = array_column($sku_value, 'id'); // [15,16,17,18,20,22,23,24]
    $ids2 = [];
    foreach ($value as $group) {
        $ids2 = array_merge($ids2, $group['id']);
    }
    $ids2 = array_unique($ids2); // [15,16,22,24,17,18,20]

    $diffIds = array_merge(array_diff($ids1, $ids2), array_diff($ids2, $ids1));
    $diffElements = array_filter($sku_value, function($item) use ($diffIds) {
        return in_array($item['id'], $diffIds);
    });
    $diff_value = array_values($diffElements);
    foreach($diff_value as $val){
        $sql = "UPDATE `{$db_prefix}spec_option` SET `delete_time` = '{$timestamp}' WHERE `id` = {$val['id']}";
        $db->query($sql);
    }

    // 修改
    foreach($name as $key => $val){
        if($val['old'] != $val['new']){
            // 修改规格名
            $sql = "UPDATE `{$db_prefix}specification` SET `spec_name` = '{$val['new']}' WHERE `id` = {$val['id']}";
            $db->query($sql);
        }
    }
    foreach($value as $key => $val){
        foreach($val['id'] as $k => $v){
            if($val['old'][$k] != $val['new'][$k]){
                // 修改规格值
                $sql = "UPDATE `{$db_prefix}spec_option` SET `option_name` = '" . $val['new'][$k] . "' WHERE `id` = {$v}";
                $db->query($sql);
            }
        }
    }

    // 新增规格值 （已有规格）
    foreach($name as $key => $val){
        foreach($new_value as $k => $v){
            if($key == $k){
                // 新增
                 foreach($v as $sku_value){
                     $sql = "INSERT INTO `{$db_prefix}spec_option` (`spec_id`, `option_name`) VALUES ({$val['id']}, '{$sku_value}')";
                     $db->query($sql);
                 }

            }
        }
    }
    // 新增规格与规格值
    foreach($new_name as $key => $val){
        foreach($new_value as $k => $v){
            if($key == $k){
                $sql = "INSERT INTO `{$db_prefix}specification` (`group_id`, `spec_name`) VALUES ('{$type_id}', '{$val}')";
                $db->query($sql);
                $attr_id = $db->insert_id();
                foreach($v as $skv){
                    $sql = "INSERT INTO `{$db_prefix}spec_option` (`spec_id`, `option_name`) VALUES ('{$attr_id}', '{$skv}')";
                    $db->query($sql);
                }
            }
        }
    }

    output::ok();
}

if($action == 'edit'){

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $tpl_id = Input::getIntVar('id');
    $sql = "select * from {$db_prefix}attribute_group where id={$tpl_id}";
    $tpl = $db->once_fetch_array($sql);

    $sql = "select * from {$db_prefix}specification where group_id={$tpl_id} and delete_time is null";
    $sku_attr = $db->fetch_all($sql);

    $attr_ids = [];
    foreach($sku_attr as $val){
        $attr_ids[] = $val['id'];
    }
    
    if(empty($attr_ids)){
        $sku_value = [];
    }else{
        $attr_ids = implode(',', $attr_ids);
        $sql = "select * from {$db_prefix}spec_option where spec_id in($attr_ids) and delete_time is null";
        $sku_value = $db->fetch_all($sql);
    }
    




    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/sku/edit');
    include View::getAdmView('open_foot');
    View::output();
}


if($action == 'detail'){
    $type_id = Input::getIntVar('type_id');

    $cate = $skuModel->getCate($type_id);

    $list = $skuModel->getDetail($type_id);

//d($list);die;

    $br = '<ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="./">控制台</a></li>
        <li class="breadcrumb-item"><a href="./goods.php">商品管理</a></li>
        <li class="breadcrumb-item"><a href="./sku.php">规格属性</a></li>
        <li class="breadcrumb-item active" aria-current="page">' . $cate['name'] . '</li>
    </ol>';

    include View::getAdmView('header');
    require_once View::getAdmView('sku_detail');
    include View::getAdmView('footer');
    View::output();
}



if ($action == 'save') {
    $id = Input::postIntVar('id');
    $type = Input::postStrVar('type');



    if (empty($type)) {
        emDirect("./sku.php?error_a=1");
    }



    if ($id) {
        $Sort_Model->updateSort($sort_data, $sid);
    } else {
        $type_id = $skuModel->addGoodsType(['name' => $type]);
    }

    doAction('save_sku', $sid, $sort_data);

    $CACHE->updateCache(['sort', 'logsort', 'navi']);
    emDirect("./sku.php?active_save=1");
}

if ($action == 'del') {
    $sid = Input::getIntVar('sid');

    LoginAuth::checkToken();

    $Sort_Model->deleteSort($sid);
    $CACHE->updateCache(['sort', 'logsort', 'navi']);
    emDirect("./sort.php?active_del=1");
}

if ($action == 'delAttrValue') {
    $value_id = Input::getIntVar('id');
    LoginAuth::checkToken();
    $type_id = $skuModel->deleteSkuValue($value_id);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}

if($action == 'del_sku_attr'){
    $type_id = Input::getIntVar('type_id');
    $id = Input::getIntVar('id');
    $skuModel->deleteSkuAttr($id);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}

if($action == 'del_sku_cate'){
    $ids = Input::postStrVar('ids');
    $ids = explode(',', $ids);
    foreach($ids as $val){
        $skuModel->deleteSkuCate($val);
    }
    output::ok();
}

if($action == 'edit_sku_value'){
    $content = Input::getStrVar('content');
    $type_id = Input::getStrVar('type_id');
    $id = Input::getStrVar('id');
    $skuModel->editSkuValue($id, $content);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}

if($action == 'update_sku_attr'){
    $content = Input::getStrVar('content');
    $type_id = Input::getStrVar('type_id');
    $id = Input::getStrVar('id');
    $skuModel->updateSkuAttr($id, $content);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}

if($action == 'update_sku'){
    $content = Input::getStrVar('content');
    $id = Input::getStrVar('id');
    $skuModel->updateSku($id, $content);
    emDirect("./sku.php");
}

if($action == 'add_sku_value'){
    $content = Input::getStrVar('content');
    $type_id = Input::getStrVar('type_id');
    $id = Input::getStrVar('id');
    $skuModel->addSkuValue($id, $content);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}

if($action == 'add_sku_attr'){
    $content = Input::getStrVar('content');
    $type_id = Input::getStrVar('type_id');
    $data = [
        'type_id' => $type_id,
        'title' => $content
    ];
    $skuModel->addSkuAttr($data);
    emDirect("./sku.php?action=detail&type_id=" . $type_id);
}