<?php

require_once 'globals.php';

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    Ret::error('插件管理功能已删除');
}

emDirect('./');
