<?php

require_once 'init.php';

doAction('init');

$emDispatcher = Dispatcher::getInstance();


$emDispatcher->dispatch();



View::output();

