<?php

require_once 'globals.php';

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    Ret::error('修复系统已删除');
}

emDirect('./');
