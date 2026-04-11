<?php

/**
 * @var string $action
 * @var object $CACHE
 */

require_once 'globals.php';

$db = Database::getInstance();
$db_prefix = DB_PREFIX;


if($action == 'setting_page'){
    $tpl = Input::getStrVar('tpl');
    include View::getUserView('open_head');
    require_once "../content/templates/$tpl/setting.php";
    plugin_setting_view();
    include View::getUserView('open_foot');
}
if($action == 'setting_ajax'){
    $tpl = Input::getStrVar('tpl');

    require_once "../content/templates/$tpl/setting.php";

    plugin_setting($tpl);
}
