<?php
/**
 * Настройки подключения к базе данных
 * 
 * ВАЖНО: Этот файл в .gitignore — не коммитить с реальными данными!
 * Для деплоя создать копию с реальными данными на сервере.
 */

return [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 's265300_traking',
    'username' => 'c985833i_bd',
    'password' => '%NX9LDn*b%vH',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
