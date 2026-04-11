<?php
/**
 * @package EMSHOP
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
    loginAuth::checkLogged();
    if (defined('ADMIN_PATH_CODE') && $admin_path_code !== ADMIN_PATH_CODE) {
        show_404_page(true);
    }
    $login_code = Option::get('login_code') === 'y';
    $is_signup = Option::get('is_signup') === 'y';

    $page_title = '登录';
    require_once View::getAdmView('user_head');
    require_once View::getAdmView('signin');
    View::output();
}

if ($action == 'dosignin') {
    loginAuth::checkLogged();
    if (defined('ADMIN_PATH_CODE') && $admin_path_code !== ADMIN_PATH_CODE) {
        show_404_page(true);
    }
    doAction('admin_login_submit');
    $username = Input::postStrVar('user');
    $password = Input::postStrVar('pw');
    $persist = Input::postIntVar('persist');
    $resp = Input::postStrVar('resp'); // eg: json (only support json now)
    $login_code = Option::get('login_code') === 'y' && isset($_POST['login_code']) ? addslashes(strtoupper(trim($_POST['login_code']))) : '';

    if (!User::checkLoginCode($login_code)) {
        if ($resp === 'json') {
            Output::error('验证错误');
        }
        emDirect('./account.php?action=signin&err_ckcode=1');
    }
//    echo
    $uid = LoginAuth::checkUser($username, $password);
    switch ($uid) {
        case $uid > 0:
            // Register::isRegServer();
            $User_Model->updateUser(['ip' => getIp()], $uid);
            LoginAuth::setAuthCookie($username, $persist);
            Log::info('成功登录后台，账号：' . $username . '；ip: ' . getIp());
            if ($resp === 'json') {
                Output::ok();
            }
            output::ok();
            break;
        case LoginAuth::LOGIN_ERROR_USER:
        case LoginAuth::LOGIN_ERROR_PASSWD:
            Log::info('登录失败，账号或密码错误。账号：' . $username . '；ip: ' . getIp());
            Output::error('账号或密码错误');
            break;
    }
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
    include View::getAdmView('user_head');
    require_once View::getAdmView('reset');
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
    if (!$mail || !$User_Model->isMailExist($mail, '', 'admin')) {
        if ($resp === 'json') {
            Output::error('错误的管理员邮箱');
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
    include View::getAdmView('user_head');
    require_once View::getAdmView('reset2');
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
    Log::info('退出后台登录，ID：' . UID);
    setcookie(AUTH_COOKIE_NAME, ' ', time() - 31536000, '/');
    emDirect("./");
}
