<?php
/**
 * @package ESHOP
 */



require_once 'globals.php';

$Sort_Model = new Sort_Model();

if(empty($action)) {

    $type = Input::getStrVar('type', 'goods');
    $sorts = $Sort_Model->getSorts($type);


    if($type == 'goods'){
        $br = '<a href="./">控制台</a><a href="./goods.php">商品管理</a><a><cite>商品分类</cite></a>';
    }else{
        $br = '<a href="./">控制台</a><a href="./blog.php">博客管理</a><a><cite>文章分类</cite></a>';
    }


    include View::getAdmView('header');
    require_once View::getAdmView('templates/default/sort/index');
    include View::getAdmView('footer');
    View::output();
}
if($action == 'index'){
    $type = Input::getStrVar('type', 'goods');
    $sorts = $Sort_Model->getSorts($type);
    $items = [];
    foreach ($sorts as $sid => $sort) {
        $sort['children'] = [];
        $items[$sid] = $sort;
    }
    $treeSorts = [];
    foreach ($items as $sid => &$sort) {
        $pid = $sort['pid'];
        if ($pid && isset($items[$pid])) {
            $items[$pid]['children'][] = &$sort;
        } else {
            $treeSorts[] = &$sort;
        }
    }
    unset($sort);
    output::data($treeSorts, count($items));
}
if($action == 'add'){
    $type = Input::getStrVar('type', 'goods');
    $sorts = $Sort_Model->getSorts($type);

    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/sort/add');
    include View::getAdmView('open_foot');
    View::output();
}

if($action == 'edit'){
    $type = Input::getStrVar('type', 'goods');
    $sorts = $Sort_Model->getSorts($type);
    $id = Input::getIntVar('id');

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $sql = "select * from {$db_prefix}sort where sid={$id}";
    $data = $db->once_fetch_array($sql);

    include View::getAdmView('open_head');
    require_once View::getAdmView('templates/default/sort/edit');
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'save') {
    $sid = Input::postIntVar('sid');
    $sortname = Input::postStrVar('sortname');
    $alias = Input::postStrVar('alias');
    $pid = Input::postIntVar('pid');
    $template = isset($_POST['template']) && $_POST['template'] != 'log_list' ? addslashes(trim($_POST['template'])) : '';
    $description = Input::postStrVar('description');
    $kw = Input::postStrVar('kw');
    $title = Input::postStrVar('title');
    $sortimg = Input::postStrVar('sortimg');
    $type = Input::postStrVar('type');
    $taxis = Input::postIntVar('taxis', 0);


    if (empty($sortname)) {
        output::error('请填写分类名');
    }

    if ($sid && $sid == $pid) {
        output::error('夫分类选择错误');
    }

    if (!empty($alias)) {
        if (!preg_match("|^[\w-]+$|", $alias)) {
            output::error('别名错误');
        } elseif (preg_match("|^[0-9]+$|", $alias)) {
            output::error('别名错误');
        } elseif (in_array($alias, array('post', 'record', 'sort', 'tag', 'author', 'page', 'posts'))) {
            output::error('禁止使用此别名');
        } else {
            $sort_cache = $CACHE->readCache('sort');
            if ($sid) {
                unset($sort_cache[$sid]);
            }
            foreach ($sort_cache as $key => $value) {
                if ($alias == $value['alias']) {
                    output::error('此别名已被使用');
                }
            }
        }
    }

    $sort_data = [
        'sortname'    => $sortname,
        'pid'         => $pid,
        'template'    => $template,
        'description' => $description,
        'kw'          => $kw,
        'title'       => $title,
        'alias'       => $alias,
        'sortimg'     => $sortimg,
        'taxis' => $taxis,
        'type' => $type
    ];


    if ($sid) {
        $Sort_Model->updateSort($sort_data, $sid);
    } else {
        $Sort_Model->addSort($sort_data);
    }

    doAction('save_sort', $sid, $sort_data);

    if($type == 'goods'){
        $CACHE->updateCache(['sort', 'logsort', 'navi']);
    }else{
        $CACHE->updateCache(['blog_sort', 'logsort', 'navi']);
    }

    $CACHE->updateCache(['sort', 'logsort', 'navi']);

    output::ok();


}

if ($action == 'del') {
    $sid = Input::postStrVar('ids');
    $type = Input::postStrVar('type');

//    LoginAuth::checkToken();
    $sid = explode(',', $sid);
    foreach($sid as $val){
        $Sort_Model->deleteSort($val);
    }


    if($type == 'goods'){
        $CACHE->updateCache(['sort', 'logsort', 'navi']);
    }else{
        $CACHE->updateCache(['blog_sort', 'logsort', 'navi']);
    }
    output::ok();
}
