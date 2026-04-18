<?php
/**
 * user
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$User_Model = new User_Model();
$couponModel = new Coupon_Model();

if($action == 'money_ajax'){
    output::error('余额功能已下线');
}
if ($action == 'money') {
    emMsg('余额功能已下线', EM_URL . 'admin/user.php');
}

if (empty($action)) {

    $db_prefix = DB_PREFIX;
    $db = Database::getInstance();


    $br = '<a href="./">控制台</a><a><cite>用户管理</cite></a>';


    $member_list = [];
    $sql = "SELECT * FROM `{$db_prefix}user_tier`";
    $member = $db->fetch_all($sql);
    foreach($member as $val){
        $member_list[] = [
            'name' => $val['tier_name'],
            'id' => $val['id']
        ];
    }
    $member_list[] = [
        'name' => '普通用户',
        'id' => -1
    ];


    include View::getAdmView('header');
    require_once View::getAdmView('user');
    include View::getAdmView('footer');
    View::output();
}

if($action == 'index'){
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $page = Input::getIntVar('page', 1);
    $limit = Input::getIntVar('limit', 10);
    $keyword = Input::getStrVar('keyword');
    $member_id = Input::getIntVar('member_id', null);
    $start = ($page - 1) * $limit;
    $where = "";
    $sort1 = Input::getStrVar('field', 'uid');
    $sort2 = Input::getStrVar('order', 'desc');
    $order_by = "order by {$sort1} {$sort2}";

    if($member_id !== null){
        $member_id = $member_id == -1 ? 0 : $member_id;
        $where .= " and u.level={$member_id}";
        $member_id = -1;
    }
    if(!empty($keyword)){
        $where .= " and (u.uid='{$keyword}' or u.tel like '%{$keyword}%' or u.nickname like '%{$keyword}%' or u.email like '%{$keyword}%')";
    }

    $sql = "SELECT
                u.*,
                m.tier_name level_name,
                IFNULL(cs.coupon_total, 0) promoter_coupon_total,
                IFNULL(cs.used_total, 0) promoter_coupon_used_total
            FROM {$db_prefix}user u
            LEFT JOIN {$db_prefix}user_tier m ON u.level = m.id
            LEFT JOIN (
                SELECT owner_uid, COUNT(*) AS coupon_total, IFNULL(SUM(used_times), 0) AS used_total
                FROM {$db_prefix}coupon
                WHERE owner_uid > 0
                GROUP BY owner_uid
            ) cs ON cs.owner_uid = u.uid
            WHERE 1=1 $where {$order_by} limit $start, {$limit}";
    $res = $db->fetch_all($sql);
    $users = [];
    foreach($res as $row){
        $row['name'] = htmlspecialchars($row['nickname']);
        $row['login'] = htmlspecialchars($row['username']);
        $row['email'] = htmlspecialchars($row['email']);
        $row['description'] = htmlspecialchars($row['description']);
        $row['create_time'] = smartDate($row['create_time']);
        $row['update_time'] = smartDate($row['update_time']);
        $row['role'] = User::getRoleName($row['role'], (int)$row['uid']);
        $row['level_name'] = empty($row['level_name']) ? '普通用户' : $row['level_name'];
        $row['promoter_coupon_total'] = (int)($row['promoter_coupon_total'] ?? 0);
        $row['promoter_coupon_used_total'] = (int)($row['promoter_coupon_used_total'] ?? 0);
        $users[] = $row;
    }

    $sql = "SELECT count(u.uid) total FROM {$db_prefix}user u left join " . DB_PREFIX . "user_tier m on u.level=m.id  where 1=1 $where";
    $res = $db->once_fetch_array($sql);
    $userCount = $res['total'];

    output::data($users, $res['total']);
}

if ($action == 'promoter_profile') {
    $uid = Input::getIntVar('uid', 0);
    if ($uid <= 0) {
        emMsg('用户ID不正确');
    }

    $promoterUser = $User_Model->getOneUser($uid);
    if (empty($promoterUser)) {
        emMsg('用户不存在');
    }

    $promoterStats = $couponModel->getPromoterCouponStats($uid);
    $promoterCoupons = $couponModel->getPromoterCoupons($uid, 1, 100);

    include View::getAdmView('open_head');
    require_once View::getAdmView('user_promoter');
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'new') {
    $email = isset($_POST['email']) ? addslashes(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? addslashes(trim($_POST['password'])) : '';
    $password2 = isset($_POST['password2']) ? addslashes(trim($_POST['password2'])) : '';
    $role = isset($_POST['role']) ? addslashes(trim($_POST['role'])) : self::ROLE_WRITER;

    LoginAuth::checkToken();

    if (User::isAdmin()) {
        $ischeck = 'n';
    }

    if ($email == '') {
        emDirect('./user.php?error_email=1');
    }
    if ($User_Model->isMailExist($email)) {
        emDirect("./user.php?error_exist_email=1");
    }
    if (strlen($password) < 6) {
        emDirect('./user.php?error_pwd_len=1');
    }
    if ($password != $password2) {
        emDirect('./user.php?error_pwd2=1');
    }

    $PHPASS = new PasswordHash(8, true);
    $password = $PHPASS->HashPassword($password);

    $User_Model->addUser('', $email, $password, $role);
    $CACHE->updateCache(array('sta', 'user'));
    emDirect('./user.php?active_add=1');
}

if ($action == 'edit') {

    $uid = isset($_GET['uid']) ? (int)$_GET['uid'] : '';

    $data = $User_Model->getOneUser($uid);

    $nickname = $data['nickname'];
    $role = $data['role'];
    $description = $data['description'];
//    $username = $data['username'];
    $email = $data['email'];
    $level = $data['level'];
    $tel = $data['tel'];

    $ex1 = $ex2 = $ex3 = '';
    if (user::isVisitor($role)) {
        $ex1 = 'selected="selected"';
    } elseif (User::isEditor($role)) {
        $ex2 = 'selected="selected"';
    } elseif (User::isAdmin($role)) {
        $ex3 = 'selected="selected"';
    }

    $userTierModel = new User_Tier_Model();
    $members = $userTierModel->getTiersAll();

//    d($members);die;

    include View::getAdmView('open_head');
    require_once View::getAdmView('user_edit');
    include View::getAdmView('open_foot');
    View::output();
}

if ($action == 'edit_ajax') {
//    $username = isset($_POST['username']) ? addslashes(trim($_POST['username'])) : '';
    $nickname = isset($_POST['nickname']) ? addslashes(trim($_POST['nickname'])) : '';
    $password = isset($_POST['password']) ? addslashes(trim($_POST['password'])) : '';
    $password2 = isset($_POST['password2']) ? addslashes(trim($_POST['password2'])) : '';
    $email = isset($_POST['email']) ? addslashes(trim($_POST['email'])) : '';
    $description = isset($_POST['description']) ? addslashes(trim($_POST['description'])) : '';
    $role = isset($_POST['role']) ? addslashes(trim($_POST['role'])) : User::ROLE_WRITER;
    $uid = isset($_POST['uid']) ? (int)$_POST['uid'] : '';
    $tel = Input::postStrVar('tel');
    $isFounderTarget = User::isFounderUid($uid);

    LoginAuth::checkToken();

    //创始人账户不能被他人编辑
    if (!User::isFounder() && $isFounderTarget) {
        emDirect('./user.php?error_del_b=1');
    }
    if ($isFounderTarget) {
        $role = User::ROLE_ADMIN;
    }
    if (empty($nickname)) {
        output::error('请填写昵称');
    }

    if ($User_Model->isMailExist($email, $uid)) {
        output::error('该邮箱已被使用');
    }
    if ($User_Model->isTelExist($tel, $uid)) {
        output::error('该手机被使用');
    }
    /*if ($User_Model->isUserExist($username, $uid)) {
        output::error('该用户名已被使用');
    }*/
    if (strlen($password) > 0 && strlen($password) < 6) {
        output::error('密码不能小于6位数');
    }


    $userData = [
//        'username'    => $username,
        'nickname'    => $nickname,
        'email'       => $email,
        'tel'       => $tel,
        'description' => $description,
        'role'        => $role,
        'level'       => Input::postIntVar('level')
    ];

    if (!empty($password)) {
        $PHPASS = new PasswordHash(8, true);
        $password = $PHPASS->HashPassword($password);
        $userData['password'] = $password;
    }

    $User_Model->updateUser($userData, $uid);
    $CACHE->updateCache('user');
    output::ok();
}

if ($action == 'del') {
    LoginAuth::checkToken();
    $ids = array_map('intval', explode(',', Input::postStrVar('ids')));
    $ids_arr = [];
    foreach ($ids as $val) {
        if ($val > 0 && !User::isFounderUid($val)) {
            $ids_arr[] = $val;
        }
    }
    if (empty($ids_arr)) {
        output::ok();
    }
    $ids = implode(',', $ids_arr);

    $sql = "DELETE FROM " . DB_PREFIX . "user WHERE uid IN ({$ids})";

    $db = Database::getInstance();
    $db->query($sql);

    $CACHE->updateCache(array('sta', 'user'));
    output::ok();


}



if ($action == 'forbid') {
    LoginAuth::checkToken();
    $uid = (int)Input::postStrVar('ids');
    if (UID == $uid) {
        output::ok();
    }
    //创始人账户不能被禁用
    if (User::isFounderUid($uid)) {
        output::ok();
    }
    $User_Model->forbidUser($uid);
    output::ok();
}

if ($action == 'unforbid') {
    LoginAuth::checkToken();
    $uid = (int)Input::postStrVar('ids');
    $User_Model->unforbidUser($uid);
    output::ok();
}

