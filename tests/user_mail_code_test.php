<?php

declare(strict_types=1);

define('ROLE', 'writer');
define('UID', 0);

class Option {
    public static function get($key) {
        return '';
    }
}

function testAssert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testAssertSame($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' | expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
    }
}

function testCase($name, callable $callback) {
    static $count = 0;
    $count++;
    $_SESSION = [];
    $callback();
    echo '[OK] ' . $name . PHP_EOL;
    return $count;
}

require_once dirname(__DIR__) . '/include/service/user.php';

$testsRun = 0;

$testsRun = testCase('mail code is single-use and clears the session code', function () {
    $_SESSION['mail_code'] = '654321';
    $_SESSION['mail'] = 'owner@example.com';

    testAssert(User::checkMailCode('654321'), 'Matching mail code should pass');
    testAssertSame(false, isset($_SESSION['mail_code']), 'Mail code should be cleared after verification');
    testAssertSame('owner@example.com', $_SESSION['mail'], 'Bound mail should stay available for reset flow');
    testAssertSame(false, User::checkMailCode('654321'), 'Cleared mail code must not be reusable');
});

$testsRun = testCase('mail code can be bound to the expected email address', function () {
    $_SESSION['mail_code'] = '123456';
    $_SESSION['mail'] = 'target@example.com';

    testAssert(User::checkMailCode('123456', 'target@example.com'), 'Matching code and mail should pass');
});

$testsRun = testCase('mail code rejects mismatched email address', function () {
    $_SESSION['mail_code'] = '123456';
    $_SESSION['mail'] = 'target@example.com';

    testAssertSame(false, User::checkMailCode('123456', 'other@example.com'), 'Verification should fail when the code was sent to a different email');
});

echo 'All tests passed. Total: ' . $testsRun . PHP_EOL;
