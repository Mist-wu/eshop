<?php

require_once '../init.php';
//LoginAuth::checkToken();

$action = Input::getStrVar('action');

if(empty($action)){
    $Media_Model = new Media_Model();
    $ret = uploadCropImg();
    $file_id = $Media_Model->addMedia($ret['file_info']);

    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "select * from {$db_prefix}attachment where aid={$file_id}";
    $res = $db->once_fetch_array($sql);
    $alias = $res['alias'];

    Output::ok($alias);
}

if($action == 'show_file'){
    $alias = Input::getStrVar('alias');
    $db = Database::getInstance();
    $db_prefix = DB_PREFIX;
    $sql = "select * from {$db_prefix}attachment where alias='{$alias}'";
    $res = $db->once_fetch_array($sql);
    $filepath = $res['filepath'];



    $parsedUrl = parse_url(EM_URL);

    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';


    $allowedDomains = [$host];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $isValid = false;
    if ($referer) {
        $parsed = parse_url($referer);
        $host = $parsed['host'] ?? '';
        $isValid = in_array($host, $allowedDomains);
    }
// 允许直接访问（如用户在浏览器输入URL），但限制外链
    if (!$isValid && $referer) {
        die('禁止外链');
    }

    $filepath = EM_ROOT . ltrim($filepath, '.');

    // d($filepath);die;

// 3. 输出图片
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    header("Content-Type: " . $finfo->file($filepath));
    readfile($filepath);

}
