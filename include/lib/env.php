<?php

/**
 * 轻量 .env 读取器
 * - 支持 KEY=VALUE
 * - 支持 export KEY=VALUE
 * - 支持单/双引号
 * - 支持双引号跨行 PEM 文本
 */

function emLoadEnv($path = null) {
    static $cache = [];

    $path = $path ?: EM_ROOT . '/.env';
    if (isset($cache[$path])) {
        return $cache[$path];
    }

    $values = [];
    if (!is_file($path) || !is_readable($path)) {
        $cache[$path] = $values;
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        $cache[$path] = $values;
        return $values;
    }

    $pendingKey = null;
    $pendingQuote = null;
    $pendingValue = '';

    foreach ($lines as $line) {
        if ($pendingKey !== null) {
            $pendingValue .= "\n" . $line;
            if (emEnvEndsWithQuote($pendingValue, $pendingQuote)) {
                $values[$pendingKey] = emEnvParseValue($pendingValue);
                emEnvSet($pendingKey, $values[$pendingKey]);
                $pendingKey = null;
                $pendingQuote = null;
                $pendingValue = '';
            }
            continue;
        }

        $trimmed = ltrim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        if (strpos($trimmed, 'export ') === 0) {
            $trimmed = substr($trimmed, 7);
        }

        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $trimmed, $matches)) {
            continue;
        }

        $key = $matches[1];
        $rawValue = $matches[2];

        if ($rawValue !== '' && ($rawValue[0] === '"' || $rawValue[0] === "'") && !emEnvEndsWithQuote($rawValue, $rawValue[0])) {
            $pendingKey = $key;
            $pendingQuote = $rawValue[0];
            $pendingValue = $rawValue;
            continue;
        }

        $values[$key] = emEnvParseValue($rawValue);
        emEnvSet($key, $values[$key]);
    }

    if ($pendingKey !== null) {
        $values[$pendingKey] = emEnvParseValue($pendingValue);
        emEnvSet($pendingKey, $values[$pendingKey]);
    }

    $cache[$path] = $values;
    return $values;
}

function emEnv($key, $default = null) {
    static $loaded = false;
    if (!$loaded) {
        emLoadEnv();
        $loaded = true;
    }

    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return $value;
}

function emEnvBool($key, $default = false) {
    $value = emEnv($key, null);
    if ($value === null) {
        return $default;
    }

    $value = strtolower(trim((string)$value));
    if ($value === '') {
        return $default;
    }

    return in_array($value, ['1', 'true', 'yes', 'on', 'y'], true);
}

function emEnvSet($key, $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}

function emEnvEndsWithQuote($value, $quote) {
    $length = strlen($value);
    if ($length < 2 || $value[$length - 1] !== $quote) {
        return false;
    }

    $slashes = 0;
    for ($i = $length - 2; $i >= 0; $i--) {
        if ($value[$i] !== '\\') {
            break;
        }
        $slashes++;
    }

    return $slashes % 2 === 0;
}

function emEnvParseValue($rawValue) {
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return '';
    }

    $first = $rawValue[0];
    $last = substr($rawValue, -1);

    if (($first === '"' || $first === "'") && $last === $first && emEnvEndsWithQuote($rawValue, $first)) {
        $value = substr($rawValue, 1, -1);

        if ($first === '"') {
            $value = str_replace(
                ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                ["\n", "\r", "\t", '"', '\\'],
                $value
            );
        } else {
            $value = str_replace(["\\'", '\\\\'], ["'", '\\'], $value);
        }

        return $value;
    }

    $rawValue = preg_replace('/\s+#.*$/', '', $rawValue);
    return trim((string)$rawValue);
}
