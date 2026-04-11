<?php
/**
 * store
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$Store_Model = new Store_Model();




if ($action === 'install') {
    $source = isset($_POST['source']) ? trim($_POST['source']) : ''; // plugin/down/11
    $cdn_source = isset($_POST['cdn_source']) ? trim($_POST['cdn_source']) : '';
    $source_type = isset($_POST['type']) ? trim($_POST['type']) : '';
    if (empty($source)) {
        output::error('安装失败，插件文件源无效');
    }



    if ($cdn_source) {
        $temp_file = emFetchFile($cdn_source);
    } else {
        $temp_file = emFetchFile(EM_LINE[CURRENT_LINE]['value'] . $source);
    }
    if (!$temp_file) {
        output::error('安装失败，下载超时或没有权限');
    }

    if ($source_type === 'tpl') {
        output::error('模板配置功能已下线');
    }

    $unzip_path = '../content/plugins/';
    $suc_url = 'plugin.php';

    $ret = emUnZip($temp_file, $unzip_path, $source_type);
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            output::ok('安装成功 <a href="' . $suc_url . '">去启用</a>');
        case 1:
        case 2:
        output::error('安装失败，请检查content下目录是否可写');
        case 3:
            output::error('安装失败，请安装php的Zip扩展');
        default:
            output::error('安装失败，不是有效的安装包');
    }
}
