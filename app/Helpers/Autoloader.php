<?php
/**
 * Autoloader — Автозагрузка классов (PSR-4 совместимый)
 * TODO: Реализовать полноценную автозагрузку в задаче 1.9
 */

spl_autoload_register(function (string $class): void {
    // Базовая директория для пространства имён App\
    $baseDir = BASE_PATH . '/app/';

    // Заменяем namespace-разделитель на разделитель директорий
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
