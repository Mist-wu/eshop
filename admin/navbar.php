<?php
/**
 * navbar menu items
 * @package EMLOG
 * @link https://www.emlog.net
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Navi_Model = new Navi_Model();

if (empty($action)) {
    $emPage = new Log_Model();
    $sorts = $CACHE->readCache('sort');
    $blog_sorts = $CACHE->readCache('blog_sort');
    $pages = $emPage->getAllPageList();

    $br = '<a href="./">控制台</a><a href="./template.php">外观设置</a><a><cite>导航管理</cite></a>';

    include View::getAdmView('header');
    require_once(View::getAdmView('templates/default/navbar/index'));
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $navis = $Navi_Model->getNavis();

//    d($navis); die;

    foreach($navis as $key => &$val){

        if($val['pid'] != 0){
            unset($navis[$key]);
            continue;
        }

        if($val['type'] == Navi_Model::navitype_admin || $val['type'] == Navi_Model::navitype_home || $val['type'] == Navi_Model::navitype_t){
            $navis[$key]['type_name'] = '系统';
            $navis[$key]['url'] = '/' . $val['url'];
        }
        if($val['type'] == Navi_Model::navitype_blog){
            $navis[$key]['type_name'] = '系统';
            $navis[$key]['url'] = $val['url'];
        }
        if($val['type'] == Navi_Model::navitype_sort){
            $navis[$key]['type_name'] = '分类';
        }
        if($val['type'] == Navi_Model::navitype_page){
            $navis[$key]['type_name'] = '页面';
        }
        if($val['type'] == Navi_Model::navitype_custom){
            $navis[$key]['type_name'] = '自定义';
        }
    }

    // 关键：提取关联数组的值，转为索引数组（忽略原键名）
    $navis = array_values($navis);

// 可选：按 taxis 或 id 排序（确保显示顺序正确）
    usort($navis, function($a, $b) {
        return $b['taxis'] - $a['taxis']; // 按排序字段升序
    });

    output::data($navis, count($navis));
}

if ($action == 'taxis') {
    $navi = isset($_POST['navi']) ? $_POST['navi'] : '';

    if (empty($navi)) {
        Output::error('没有可排序的导航');
    }

    foreach ($navi as $key => $value) {
        $value = (int)$value;
        $key = (int)$key;
        $Navi_Model->updateNavi(array('taxis' => $key), $value);
    }
    $CACHE->updateCache('navi');
    Output::ok();
}

if ($action == 'add_ajax') {
    $taxis = isset($_POST['taxis']) ? (int)trim($_POST['taxis']) : 0;
    $naviname = isset($_POST['naviname']) ? addslashes(trim($_POST['naviname'])) : '';
    $url = isset($_POST['url']) ? addslashes(trim($_POST['url'])) : '';
    $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
    $newtab = isset($_POST['newtab']) ? addslashes(trim($_POST['newtab'])) : 'n';

    if (empty($naviname)) {
        output::error('请输入导航名称');
    }
    if(empty($url)){
        output::error('请输入导航网址');
    }

    $Navi_Model->addNavi($naviname, $url, $taxis, $pid, $newtab);
    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'add_custom') {
    $navis = $Navi_Model->getNavis();
    include View::getAdmView('open_head');
    require_once(View::getAdmView('templates/default/navbar/add_custom'));
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'add_sort') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sorts = $CACHE->readCache('sort');
        include View::getAdmView('open_head');
        require_once(View::getAdmView('templates/default/navbar/add_sort'));
        include View::getAdmView('open_foot');
        View::output();
        exit;
    }

    $sort_ids = isset($_POST['sort_ids']) ? $_POST['sort_ids'] : array();

    $sorts = $CACHE->readCache('sort');

    if (empty($sort_ids)) {
        output::error('请选择分类');
    }

    foreach ($sort_ids as $val) {
        $sort_id = (int)$val;
        $Navi_Model->addNavi(addslashes($sorts[$sort_id]['sortname']), '', 0, 0, 'n', Navi_Model::navitype_sort, $sort_id);
    }

    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'add_blogsort') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $blog_sorts = $CACHE->readCache('blog_sort');
        include View::getAdmView('open_head');
        require_once(View::getAdmView('templates/default/navbar/add_blogsort'));
        include View::getAdmView('open_foot');
        View::output();
        exit;
    }

    $sort_ids = isset($_POST['sort_ids']) ? $_POST['sort_ids'] : array();

    $sorts = $CACHE->readCache('blog_sort');

    if (empty($sort_ids)) {
        output::error('请选择分类');
    }

    foreach ($sort_ids as $val) {
        $sort_id = (int)$val;
        $Navi_Model->addNavi(addslashes($sorts[$sort_id]['sortname']), '', 0, 0, 'n', Navi_Model::navitype_blogsort, $sort_id);
    }

    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'add_page') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $emPage = new Log_Model();
        $pages = $emPage->getAllPageList();
        include View::getAdmView('open_head');
        require_once(View::getAdmView('templates/default/navbar/add_page'));
        include View::getAdmView('open_foot');
        View::output();
        exit;
    }

    $pages = isset($_POST['pages']) ? $_POST['pages'] : array();

    if (empty($pages)) {
        output::error('请选择页面');
    }

    foreach ($pages as $id => $title) {
        $id = (int)$id;
        $title = addslashes($title);
        $Navi_Model->addNavi($title, '', 0, 0, 'n', Navi_Model::navitype_page, $id);
    }

    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'edit') {
    $naviId = Input::getStrVar('id');
    $navis = $CACHE->readCache('navi');
    $naviData = $Navi_Model->getOneNavi($naviId);
    extract($naviData);
    if ($type != Navi_Model::navitype_custom) {
        $url = '该导航地址由系统生成，无法修改';
    }
    $conf_newtab = $newtab == 'y' ? 'checked="checked"' : '';
    $conf_isdefault = $type != Navi_Model::navitype_custom ? 'disabled="disabled"' : '';


    include View::getAdmView('open_head');
    require_once(View::getAdmView('templates/default/navbar/edit'));
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'update') {
    $naviname = isset($_POST['naviname']) ? addslashes(trim($_POST['naviname'])) : '';
    $url = isset($_POST['url']) ? addslashes(trim($_POST['url'])) : '';
    $newtab = isset($_POST['newtab']) ? addslashes(trim($_POST['newtab'])) : 'n';
    $naviId = isset($_POST['navid']) ? (int)$_POST['navid'] : '';
    $isdefault = isset($_POST['isdefault']) ? addslashes(trim($_POST['isdefault'])) : 'n';
    $pid = isset($_POST['pid']) ? (int)trim($_POST['pid']) : 0;
    $taxis = isset($_POST['taxis']) ? (int)trim($_POST['taxis']) : 0;

    $navi_data = array(
        'naviname' => $naviname,
        'newtab'   => $newtab,
        'pid'      => $pid,
        'taxis' => $taxis
    );

    if (empty($naviname)) {
        unset($navi_data['naviname']);
    }

    if ($isdefault == 'n') {
        $navi_data['url'] = $url;
    }

    $Navi_Model->updateNavi($navi_data, $naviId);

    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'del') {
    LoginAuth::checkToken();

    $ids = Input::postStrVar('ids');
    $ids = explode(',', $ids);

    foreach($ids as $val){
        $Navi_Model->deleteNavi($val);
    }
    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'hide') {

    $naviId = Input::postIntVar('id');

    $Navi_Model->updateNavi(array('hide' => 'y'), $naviId);

    $CACHE->updateCache('navi');
    output::ok();
}

if ($action == 'show') {

    $naviId = Input::postIntVar('id');
    $Navi_Model->updateNavi(array('hide' => 'n'), $naviId);
    $CACHE->updateCache('navi');
    output::ok();
}
