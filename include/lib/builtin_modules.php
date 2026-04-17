<?php

function emLoadBuiltinModules()
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $loaded = true;

    $modules = [
        EM_ROOT . '/content/plugins/tips/tips.php',
        EM_ROOT . '/content/plugins/adm_home/adm_home.php',
        EM_ROOT . '/content/plugins/goods_once/goods_once.php',
        EM_ROOT . '/content/plugins/goods_general/goods_general.php',
    ];

    foreach ($modules as $module) {
        if (is_file($module)) {
            require_once $module;
        }
    }
}
