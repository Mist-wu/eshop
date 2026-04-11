<?php



require_once '../user/globals.php';

if (empty($action)) {
    require_once(View::getUserView('home'));
    View::output();
}

