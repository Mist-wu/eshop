<?php

require_once '../init.php';

$sta_cache = $CACHE->readCache('sta');
$action = Input::getStrVar('action');

loginAuth::checkLogin(NULL, 'admin');
User::checkRolePermission();


$userModel = new User_Model();
$user = $userModel->getOneUser(UID);
$user['avatar'] = User::getAvatar($user['photo']);


