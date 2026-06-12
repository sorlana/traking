<?php
/**
 * Traking — Единая точка входа приложения
 * Все запросы через .htaccess перенаправляются сюда
 */

// Определяем корневой путь проекта
define('BASE_PATH', dirname(__DIR__));

// Автозагрузка классов
require_once BASE_PATH . '/app/Helpers/Autoloader.php';

// Глобальные хелпер-функции (e(), и т.д.)
require_once BASE_PATH . '/app/Helpers/functions.php';

// Загрузка конфигурации
$config = require BASE_PATH . '/config/app.php';
$GLOBALS['config'] = $config;

// Запуск сессии
session_start();

// Обработка ошибок
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Установка часового пояса
date_default_timezone_set($config['timezone'] ?? 'Europe/Moscow');

// Запуск роутера
// Временная отладка — удалить после настройки!
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'не задан') . "\n";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'не задан') . "\n";
    echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'не задан') . "\n";
    echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'не задан') . "\n";
    echo "BASE_PATH: " . BASE_PATH . "\n";
    echo "config.base_path: " . ($config['base_path'] ?? 'не задан') . "\n";
    echo "config.url: " . ($config['url'] ?? 'не задан') . "\n";
    exit;
}

require_once BASE_PATH . '/config/routes.php';
