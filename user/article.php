<?php


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