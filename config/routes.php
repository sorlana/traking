<?php
/**
 * Маршруты приложения Traking
 *
 * Здесь определяются все маршруты: публичные и защищённые.
 * Роутер создаётся, маршруты регистрируются, затем вызывается dispatch().
 */

use Helpers\Router;
use Helpers\Auth;

$router = new Router();

// ============================================================================
// Регистрация middleware
// ============================================================================
$router->registerMiddleware('auth', \Middleware\AuthMiddleware::class);
$router->registerMiddleware('admin', \Middleware\AdminMiddleware::class);
$router->registerMiddleware('manager', \Middleware\ManagerMiddleware::class);
$router->registerMiddleware('csrf', \Middleware\CsrfMiddleware::class);

// ============================================================================
// Публичные маршруты (без авторизации)
// ============================================================================

// Главная страница — редирект на /dashboard или /login
$router->get('/', function () {
    if (Auth::check()) {
        if (\Helpers\Auth::isAdmin()) {
            \Helpers\Response::redirect('/admin/users');
        } else {
            \Helpers\Response::redirect('/dashboard');
        }
    } else {
        // Проверяем cookie (может быть валидный токен)
        $plainToken = $_COOKIE['auth_token'] ?? null;
        if ($plainToken !== null && $plainToken !== '') {
            $authService = new \Services\AuthService();
            $user = $authService->verifyToken($plainToken);
            if ($user !== null) {
                if (\Helpers\Auth::isAdmin()) {
                    \Helpers\Response::redirect('/admin/users');
                } else {
                    \Helpers\Response::redirect('/dashboard');
                }
            }
        }
        \Helpers\Response::redirect('/login');
    }
});

// Авторизация — показ формы
$router->get('/login', [\Controllers\AuthController::class, 'showLogin']);

// Авторизация — обработка POST
$router->post('/login', [\Controllers\AuthController::class, 'login']);

// Выход из системы
$router->get('/logout', [\Controllers\AuthController::class, 'logout']);

// ============================================================================
// Защищённые маршруты (требуют авторизации)
// ============================================================================
$router->group(['middleware' => ['auth']], function (Router $router) {

    // Настройки пользователя
    $router->get('/settings', [\Controllers\SettingsController::class, 'index']);
    $router->post('/settings', [\Controllers\SettingsController::class, 'update']);
    $router->post('/settings/dnd', [\Controllers\SettingsController::class, 'toggleDnd']);

    // Push-уведомления
    $router->post('/push/subscribe', [\Controllers\PushController::class, 'subscribe']);
    $router->post('/push/unsubscribe', [\Controllers\PushController::class, 'unsubscribe']);
    $router->get('/push/vapid-key', [\Controllers\PushController::class, 'vapidKey']);

    // Дашборд — главная страница после входа
    $router->get('/dashboard', [\Controllers\DashboardController::class, 'index']);

    // Уведомления
    $router->get('/notifications', [\Controllers\NotificationController::class, 'index']);
    $router->post('/notifications/read-all', [\Controllers\NotificationController::class, 'markAllRead']);
    $router->post('/notifications/{id}/read', [\Controllers\NotificationController::class, 'markRead']);
    $router->get('/ajax/notifications/count', [\Controllers\NotificationController::class, 'ajaxCount']);
    $router->get('/ajax/notifications/list', [\Controllers\NotificationController::class, 'ajaxList']);

    // Проекты
    $router->get('/projects', [\Controllers\ProjectController::class, 'index']);
    $router->get('/projects/create', [\Controllers\ProjectController::class, 'create']);
    $router->post('/projects/create', [\Controllers\ProjectController::class, 'store']);
    $router->get('/projects/{id}', [\Controllers\ProjectController::class, 'show']);
    $router->get('/projects/{id}/edit', [\Controllers\ProjectController::class, 'edit']);
    $router->post('/projects/{id}/edit', [\Controllers\ProjectController::class, 'update']);
    $router->post('/projects/{id}/add-user', [\Controllers\ProjectController::class, 'addUser']);
    $router->post('/projects/{id}/remove-user', [\Controllers\ProjectController::class, 'removeUser']);
    $router->post('/projects/{id}/add-document', [\Controllers\ProjectController::class, 'addDocument']);
    $router->post('/projects/{id}/status', [\Controllers\ProjectController::class, 'changeStatus']);
    $router->post('/projects/{id}/delete', [\Controllers\ProjectController::class, 'delete']);

    // Задачи
    $router->get('/tasks/last', function () {
        // Переход к последней просмотренной задаче
        $lastTaskId = \Helpers\Session::get('last_task_id');
        if ($lastTaskId) {
            \Helpers\Response::redirect('/tasks/' . (int) $lastTaskId);
        } else {
            \Helpers\Response::redirect('/tasks');
        }
    });
    $router->get('/tasks', [\Controllers\TaskController::class, 'index']);
    $router->get('/tasks/create', [\Controllers\TaskController::class, 'create']);
    $router->post('/tasks/create', [\Controllers\TaskController::class, 'store']);
    $router->get('/tasks/{id}', [\Controllers\TaskController::class, 'show']);
    $router->get('/tasks/{id}/edit', [\Controllers\TaskController::class, 'edit']);
    $router->post('/tasks/{id}/edit', [\Controllers\TaskController::class, 'update']);
    $router->post('/tasks/{id}/status', [\Controllers\TaskController::class, 'changeStatus']);
    $router->post('/tasks/{id}/close', [\Controllers\TaskController::class, 'close']);
    $router->post('/tasks/{id}/reassign', [\Controllers\TaskController::class, 'reassign']);
    $router->post('/tasks/{id}/delete', [\Controllers\TaskController::class, 'delete']);

    // AJAX-эндпоинты для задач
    $router->get('/ajax/tasks/{id}/tree', [\Controllers\TaskController::class, 'ajaxTree']);

    // Комментарии (AJAX)
    $router->get('/ajax/tasks/{id}/messages', [\Controllers\CommentController::class, 'pollMessages']);
    $router->post('/tasks/{id}/comments', [\Controllers\CommentController::class, 'store']);
    $router->post('/tasks/{id}/messages/read', [\Controllers\CommentController::class, 'markRead']);
    $router->post('/comments/{id}/edit', [\Controllers\CommentController::class, 'update']);
    $router->post('/comments/{id}/delete', [\Controllers\CommentController::class, 'delete']);
    $router->post('/comments/{id}/pin', [\Controllers\CommentController::class, 'togglePin']);

    // Файлы и ссылки
    $router->post('/tasks/{id}/files', [\Controllers\FileController::class, 'upload']);
    $router->get('/files/{id}/download', [\Controllers\FileController::class, 'download']);
    $router->post('/files/{id}/delete', [\Controllers\FileController::class, 'delete']);
    $router->post('/tasks/{id}/links', [\Controllers\FileController::class, 'addLink']);
    $router->post('/links/{id}/delete', [\Controllers\FileController::class, 'deleteLink']);
});

// ============================================================================
// Маршруты для администратора
// ============================================================================
$router->group(['middleware' => ['auth', 'admin'], 'prefix' => '/admin'], function (Router $router) {
    // Управление пользователями
    $router->get('/users', [\Controllers\UserController::class, 'index']);
    $router->get('/users/create', [\Controllers\UserController::class, 'create']);
    $router->post('/users/create', [\Controllers\UserController::class, 'store']);
    $router->get('/users/{id}/edit', [\Controllers\UserController::class, 'edit']);
    $router->post('/users/{id}/edit', [\Controllers\UserController::class, 'update']);
    $router->post('/users/{id}/toggle-status', [\Controllers\UserController::class, 'toggleStatus']);
    $router->post('/users/{id}/reset-password', [\Controllers\UserController::class, 'resetPassword']);
});

// ============================================================================
// Запуск роутера — обработка текущего запроса
// ============================================================================
$router->dispatch();
