<?php
/**
 * ExecutorMiddleware — Проверка любой авторизованной роли
 *
 * Пропускает пользователей с role_id=1 (admin), 2 (manager) или 3 (executor).
 * По сути — проверяет, что пользователь авторизован И имеет валидную роль.
 * Используется для маршрутов, доступных всем авторизованным пользователям:
 *   $router->group(['middleware' => ['auth', 'executor']], function ($router) { ... });
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Response;

class ExecutorMiddleware extends BaseMiddleware
{
    /** @var array Допустимые роли */
    private const ALLOWED_ROLES = [1, 2, 3];

    /**
     * Проверить, что пользователь имеет одну из допустимых ролей
     *
     * @return bool true — доступ разрешён, false — отказ
     */
    public function handle(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            Response::redirect('/login');
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);

        // Любая валидная роль: admin(1), manager(2), executor(3)
        if (!in_array($roleId, self::ALLOWED_ROLES, true)) {
            Response::forbidden('У вас нет прав для доступа к этому разделу');
            return false;
        }

        return true;
    }
}
