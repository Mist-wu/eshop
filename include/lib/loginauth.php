<?php
/**
 * Login authentication
 * @package EMLOG
 * @link https://www.emlog.net
 */

class LoginAuth {

    const LOGIN_ERROR_USER = -1;
    const LOGIN_ERROR_PASSWD = -2;


    public static function isLogin() {
        global $userData;

        if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
            $auth_cookie = $_COOKIE[AUTH_COOKIE_NAME];
        } elseif (isset($_POST[AUTH_COOKIE_NAME])) {
            $auth_cookie = $_POST[AUTH_COOKIE_NAME];
        } else {
            return false;
        }

        if (($userData = self::validateAuthCookie($auth_cookie)) === false) {
            return false;
        }

        return true;
    }

    public static function checkLogin($error_code = NULL, $role = NULL) {



        if (self::isLogin() === true) {


            if($role == 'admin' && ROLE != ROLE_ADMIN){
                emMsg('当前已登录用户账号，无法进入后台页面');
            }

            return;
        }

        if($role == 'admin'){

            if ($error_code) {
                emDirect(EM_URL . "admin/account.php?action=signin&code=$error_code");
            } else {
                emDirect(EM_URL . "admin/account.php?action=signin");
            }
        }else{

            if ($error_code) {
                emDirect(EM_URL . "user/account.php?action=signin&code=$error_code");
            } else {
                emDirect(EM_URL . "user/account.php?action=signin");
            }
        }


    }

    public static function checkLogged() {
        if (self::isLogin() === false) {
            return;
        }
        emDirect("./");
    }

    public static function checkUser($email_tel, $password, $type = '') {


        if (empty($email_tel) || empty($password)) {
            return self::LOGIN_ERROR_USER;
        }
        $userData = self::getUserDataByLogin($email_tel, $type);

        if (false === $userData) {
            return self::LOGIN_ERROR_USER;
        }
        $hash = $userData['password'];
        if (true === self::checkPassword($password, $hash)) {
            return $userData['uid'];
        }
        return self::LOGIN_ERROR_PASSWD;
    }

    public static function getUserDataByLogin($account, $type = '') {
        $DB = Database::getInstance();
        if (empty($account)) {
            return false;
        }
        if($type == 'email'){
            $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE email = '$account' AND state = 0");
        }
        if($type == 'tel'){
            $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE tel = '$account' AND state = 0");
        }

        if(empty($type) || empty($ret)){
            $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE username = '$account' AND state = 0");
            if(empty($ret)){
                $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE email = '$account' AND state = 0");
            }

        }

        if(empty($ret)){
            return false;
        }

        $userData['nickname'] = htmlspecialchars($ret['nickname']);
        $userData['username'] = htmlspecialchars($ret['username']);
        $userData['password'] = $ret['password'];
        $userData['uid'] = $ret['uid'];
        $userData['role'] = $ret['role'];
        $userData['level'] = $ret['level'];
        $userData['photo'] = $ret['photo'];
        $userData['email'] = $ret['email'];
        $userData['tel'] = $ret['tel'];
        $userData['description'] = $ret['description'];
        $userData['ip'] = $ret['ip'];
        $userData['create_time'] = $ret['create_time'];
        $userData['update_time'] = $ret['update_time'];
        $userData['money'] = $ret['money'];
        $userData['expend'] = $ret['expend'];
        $userData['token'] = $ret['_token'];




        return $userData;
    }

    public static function checkPassword($password, $hash) {
        global $em_hasher;
        if (empty($em_hasher)) {
            $em_hasher = new PasswordHash(8, true);
        }
        return $em_hasher->CheckPassword($password, $hash);
    }

    public static function setAuthCookie($user_login, $persist = false) {
        if ($persist) {
            $expiration = time() + 86400;
        } else {
            $expiration = 0;
        }
        $auth_cookie_name = AUTH_COOKIE_NAME;
        $auth_cookie = self::generateAuthCookie($user_login, $expiration);
        setcookie($auth_cookie_name, $auth_cookie, $expiration, '/', '', false, true);
    }

    private static function generateAuthCookie($user_login, $expiration) {
        $key = self::emHash($user_login . '|' . $expiration);
        $hash = hash_hmac('md5', $user_login . '|' . $expiration, $key);

        return $user_login . '|' . $expiration . '|' . $hash;
    }

    private static function emHash($data) {
        return hash_hmac('md5', $data, AUTH_KEY);
    }

    public static function validateAuthCookie($cookie = '') {
        if (empty($cookie)) {
            return false;
        }

        $cookie_elements = explode('|', $cookie);
        if (count($cookie_elements) !== 3) {
            return false;
        }

        list($username, $expiration, $hmac) = $cookie_elements;

        if (!empty($expiration) && $expiration < time()) {
            return false;
        }

        $key = self::emHash($username . '|' . $expiration);
        $hash = hash_hmac('md5', $username . '|' . $expiration, $key);

        if ($hmac !== $hash) {
            return false;
        }

        $type = isEmail($username) ? 'email' : 'tel';

        $user = self::getUserDataByLogin($username, $type);
        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function genToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
//        d($_SESSION['em_csrf_token']);
        if (!empty($_SESSION['em_csrf_token'])) {
            return $_SESSION['em_csrf_token'];
        }

        $token = sha1(getRandStr(32));
        $_SESSION['em_csrf_token'] = $token;
        return $token;
    }

    public static function getToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        $token = '';
        if (!empty($_SESSION['em_csrf_token'])) {
            $token = $_SESSION['em_csrf_token'];
        }
        return $token;
    }

    public static function checkToken() {
        $token = isset($_REQUEST['token']) ? addslashes($_REQUEST['token']) : '';
        $sessionToken = self::getToken();
        // The session is abnormal, the /tmp directory may not be writable, skip the check
        if (empty($sessionToken)) {
            return;
        }
        if ($token !== $sessionToken) {
            emMsg('安全token校验失败，请尝试刷新页面或者更换浏览器重试');
        }
    }
    public static function checkAjaxToken() {
        $token = isset($_REQUEST['token']) ? addslashes($_REQUEST['token']) : '';
        $sessionToken = self::getToken();
        // The session is abnormal, the /tmp directory may not be writable, skip the check
        if (empty($sessionToken)) {
            return false;
        }
        if ($token !== $sessionToken) {
            return false;
        }
        return true;
    }
}
