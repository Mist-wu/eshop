<?php

/**
 * The article management

 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Log_Model = new Log_Model();
$Tag_Model = new Tag_Model();
$Sort_Model = new Sort_Model();
$User_Model = new User_Model();
$Media_Model = new Media_Model();
$MediaSort_Model = new MediaSort_Model();
$Template_Model = new Template_Model();

if (empty($action)) {


    $br = '<a href="./">控制台</a><a href="./article.php">博客管理</a><a><cite>文章列表</cite></a>';

    include View::getAdmView(User::haveEditPermission() ? 'header' : 'uc_header');
    require_once View::getAdmView('templates/default/article/index');
    include View::getAdmView(User::haveEditPermission() ? 'footer' : 'uc_footer');
    View::output();
}

if ($action == 'index') {
    $draft = Input::getIntVar('draft');
    $tagId = Input::getIntVar('tagid');
    $sid = Input::getIntVar('sid');
    $uid = Input::getIntVar('uid');
    $page = Input::getIntVar('page', 1);
    $keyword = Input::getStrVar('keyword');
    $checked = Input::getStrVar('checked');
    $order = Input::getStrVar('order');
    $perpage_num = Input::getIntVar('limit');

    $condition = '';
    if ($tagId) {
        $blogIdStr = $Tag_Model->getTagById($tagId) ?: 0;
        $condition = "and gid IN ($blogIdStr)";
    } elseif ($sid) {
        $condition = "and sortid=$sid";
    } elseif ($uid) {
        $condition = "and author=$uid";
    } elseif ($checked) {
        $condition = "and checked='$checked'";
    } elseif ($keyword) {
        $condition = "and title like '%$keyword%'";
    }



    $orderBy = ' ORDER BY ';
    switch ($order) {
        case 'view':
            $orderBy .= 'views DESC';
            break;
        case 'comm':
            $orderBy .= 'comnum DESC';
            break;
        case 'top':
            $orderBy .= 'top DESC, sortop DESC';
            break;
        default:
            $orderBy .= 'date DESC';
            break;
    }

    $hide_state = $draft ? 'y' : 'n';
    if ($draft) {
        $hide_stae = 'y';
        $sorturl = '&draft=1';
    } else {
        $hide_stae = 'n';
        $sorturl = '';
    }

    $logNum = $Log_Model->getLogNum($hide_state, $condition, 'blog', 1);
    $logs = $Log_Model->getLogsForAdmin($condition . $orderBy, $hide_state, $page, 'blog', $perpage_num);
    $sorts = $CACHE->readCache('blog_sort');
    $tags = $Tag_Model->getTags();

    foreach($logs as $key => $val){
        $sortName = isset($sorts[$val['sortid']]['sortname']) ? $sorts[$val['sortid']]['sortname'] : '未知分类';
        $logs[$key]['sortname'] = $val['sortid'] == -1 ? '未分类' : $sortName;
    }

    output::data($logs, $logNum);


}

if ($action == 'del') {
    $ids = Input::postStrVar('ids');
    LoginAuth::checkToken();

    $ids = explode(',', $ids);

    foreach($ids as $val){
        $Log_Model->deleteLog($val);
    }
    $CACHE->updateCache();
    output::ok();
}

if ($action == 'tag') {
    $gid = Input::postIntVar('gid');
    $tagsStr = strip_tags(Input::postStrVar('tag'));

    if (!User::haveEditPermission()) {
        emMsg('权限不足！', './');
    }

    $Tag_Model->updateTag($tagsStr, $gid);
    emDirect("./article.php");
}

if ($action === 'pub') {
    $gid = Input::getIntVar('gid');

    $Log_Model->hideSwitch($gid, 'n');
    if (User::haveEditPermission()) {
        $Log_Model->checkSwitch($gid, 'y');
    }

    $CACHE->updateCache();
    emDirect("./article.php?draft=1&active_post=1&draft=1");
}

if ($action == 'operate_log') {
    $operate = Input::requestStrVar('operate');
    $draft = Input::postIntVar('draft');
    $logs = Input::postIntArray('blog');
    $sort = Input::postIntVar('sort');
    $author = Input::postIntVar('author');
    $gid = Input::requestNumVar('gid');

    LoginAuth::checkToken();

    if (!$operate) {
        emDirect("./article.php?draft=$draft&error_b=1");
    }
    if (empty($logs) && empty($gid)) {
        emDirect("./article.php?draft=$draft&error_a=1");
    }

    switch ($operate) {
        case 'del':
            foreach ($logs as $val) {
                doAction('before_del_log', $val);
                $Log_Model->deleteLog($val);
                doAction('del_log', $val);
            }
            $CACHE->updateCache();
            emDirect("./article.php?draft=$draft");
            break;
        case 'top':
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('top' => 'y'), $val);
            }
            emDirect("./article.php?active_up=1&draft=$draft");
            break;
        case 'sortop':
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('sortop' => 'y'), $val);
            }
            emDirect("./article.php?active_up=1&draft=$draft");
            break;
        case 'notop':
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('top' => 'n', 'sortop' => 'n'), $val);
            }
            emDirect("./article.php?active_down=1&draft=$draft");
            break;
        case 'hide':
            foreach ($logs as $val) {
                $Log_Model->hideSwitch($val, 'y');
            }
            $CACHE->updateCache();
            emDirect("./article.php?active_hide=1&draft=$draft");
            break;
        case 'pub':
            foreach ($logs as $val) {
                $Log_Model->hideSwitch($val, 'n');
                if (User::haveEditPermission()) {
                    $Log_Model->checkSwitch($val, 'y');
                }
            }
            $CACHE->updateCache();
            emDirect("./article.php?draft=1&active_post=1&draft=$draft");
            break;
        case 'move':
            foreach ($logs as $val) {
                $Log_Model->checkEditable($val);
                $Log_Model->updateLog(array('sortid' => $sort), $val);
            }
            $CACHE->updateCache(array('sort', 'logsort'));
            emDirect("./article.php?active_move=1&draft=$draft");
            break;
        case 'change_author':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('author' => $author), $val);
            }
            $CACHE->updateCache('sta');
            emDirect("./article.php?active_change_author=1&draft=$draft");
            break;
        case 'check':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            if ($logs) {
                foreach ($logs as $id) {
                    $Log_Model->checkSwitch($id, 'y');
                }
            } else {
                $Log_Model->checkSwitch($gid, 'y');
            }
            $CACHE->updateCache();
            emDirect("./article.php?active_ck=1&draft=$draft");
            break;
        case 'uncheck':
            if (!User::haveEditPermission()) {
                emMsg('权限不足！', './');
            }
            if ($logs) {
                $feedback = '';
                foreach ($logs as $id) {
                    $Log_Model->unCheck($id, $feedback);
                }
            } else {
                $gid = Input::postIntVar('gid');
                $feedback = Input::postStrVar('feedback');
                $Log_Model->unCheck($gid, $feedback);
            }
            $CACHE->updateCache();
            emDirect("./article.php?active_unck=1&draft=$draft");
            break;
    }
}

if ($action === 'write') {
    $blogData = [
        'logid'    => -1,
        'title'    => '',
        'content'  => '',
        'excerpt'  => '',
        'alias'    => '',
        'sortid'   => -1,
        'type'     => 'blog',
        'password' => '',
        'hide'     => '',
        'author'   => UID,
        'cover'    => '',
        'link'     => '',
        'template' => '',
    ];

    extract($blogData);

    $isdraft = false;
    $containerTitle = User::haveEditPermission() ? '写文章' : '发布' . Option::get('posts_name');
    $orig_date = '';
    $sorts = $CACHE->readCache('blog_sort');
    $tagStr = '';
    $tags = $Tag_Model->getTags();
    $is_top = '';
    $is_sortop = '';
    $is_allow_remark = 'checked="checked"';
    $postDate = date('Y-m-d H:i:s');
    $mediaSorts = $MediaSort_Model->getSorts();
    $customTemplates = $Template_Model->getCustomTemplates('log');
    $customFields = $Template_Model->getCustomFields();
    $fields = [];

    $br = '<a href="./">控制台</a><a href="./article.php">博客管理</a><a><cite>' . $containerTitle . '</cite></a>';

    include View::getAdmView('open_head');
    require_once(View::getAdmView('article_write'));
    include View::getAdmView('open_foot');

    View::output();
}

if ($action === 'edit') {
    $logid = Input::getIntVar('gid');

    $Log_Model->checkEditable($logid);
    $blogData = $Log_Model->getOneLogForAdmin($logid);
    extract($blogData);

    $isdraft = $hide == 'y' ? true : false;
    $postsName = User::isAdmin() ? '文章' : Option::get('posts_name');
    $containerTitle = $isdraft ? '编辑草稿' : '编辑' . $postsName;
    $postDate = date('Y-m-d H:i:s', $date);
    $sorts = $CACHE->readCache('blog_sort');

    //tag
    $tags = [];
    foreach ($Tag_Model->getTag($logid) as $val) {
        $tags[] = $val['tagname'];
    }
    $tagStr = implode(',', $tags);
    //old tag
    $tags = $Tag_Model->getTags();

    $mediaSorts = $MediaSort_Model->getSorts();

    // fields
    $fields = Field::getFields($logid);

    $customTemplates = $Template_Model->getCustomTemplates('log');
    $customFields = $Template_Model->getCustomFields();

    $is_top = $top == 'y' ? 'checked="checked"' : '';
    $is_sortop = $sortop == 'y' ? 'checked="checked"' : '';
    $is_allow_remark = $allow_remark == 'y' ? 'checked="checked"' : '';

    $br = '<a href="./">控制台</a><a href="./article.php">博客管理</a><a><cite>' . $containerTitle . '</cite></a>';

    include View::getAdmView(User::haveEditPermission() ? 'header' : 'uc_header');
    require_once(View::getAdmView('article_write'));
    include View::getAdmView(User::haveEditPermission() ? 'footer' : 'uc_footer');
    View::output();
}

if ($action == 'upload_cover') {
    $ret = uploadCropImg();
    $Media_Model->addMedia($ret['file_info']);
    Output::ok($ret['file_info']['file_path']);
}
if ($action == 'upload_cover2') {
    $ret = uploadCropImg();
    $Media_Model->addMedia($ret['file_info']);


    echo json_encode([
        'location' => $ret['file_info']['file_path']
    ]); die;
}
