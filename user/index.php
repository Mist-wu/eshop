<?php



require_once '../user/globals.php';

if (empty($action)) {
    global $userData;

    // d($userData);die;
    if(empty($userData['token'])){
        $token = md5($userData['uid'] . $userData['password'] . mt_rand(1000, 9999));
        $userModel = new User_Model();
        $userModel->updateUser([
            '_token' => $token
        ], UID);
    }else{
        $token = $userData['token'];
    }


    include View::getUserView('_header');
    require_once(View::getUserView('index'));
    include View::getUserView('_footer');
    View::output();
}

if ($action == 'upload_cover') {
    $Media_Model = new Media_Model();
    $ret = uploadCropImg();
    $Media_Model->addMedia($ret['file_info']);
    Output::ok($ret['file_info']['file_path']);
}