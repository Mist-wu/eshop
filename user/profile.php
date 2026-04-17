<?php
/**
 * @package ESHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';


$User_Model = new User_Model();


$action = Input::getStrVar('action');

if ($action == 'upload_avatar') {
    LoginAuth::checkToken();
    $Media_Model = new Media_Model();
    $ret = uploadCropImg();
    $Media_Model->addMedia($ret['file_info']);
    Output::ok($ret['file_info']['file_path']);
}

if ($action == 'update') {
    LoginAuth::checkToken();
    $tel = Input::postStrVar('tel');
    $email = Input::postStrVar('email');
    $photo = Input::postStrVar('photo');

    if (empty($tel) && empty($email)) {
        Output::error('手机号和邮箱至少填写一项');
    }

    if (!empty($tel) && !preg_match('/^\d{11}$/', $tel)) {
        Output::error('手机号码格式错误');
    }

    if (!empty($email) && !checkMail($email)) {
        Output::error('邮箱格式错误');
    }

    if (!empty($email) && $User_Model->isMailExist($email, UID)) {
        Output::error('该邮箱已被使用');
    }

    if (!empty($tel) && $User_Model->isTelExist($tel, UID)) {
        Output::error('该手机号码已被使用');
    }

    $User_Model->updateUser([
        'tel' => $tel,
        'email' => $email,
        'photo' => $photo
    ], UID);

    $CACHE->updateCache('user');
    Output::ok();
}

// 订单列表
if (empty($action)) {
    include View::getUserView('_header');
    require_once View::getUserView('profile');
    include View::getUserView('_footer');
    View::output();
}


