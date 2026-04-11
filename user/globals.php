<?php
/**
 * global
 * @package EMSHOP
 */

/**
 * @var string $action
 * @var object $CACHE
 */

require_once '../init.php';

$sta_cache = $CACHE->readCache('sta');
$action = Input::getStrVar('action');




loginAuth::checkLogin(NULL, 'user');

