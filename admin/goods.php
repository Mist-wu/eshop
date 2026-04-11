<?php

require_once 'globals.php';

$goodsModel = new Goods_Model();
$Sort_Model = new Sort_Model();
$User_Model = new User_Model();
$Media_Model = new Media_Model();
$MediaSort_Model = new MediaSort_Model();
$Template_Model = new Template_Model();


if (empty($action)) {

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perpage_num = Option::get('admin_article_perpage_num');
    $start_limit = !empty($page) ? ($page - 1) * $perpage_num : 0;
    $limit = "LIMIT $start_limit, " . $perpage_num;

    $db = Database::getInstance();

    $sorts = $CACHE->readCache('sort');
    $sorts[] = [
        'sortname' => '未分类',
        'sid' => -1
    ];
    $category_json = [];
    foreach($sorts as $val){
        $category_json[] = [
            'text' => $val['sortname'],
            'value' => $val['sid']
        ];
    }
    $category_json[] = [
        'text' => '未分类',
        'value' => -1
    ];
    $category_json = json_encode($category_json);


    $br = '<a href="./">控制台</a><a href="./goods.php">商品管理</a><a><cite>商品列表</cite></a>';
    include View::getAdmView(User::haveEditPermission() ? 'header' : 'uc_header');
    require_once View::getAdmView('goods');
    include View::getAdmView(User::haveEditPermission() ? 'footer' : 'uc_footer');
    View::output();
}

if($action == 'home_switch'){
    $goods_id = Input::postIntVar('goods_id');
    $home = Input::postStrVar('home');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "update {$db_prefix}goods set home = '{$home}' where id = $goods_id";
    $db->query($sql);
    Ret::success();
}

if($action == 'home_batch'){
    $ids = Input::postStrVar('ids');
    $home = Input::postStrVar('home');
    $home = $home === 'y' ? 'y' : 'n';

    $ids = array_filter(array_map('intval', explode(',', $ids)), function($id){
        return $id > 0;
    });
    if (empty($ids)) {
        Ret::error('请选择商品');
    }

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $id_list = implode(',', $ids);
    $sql = "update {$db_prefix}goods set home = '{$home}' where id in ({$id_list})";
    $db->query($sql);
    Ret::success('操作成功');
}

if($action == 'index'){
    $post = [
        'page' => Input::getIntVar('page', 1),
        'limit' => Input::getIntVar('limit', 10),
        'keyword' => Input::getStrVar('keyword', ''),
        'category_id' => Input::getIntVar('category_id', 0),
        'is_on_shelf' => Input::getStrVar('is_on_shelf', ''),
        'field' => Input::getStrVar('field', ''),
        'order' => Input::getStrVar('order', '')
    ];

    $result = $goodsModel->getGoodsListForAdmin($post);

    output::data($result['list'], $result['total']);
}


if($action == 'shelf'){
    $goods_id = Input::postIntVar('goods_id');
    $is_on_shelf = Input::postIntVar('status');
    $goodsModel->setGoodsOnShelf($goods_id, $is_on_shelf);
    output::ok();
}


if($action == 'del'){
    $ids = Input::postStrVar('ids');
    $ids = explode(',', $ids);
    foreach($ids as $goods_id){
        $goods = $goodsModel->getOneGoodsForAdmin($goods_id);
        $goodsModel->deleteGoods($goods_id);
        doAction('del_product', $goods);
    }
    output::ok();
}

if ($action == 'del') {
    $id = Input::getIntVar('id');
    $isRm = Input::getIntVar('rm');

    LoginAuth::checkToken();
    if ($isRm) {

    } else {
        $goodsModel->hideSwitch($id, 'y');
    }
    $CACHE->updateCache();
    emDirect("./goods.php?&active_del=1");
}

if ($action == 'operate_goods') {
    $operate = Input::requestStrVar('operate');
    $draft = Input::postIntVar('draft');
    $logs = Input::postIntArray('blog');
    $sort = Input::postIntVar('sort');
    $author = Input::postIntVar('author');
    $id = Input::requestNumVar('id');

    LoginAuth::checkToken();

    if (!$operate) {
        emDirect("./goods.php?draft=$draft&error_b=1");
    }
    if (empty($logs) && empty($id)) {
        emDirect("./goods.php?draft=$draft&error_a=1");
    }

    switch ($operate) {
        case 'del':
            foreach ($logs as $val) {
                doAction('before_del_product', $val);
                $goodsModel->deleteProduct($val);
                doAction('del_product', $val);
            }
            $CACHE->updateCache();
            emDirect("./goods.php?draft=1&active_del=1&draft=$draft");
            break;
        case 'top':
            foreach ($logs as $val) {
                $goodsModel->updateLog(array('top' => 'y'), $val);
            }
            emDirect("./goods.php?active_up=1&draft=$draft");
            break;
        case 'sortop':
            foreach ($logs as $val) {
                $goodsModel->updateLog(array('sortop' => 'y'), $val);
            }
            emDirect("./goods.php?active_up=1&draft=$draft");
            break;
        case 'notop':
            foreach ($logs as $val) {
                $goodsModel->updateLog(array('top' => 'n', 'sortop' => 'n'), $val);
            }
            emDirect("./goods.php?active_down=1&draft=$draft");
            break;
        case 'hide':
            foreach ($logs as $val) {
                $goodsModel->hideSwitch($val, 'y');
            }
            $CACHE->updateCache();
            emDirect("./goods.php?active_hide=1&draft=$draft");
            break;
        case 'pub':
            foreach ($logs as $val) {
                $goodsModel->hideSwitch($val, 'n');
                if (User::haveEditPermission()) {
                    $goodsModel->checkSwitch($val, 'y');
                }
            }
            $CACHE->updateCache();
            emDirect("./goods.php?draft=1&active_post=1&draft=$draft");
            break;
        case 'move':
            foreach ($logs as $val) {
                $goodsModel->checkEditable($val);
                $goodsModel->updateLog(array('sortid' => $sort), $val);
            }
            $CACHE->updateCache(array('sort', 'logsort'));
            emDirect("./goods.php?active_move=1&draft=$draft");
            break;
        case 'change_author':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            foreach ($logs as $val) {
                $goodsModel->updateLog(array('author' => $author), $val);
            }
            $CACHE->updateCache('sta');
            emDirect("./goods.php?active_change_author=1&draft=$draft");
            break;
        case 'check':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            if ($logs) {
                foreach ($logs as $id) {
                    $goodsModel->checkSwitch($id, 'y');
                }
            } else {
                $goodsModel->checkSwitch($id, 'y');
            }
            $CACHE->updateCache();
            emDirect("./goods.php?active_ck=1&draft=$draft");
            break;
        case 'uncheck':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            if ($logs) {
                $feedback = '';
                foreach ($logs as $id) {
                    $goodsModel->unCheck($id, $feedback);
                }
            } else {
                $id = Input::postIntVar('id');
                $feedback = Input::postStrVar('feedback');
                $goodsModel->unCheck($id, $feedback);
            }
            $CACHE->updateCache();
            emDirect("./goods.php?active_unck=1&draft=$draft");
            break;
    }
}

if ($action === 'release') {
    $goods = [
        'group_id' => 0,
        'type' => '',
        'is_sku' => 'n',
        'id'    => 0,
        'title'    => '',
        'content'  => '',
        'pay_content'  => '',
        'excerpt'  => '',
        'alias'    => '',
        'sort_id'   => -1,
        'password' => '',
        'hide'     => '',
        'author'   => UID,
        'cover'    => '',
        'link'     => '',
        'template' => '',
        'attach_user' => null,
        'is_on_shelf' => 1,
        'sales' => 0,
        'desc' => '',
        'index_top' => 'n',
        'sort_top' => 'n',
        'des' => '',
        'sort_num' => '',
        'group_id' => 0,
        'payment' => [],
        'goods_type_all' => [
//            ['name' => '一次性卡密（版本废弃）', 'value' => 'duli'],
//            ['name' => '固定通用卡密', 'value' => 'guding'],
//            ['name' => '虚拟服务类型', 'value' => 'xuni'],
//            ['name' => '自定义接口URL/POST', 'value' => 'post'],
        ]
    ];


    doMultiAction('adm_add_goods_goodsinfo', $goods, $goods);
//    d($goods);die;

    $sorts = $CACHE->readCache('sort');

    // 初始化批量优惠为空数组
    $discount = [];

    $userTierModel = new User_Tier_Model();
    $members = $userTierModel->getTiersAll();

    $sku_table = [
        'head' => [
            ['title' => '游客访问(元)', 'icon' => 'fa fa-edit'],
            ['title' => '登录用户(元)', 'icon' => 'fa fa-edit'],
            ['title' => '市场价(元)', 'icon' => 'fa fa-edit'],
            ['title' => '成本价(元)', 'icon' => 'fa fa-edit'],
            ['title' => '销量', 'icon' => 'fa fa-edit'],
        ],
        'body' => [
            ['field' => 'guest_price', 'value' => '', 'type' => 'number'],
            ['field' => 'user_price', 'value' => '', 'type' => 'number'],
            ['field' => 'market_price', 'value' => '', 'type' => 'number'],
            ['field' => 'cost_price', 'value' => '', 'type' => 'number'],
            ['field' => 'sales', 'value' => '', 'type' => 'number'],
        ]

    ];



    include View::getAdmView('open_head');
    require_once(View::getAdmView('goods_release'));
    include View::getAdmView('open_foot');
    View::output();
}

if ($action === 'edit') {
    $goods_id = Input::getIntVar('id');

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;

    $goods = $goodsModel->getOneGoodsForAdmin($goods_id);

    $goods['goods_type_all'] = [];
    doMultiAction('adm_add_goods_goodsinfo', $goods, $goods);

    // d($goods);die;

    $sorts = $CACHE->readCache('sort');
    $mediaSorts = $MediaSort_Model->getSorts();

    $userTierModel = new User_Tier_Model();
    $members = $userTierModel->getTiersAll();

    $sku_table = [
        'head' => [
            ['title' => '游客访问(元)', 'icon' => 'fa fa-edit'],
            ['title' => '登录用户(元)', 'icon' => 'fa fa-edit'],
            ['title' => '市场价(元)', 'icon' => 'fa fa-edit'],
            ['title' => '成本价(元)', 'icon' => 'fa fa-edit'],
            ['title' => '销量', 'icon' => 'fa fa-edit'],
        ],
        'body' => [
            ['field' => 'guest_price', 'value' => '', 'type' => 'number'],
            ['field' => 'user_price', 'value' => '', 'type' => 'number'],
            ['field' => 'market_price', 'value' => '', 'type' => 'number'],
            ['field' => 'cost_price', 'value' => '', 'type' => 'number'],
            ['field' => 'sales', 'value' => '', 'type' => 'number'],
        ]

    ];



    include View::getAdmView('open_head');
    require_once(View::getAdmView('goods_release'));
    include View::getAdmView('open_foot');

    View::output();
}

if ($action == 'upload_cover') {
    $ret = uploadCropImg();
    $Media_Model->addMedia($ret['file_info']);
    Output::ok($ret['file_info']['file_path']);
}

