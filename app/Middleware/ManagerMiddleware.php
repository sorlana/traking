<?php
/**
 * ManagerMiddleware — Проверка роли «Руководитель» или выше
 *
 * Пропускает пользователей с role_id=1 (admin) или role_id=2 (manager).
 * Используется для маршрутов, доступных руководителям и администраторам:
 *   $router->group(['middleware' => ['auth', 'manager']], function ($router) { ... });
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Response;

class ManagerMiddleware extends BaseMiddleware
{
    /**
     * Проверить, что пользователь — руководитель или администратор
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

        // Admin (1) или Manager (2)
        if ($roleId !== 1 && $roleId !== 2) {
            Response::forbidden('У вас нет прав для доступа к этому разделу');
            return false;
        }

        return true;
    }
}
