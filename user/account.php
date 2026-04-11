<?php
/**

 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once '../init.php';

$sta_cache = $CACHE->readCache('sta');
$action = Input::getStrVar('action');

$admin_path_code = isset($_GET['s']) ? addslashes(htmlClean($_GET['s'])) : '';
$User_Model = new User_Model();

if ($action == 'signin') {

    if (Option::get('login_switch') !== 'y') {
        emMsg('系统已关闭登录！');
    }

    loginAuth::checkLogged();

    $login_code = Option::get('login_code') === 'y';
    $is_signup = Option::get('is_signup') === 'y';

    $default_type = Option::get('login_tel_switch') == 'y' ? 'tel' : 'email';

    $page_title = '登录';
    require_once View::getUserView('user_head');
    require_once View::getUserView('signin');
    View::output();
}

if ($action == 'dosignin') {

//    loginAuth::checkLogged();

    if (Option::get('login_switch') !== 'y') {
        output::error('管理员已关闭登录功能');
    }


//    Output::error('验证错误0');
    doAction('user_login_submit');


    $tel = Input::postStrVar('tel');
    $email = Input::postStrVar('email');
    $password = Input::postStrVar('password');
    $persist = Input::postIntVar('persist');
    $type = Input::postStrVar('type');
    $login_code = Option::get('login_code') === 'y' && isset($_POST['login_code']) ? addslashes(strtoupper(trim($_POST['login_code']))) : '';

    if (!User::checkLoginCode($login_code)) {
        Output::error('验证错误');
    }



    if($type == 'tel'){
        $uid = LoginAuth::checkUser($tel, $password, $type);
    }
    if($type == 'email'){
        $uid = LoginAuth::checkUser($email, $password, $type);
    }
    if(empty($uid)){
        Output::error('账号或密码错误');
    }
//var_dump($uid);die;
    switch ($uid) {
        case $uid > 0:
            Register::isRegServer();
            $User_Model->updateUser(['ip' => getIp()], $uid);
            if($type == 'tel'){
                LoginAuth::setAuthCookie($tel, $persist);
            }
            if($type == 'email'){
                LoginAuth::setAuthCookie($email, $persist);
            }

            Output::ok();
            break;
        case LoginAuth::LOGIN_ERROR_USER:
        case LoginAuth::LOGIN_ERROR_PASSWD:
            if($type == 'email'){
                Output::error('邮箱或密码错误');
            }
            if($type == 'tel'){
                Output::error('手机号码或密码错误');
            }

            break;
    }
}

if ($action == 'signup') {
    loginAuth::checkLogged();
    $login_code = Option::get('login_code') === 'y';
    $email_code = Option::get('email_code') === 'y';
    $error_msg = '';

    if (Option::get('register_switch') !== 'y') {
        emMsg('系统已关闭注册！');
    }



    $default_type = Option::get('register_tel_switch') == 'y' ? 'tel' : 'email';

    $page_title = '注册账号';
    include View::getUserView('user_head');
    require_once View::getUserView('signup');
    View::output();
}

if ($action == 'dosignup') {
    loginAuth::checkLogged();

    if (Option::get('register_switch') !== 'y') {
        output::ok('管理员已关闭注册功能');
    }

    doAction('user_register_submit');

    $mail = Input::postStrVar('email');
    $tel = Input::postStrVar('tel');
    $passwd = Input::postStrVar('password');
    $repasswd = Input::postStrVar('repassword');
    $login_code = strtoupper(Input::postStrVar('login_code'));
    $mail_code = Input::postStrVar('mail_code');
    $type = Input::postStrVar('type');
    $reg_ip = getIp();

    if($type == 'email'){
        if (!checkMail($mail)) {
            Output::error('错误的邮箱格式');
        }
        if ($User_Model->isMailExist($mail)) {
            Output::error('该邮箱已被注册');
        }
    }
    if($type == 'tel'){
        if(strlen($tel) != 11){
            output::error('手机号码填写错误');
        }
        if ($User_Model->isTelExist($tel)) {
            Output::error('该手机号码已被注册');
        }
    }

    if (!User::checkLoginCode($login_code)) {
        Output::error('图形验证码错误');
    }
    if (Option::get('email_code') === 'y' && !User::checkMailCode($mail_code)) {
        Output::error('邮件验证码错误');
    }

    if (strlen($passwd) < 6) {
        Output::error('密码不小于6位');
    }
    if ($passwd !== $repasswd) {
        Output::error('两次输入的密码不一致');
    }

    $PHPASS = new PasswordHash(8, true);
    $passwd = $PHPASS->HashPassword($passwd);
    if($type == 'tel'){
        $User_Model->addUser('', $tel, $passwd, $reg_ip,  User::ROLE_WRITER, 'tel');
        $account = $tel;

    }else{
        $User_Model->addUser('', $mail, $passwd, $reg_ip,  User::ROLE_WRITER, 'email');
        $account = $mail;
    }

    $CACHE->updateCache(['sta', 'user']);

    doAction('user_register_after', $account, $type);

    output::ok();
}

if ($action == 'send_email_code') {
    $mail = Input::postStrVar('mail');

    if (!checkMail($mail)) {
        Output::error('错误的邮箱');
    }

    doAction('send_email_code', $mail);

    $ret = Notice::sendVerifyMailCode($mail);
    if ($ret) {
        Output::ok();
    } else {
        Output::error('发送邮件失败');
    }
}

if ($action == 'reset') {
    if (ISLOGIN === true) {
        emDirect("../admin/");
    }

    $login_code = Option::get('login_code') === 'y';
    $error_msg = '';

    $page_title = '找回密码';
    include View::getUserView('user_head');
    require_once View::getUserView('reset');
    View::output();
}

if ($action == 'doreset') {
    loginAuth::checkLogged();

    $mail = Input::postStrVar('mail');
    $login_code = strtoupper(Input::postStrVar('login_code'));
    $resp = Input::postStrVar('resp'); // eg: json (only support json now)

    if (!User::checkLoginCode($login_code)) {
        if ($resp === 'json') {
            Output::error('图形验证码错误');
        }
        emDirect('./account.php?action=reset&err_ckcode=1');
    }
    if (!$mail || !$User_Model->isMailExist($mail)) {
        if ($resp === 'json') {
            Output::error('错误的注册邮箱');
        }
        emDirect('./account.php?action=reset&error_mail=1');
    }

    $ret = Notice::sendResetMailCode($mail);
    if ($ret) {
        if ($resp === 'json') {
            Output::ok();
        }
        emDirect("./account.php?action=reset2&succ_mail=1");
    } else {
        if ($resp === 'json') {
            Output::error('邮件验证码发送失败，请检查邮件通知设置');
        }
        emDirect("./account.php?action=reset&error_sendmail=1");
    }
}

if ($action == 'reset2') {
    if (ISLOGIN === true) {
        emDirect("../admin/");
    }

    $login_code = Option::get('login_code') === 'y';
    $error_msg = '';

    $page_title = '找回密码';

    include View::getUserView('user_head');
    include View::getUserView('reset2');
    View::output();
}

if ($action == 'doreset2') {
    $mail_code = Input::postStrVar('mail_code');
    $passwd = Input::postStrVar('passwd');
    $repasswd = Input::postStrVar('repasswd');
    $resp = Input::postStrVar('resp'); // only json

    if (strlen($passwd) < 6) {
        if ($resp === 'json') {
            Output::error('密码长度不合规');
        }
        emDirect('./account.php?action=reset2&error_pwd_len=1');
    }
    if ($passwd !== $repasswd) {
        if ($resp === 'json') {
            Output::error('两次输入的密码不一致');
        }
        emDirect('./account.php?action=reset2&error_pwd2=1');
    }
    if (!$mail_code || !User::checkMailCode($mail_code)) {
        if ($resp === 'json') {
            Output::error('邮件验证码错误');
        }
        emDirect('./account.php?action=reset2&err_mail_code=1');
    }

    $PHPASS = new PasswordHash(8, true);
    $passwd = $PHPASS->HashPassword($passwd);
    if (!isset($_SESSION)) {
        session_start();
    }
    $mail = isset($_SESSION['mail']) ? $_SESSION['mail'] : '';
    $User_Model->updateUserByMail(['password' => $passwd], $mail);
    if ($resp === 'json') {
        Output::ok();
    }
    emDirect("./account.php?action=signin&succ_reset=1");
}

if ($action == 'logout') {
    setcookie(AUTH_COOKIE_NAME, ' ', time() - 31536000, '/');
    emDirect("../");
}
