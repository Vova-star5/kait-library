<?php

/**
 * Конфигурация приложения.
 * Значения читаются из файла .env в корне проекта (см. .env.example).
 */

function load_env($path)
{
    $env = [];
    if (!file_exists($path)) {
        return $env;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $env[trim($parts[0])] = trim($parts[1], " \t\"'");
        }
    }
    return $env;
}

$env = load_env(__DIR__ . '/../.env');

define('APP_NAME', $env['APP_NAME'] ?? 'Библиотека КАИТ');
define('APP_URL', $env['APP_URL'] ?? 'http://localhost/kait-library/public');
define('APP_DEBUG', ($env['APP_DEBUG'] ?? 'true') === 'true');

define('DB_DRIVER', $env['DB_DRIVER'] ?? 'mysql');
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_PORT', $env['DB_PORT'] ?? '3306');
define('DB_NAME', $env['DB_NAME'] ?? 'kait_library');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('ITEMS_PER_PAGE', 6);

// Правила выдачи
define('MAX_BOOKS_STUDENT', 5);
define('MAX_BOOKS_TEACHER', 10);
define('LOAN_DAYS_STUDENT', 14);
define('LOAN_DAYS_TEACHER', 30);
define('FINE_PER_DAY', 10);

if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

date_default_timezone_set('Europe/Moscow');
