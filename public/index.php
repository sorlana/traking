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

// Глобальный обработчик исключений — для AJAX возвращает JSON с деталями
set_exception_handler(function (Throwable $e) use ($config) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['error' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } else {
        if ($config['debug']) {
            echo '<h1>Ошибка</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
            echo 'Внутренняя ошибка сервера';
        }
    }
    exit;
});

// Установка часового пояса
date_default_timezone_set($config['timezone'] ?? 'Europe/Moscow');

// Запуск роутера
require_once BASE_PATH . '/config/routes.php';
