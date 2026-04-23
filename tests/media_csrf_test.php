<?php

declare(strict_types=1);

define('EM_ROOT', dirname(__DIR__));

function emMsg($msg, $url = '') {
    throw new RuntimeException($url === '' ? $msg : $msg . ' | ' . $url);
}

require_once EM_ROOT . '/include/lib/loginauth.php';

function testAssert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function testAssertStringContains($needle, $haystack, $message) {
    if (strpos((string)$haystack, (string)$needle) === false) {
        throw new RuntimeException($message . ' | needle=' . var_export($needle, true) . ' haystack=' . var_export($haystack, true));
    }
}

function testCase($name, callable $callback) {
    static $count = 0;
    $count++;
    $callback();
    echo '[OK] ' . $name . PHP_EOL;
    return $count;
}

function testResetRequest($requestToken = null) {
    $_SESSION = ['em_csrf_token' => 'expected-token'];
    $_REQUEST = [];
    if ($requestToken !== null) {
        $_REQUEST['token'] = $requestToken;
    }
}

function testExpectTokenFailure($name, callable $callback) {
    try {
        $callback();
    } catch (RuntimeException $e) {
        testAssertStringContains('安全token校验失败', $e->getMessage(), $name . ' should fail with csrf message');
        return;
    }
    throw new RuntimeException($name . ' should reject the request');
}

function testMediaActionBlock($action) {
    $source = file_get_contents(EM_ROOT . '/admin/media.php');
    $pattern = "/^if \\(\\\$action\\s*=+\\s*'{$action}'\\) \\{(?P<body>.*?)(?=^if \\(\\\$action\\s*=+\\s*'|\\z)/ms";
    if (!preg_match($pattern, $source, $matches)) {
        throw new RuntimeException('Missing media action block: ' . $action);
    }
    return $matches['body'];
}

$testsRun = 0;

$testsRun = testCase('csrf check rejects missing token', function () {
    testResetRequest();
    testExpectTokenFailure('missing token', function () {
        LoginAuth::checkToken();
    });
});

$testsRun = testCase('csrf check rejects wrong token', function () {
    testResetRequest('wrong-token');
    testExpectTokenFailure('wrong token', function () {
        LoginAuth::checkToken();
    });
});

$testsRun = testCase('csrf check accepts matching token', function () {
    testResetRequest('expected-token');
    LoginAuth::checkToken();
    testAssert(true, 'matching token should pass');
});

$testsRun = testCase('media write actions enforce csrf token', function () {
    $writeActions = [
        'upload',
        'delete',
        'delete_async',
        'operate_media',
        'update_media',
        'add_media_sort',
        'update_media_sort',
        'del_media_sort',
    ];

    foreach ($writeActions as $action) {
        testAssertStringContains(
            'LoginAuth::checkToken();',
            testMediaActionBlock($action),
            'media action should check token: ' . $action
        );
    }
});

$testsRun = testCase('media javascript write requests include csrf token', function () {
    $mediaView = file_get_contents(EM_ROOT . '/admin/views/media.php');
    $twitterView = file_get_contents(EM_ROOT . '/admin/views/twitter.php');
    $headerView = file_get_contents(EM_ROOT . '/admin/views/header.php');
    $commonJs = file_get_contents(EM_ROOT . '/admin/views/js/common.js');
    $mediaLibJs = file_get_contents(EM_ROOT . '/admin/views/js/media-lib.js');

    testAssertStringContains('window.EM_ADMIN_TOKEN', $headerView, 'admin header should expose csrf token to shared javascript');
    testAssertStringContains('data: { token: mediaToken }', $mediaView, 'layui media upload should include token');
    testAssertStringContains('token: mediaToken', $mediaView, 'layui media delete should include token');
    testAssertStringContains('token=<?= LoginAuth::genToken() ?>', $twitterView, 'editor.md upload url should include token');
    testAssertStringContains("formData.append('token', token)", $commonJs, 'pasted image upload should append token');
    testAssertStringContains('token: getAdminCsrfToken()', $mediaLibJs, 'media library async delete should include token');
    testAssertStringContains('formData.append("token", getAdminCsrfToken())', $mediaLibJs, 'dropzone media upload should append token');
});

echo 'Tests run: ' . $testsRun . PHP_EOL;
