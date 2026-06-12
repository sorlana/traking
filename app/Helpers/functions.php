<?php
/**
 * Глобальные хелпер-функции приложения Traking
 *
 * Подключается в public/index.php для доступа из любого шаблона и контроллера.
 * Содержит: экранирование вывода, CSRF-защита, URL-хелперы.
 */

// ============================================================================
// URL-хелпер для подпапки
// ============================================================================

if (!function_exists('url')) {
    /**
     * Генерирует полный URL с учётом base_path (подпапки)
     *
     * Пример:
     *   url('/login') → '/traking/login'
     *   url('/tasks/5') → '/traking/tasks/5'
     *
     * @param string $path Относительный путь (начинается с /)
     * @return string Полный путь с base_path
     */
    function url(string $path = '/'): string
    {
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Генерирует URL для статического файла (из public/assets/)
     *
     * Пример:
     *   asset('/assets/css/app.css') → '/traking/assets/css/app.css'
     *
     * @param string $path Путь к файлу (начинается с /)
     * @return string Полный путь
     */
    function asset(string $path): string
    {
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Экранирование вывода — обёртка над htmlspecialchars()
     *
     * Используется в шаблонах для безопасного вывода данных:
     *   <?= e($user['name']) ?>
     *
     * @param mixed $value Значение для экранирования
     * @return string Экранированная строка
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ============================================================================
// CSRF-защита
// ============================================================================

if (!function_exists('csrf_token')) {
    /**
     * Генерирует или возвращает CSRF-токен из сессии
     *
     * Токен создаётся один раз за сессию и сохраняется.
     * Используется для защиты форм от CSRF-атак.
     *
     * @return string CSRF-токен (64 символа hex)
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Возвращает скрытое поле формы с CSRF-токеном
     *
     * Вставляется в каждую форму с методом POST:
     *   <form method="POST"> <?= csrf_field() ?> ... </form>
     *
     * @return string HTML-тег <input type="hidden">
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . e($token) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Проверяет CSRF-токен из POST-запроса с токеном в сессии
     *
     * Сравнивает $_POST['_token'] с сессионным токеном.
     * Используется в CsrfMiddleware для защиты POST-запросов.
     *
     * @return bool true если токен валиден, false если нет
     */
    function csrf_verify(): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        $postToken = $_POST['_token'] ?? '';

        if ($sessionToken === '' || $postToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $postToken);
    }
}


// ============================================================================
// Проверка прав доступа
// ============================================================================

if (!function_exists('can')) {
    /**
     * Проверка прав доступа — используется в шаблонах и контроллерах
     *
     * Примеры использования:
     *   if (can('manage_users')) { ... }
     *   if (can('edit_project', $projectId)) { ... }
     *   if (can('view_task', $taskId)) { ... }
     *
     * @param string $action Действие ('manage_users', 'create_project', 'create_task', и т.д.)
     * @param mixed $resource Опциональный ресурс (project_id, task_id)
     * @return bool
     */
    function can(string $action, mixed $resource = null): bool
    {
        $user = \Helpers\Auth::user();
        if ($user === null) {
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);

        // Admin может всё
        if ($roleId === 1) {
            return true;
        }

        return match ($action) {
            'manage_users' => false, // Только admin
            'create_project' => $roleId <= 2, // Admin или Manager
            'edit_project' => $roleId <= 2 && ($resource === null || \Middleware\ProjectAccessMiddleware::check((int) $resource)),
            'create_task' => $roleId <= 2 && ($resource === null || \Middleware\ProjectAccessMiddleware::check((int) $resource)),
            'view_project' => $resource !== null && \Middleware\ProjectAccessMiddleware::check((int) $resource),
            'view_task' => $resource !== null && \Middleware\TaskAccessMiddleware::check((int) $resource),
            default => false,
        };
    }
}
