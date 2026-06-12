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
require_once BASE_PATH . '/config/routes.php';
