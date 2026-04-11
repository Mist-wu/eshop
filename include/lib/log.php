<?php

class Log {
    private static $logDir; // 静态日志目录

    // 初始化日志目录（静态构造逻辑）
    private static function init() {
        // 仅在首次调用时初始化目录
        if (!isset(self::$logDir)) {
            self::$logDir = rtrim(LOG_PATH, '/\\');
            // 若目录不存在则创建
            if (!is_dir(self::$logDir)) {
                if (!mkdir(self::$logDir, 0755, true)) {
                    error_log('Log init failed: cannot create log directory: ' . self::$logDir);
                }
            }
        }
    }

    // 核心日志记录方法（静态）
    private static function logFun($message, $level = 'info') {
        // 确保目录已初始化
        self::init();

        $date = date('Y-m-d');
        $month = date('Y-m');
        $time = date('H:i:s');
        $level = strtolower($level);
        // 防止多行污染日志
        $message = str_replace(["\r", "\n"], ' ', (string)$message);

        // 按月分目录，按日分文件
        $monthDir = self::$logDir . '/' . $month;
        if (!is_dir($monthDir)) {
            if (!mkdir($monthDir, 0755, true)) {
                error_log('Log write failed: cannot create month directory: ' . $monthDir);
                return;
            }
        }

        $logFile = $monthDir . '/' . $date . '.log';
        $logContent = "[{$time}] [{$level}] {$message}\n";

        // 追加写入日志
        if (file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX) === false) {
            error_log('Log write failed: ' . $logFile);
        }
    }

    // 静态快捷方法：记录错误
    public static function error($message) {
        self::logFun($message, 'error');
    }

    // 静态快捷方法：记录信息
    public static function info($message) {
        self::logFun($message, 'info');
    }

    // 可按需添加其他级别（如debug/warn）
    public static function debug($message) {
        self::logFun($message, 'debug');
    }

    public static function warning($message) {
        self::logFun($message, 'warning');
    }
}

