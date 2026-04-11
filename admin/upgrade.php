<?php

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

// 检测更新
if ($action === 'check_update') {
    $url = EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=is_new_version';
    $data = [
        'version'   => Option::EM_VERSION,
    ];
    $res = emCurl($url, http_build_query($data), true);
    header('Content-Type: application/json; charset=UTF-8');
    die($res);
}
// 执行更新
if ($action === 'update' && User::isAdmin()) {
    // 下载更新包和sql文件
    $temp_sql_file = emFetchFile(EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=update_sql&domain=' . getDomain());
    if (!$temp_sql_file) {
        Ret::error('数据库更新文件下载失败');
    }
    $temp_zip_file = emFetchFile(EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=update_zip&domain=' . getDomain());
    if (!$temp_zip_file) {
        Ret::error('更新包下载失败');
    }

    $testSql = [];
    
    $DB = Database::getInstance();
    $setchar = "ALTER DATABASE `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    $sql = file($temp_sql_file);
    array_unshift($sql, $setchar);
    $query = '';
    foreach ($sql as $value) {
        // 只执行需要的更新
        if (!empty($value) && $value[0] == '#') {
            preg_match("/#\s(version\s[\.\d]+)/i", $value, $v);
            $ver = isset($v[1]) ? trim($v[1]) : '';
            if (version_compare('version ' . Option::EM_VERSION, $ver) > 0) {
                break;
            }
        }
        if (!$value || $value[0] == '#') {
            continue;
        }
        $value = str_replace("{db_prefix}", DB_PREFIX, trim($value));
        $query .= $value;
        if (preg_match("/\;$/i", $value)) {
            $testSql[] = $query;
            $query = '';
        }
    }
    $first_sql = $testSql[0];
    unset($testSql[0]);
    $querySql = array_reverse($testSql);
    $DB->query($first_sql, 1);
    foreach ($querySql as $sql) {
        $DB->query($sql, 1);
    }
    $CACHE->updateCache();
    @unlink($temp_sql_file);
    
    $ret = emUnZip($temp_zip_file, '../', 'update');
    switch ($ret) {
        case 1:
        case 2:
            Ret::error('更新失败，目录不可写，请设置您的站点目录权限');
        case 3:
            Ret::error('解压更新失败，可能是您的php未安装zip扩展（ZipArchive）');
    }
    @unlink($temp_zip_file);
    $url = EM_LINE[CURRENT_LINE]['value'] . 'api/emshop.php?action=app_upgrade_num_inc';
    $data = [
        'version'   => Option::EM_VERSION,
    ];
    emCurl($url, http_build_query($data), true);
    Ret::success('', '更新成功');
}
