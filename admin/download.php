<?php
/**
 * control panel
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';


$filename = Input::getStrVar('filename');

$filepath = EM_ROOT . '/content/em_temp/' . $filename;

// 防止输出缓存影响下载
if (ob_get_length()) {
    ob_end_clean();
}

// 强制设置为下载类型
header('Content-Type: application/octet-stream');
// 指定下载的文件名（可选，默认使用原文件名）
header('Content-Disposition: attachment; filename="' . $filename . '"');
// 告诉浏览器文件大小（可选，用于显示下载进度）
header('Content-Length: ' . filesize($filepath));

// 输出文件内容
readfile($filepath);
exit;