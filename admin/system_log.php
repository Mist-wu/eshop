<?php
/**
 * System log viewer
 * @package ESHOP
 */

require_once 'globals.php';

if (empty($action)) {
    $logBase = rtrim(LOG_PATH, '/\\');
    $logDates = [];

    if (is_dir($logBase)) {
        $monthDirs = glob($logBase . '/*', GLOB_ONLYDIR);
        if ($monthDirs) {
            foreach ($monthDirs as $monthDir) {
                $files = glob($monthDir . '/*.log');
                if (!$files) {
                    continue;
                }
                foreach ($files as $file) {
                    $date = basename($file, '.log');
                    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
                        $logDates[] = $date;
                    }
                }
            }
        }
    }

    $logDates = array_values(array_unique($logDates));
    rsort($logDates);

    $selectedDate = Input::getStrVar('date');
    if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $selectedDate) || !in_array($selectedDate, $logDates)) {
        $selectedDate = $logDates[0] ?? '';
    }

    $level = strtolower(Input::getStrVar('level', 'all'));
    $allowedLevels = ['all', 'error', 'warning', 'info', 'debug'];
    if (!in_array($level, $allowedLevels)) {
        $level = 'all';
    }

    $keyword = trim(Input::getStrVar('keyword'));
    $perpage = Input::getIntVar('perpage', 200);
    $perpageOptions = [50, 100, 200, 500];
    if (!in_array($perpage, $perpageOptions)) {
        $perpage = 200;
    }

    $page = Input::getIntVar('page', 1);
    if ($page < 1) {
        $page = 1;
    }

    $stats = [
        'all' => 0,
        'error' => 0,
        'warning' => 0,
        'info' => 0,
        'debug' => 0,
        'other' => 0,
    ];
    $entries = [];
    $filteredCount = 0;
    $logFile = '';
    $logFileExists = false;

    if ($selectedDate) {
        $monthDir = substr($selectedDate, 0, 7);
        $logFile = $logBase . '/' . $monthDir . '/' . $selectedDate . '.log';
        $logFileExists = is_file($logFile);
        if ($logFileExists) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES);
            if (is_array($lines)) {
                $lines = array_reverse($lines);
                foreach ($lines as $line) {
                    $line = trim((string)$line);
                    if ($line === '') {
                        continue;
                    }

                    $stats['all']++;
                    $time = '--:--:--';
                    $lineLevel = 'info';
                    $message = $line;

                    if (preg_match('/^\\[(\\d{2}:\\d{2}:\\d{2})\\]\\s+\\[([a-z]+)\\]\\s*(.*)$/i', $line, $match)) {
                        $time = $match[1];
                        $lineLevel = strtolower($match[2]);
                        $message = $match[3];
                    }

                    if (isset($stats[$lineLevel])) {
                        $stats[$lineLevel]++;
                    } else {
                        $stats['other']++;
                    }

                    if ($level !== 'all' && $lineLevel !== $level) {
                        continue;
                    }

                    if ($keyword !== '' && stripos($message, $keyword) === false && stripos($line, $keyword) === false) {
                        continue;
                    }

                    $filteredCount++;
                    $entries[] = [
                        'time' => $time,
                        'level' => $lineLevel,
                        'message' => $message,
                        'raw' => $line,
                    ];
                }
            }
        }
    }

    $totalPages = $perpage > 0 ? (int)ceil($filteredCount / $perpage) : 1;
    if ($totalPages < 1) {
        $totalPages = 1;
    }
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perpage;
    $entriesPage = array_slice($entries, $offset, $perpage);

    $query = [
        'date' => $selectedDate,
        'level' => $level,
        'keyword' => $keyword,
        'perpage' => $perpage,
    ];
    $pageUrl = 'system_log.php?' . http_build_query($query) . '&page=';
    $pageurl = pagination($filteredCount, $perpage, $page, $pageUrl);

    $logBaseDisplay = $logBase;
    if (strpos($logBaseDisplay, EM_ROOT) === 0) {
        $logBaseDisplay = ltrim(str_replace(EM_ROOT, '', $logBaseDisplay), '/\\');
    }
    $logFileDisplay = $logFile ? $logFile : '';
    if ($logFileDisplay && strpos($logFileDisplay, EM_ROOT) === 0) {
        $logFileDisplay = ltrim(str_replace(EM_ROOT, '', $logFileDisplay), '/\\');
    }

    $br = '<a href="./">控制台</a><a href="./setting.php">系统管理</a><a><cite>系统日志</cite></a>';

    include View::getAdmView('header');
    require_once View::getAdmView('system_log');
    include View::getAdmView('footer');
    View::output();
}
