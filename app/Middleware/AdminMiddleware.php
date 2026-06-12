<?php
/**
 * AdminMiddleware — Проверка роли «Администратор»
 *
 * Пропускает только пользователей с role_id=1 (admin).
 * Используется для маршрутов администраторской панели:
 *   $router->group(['middleware' => ['auth', 'admin']], function ($router) { ... });
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Response;

class AdminMiddleware extends BaseMiddleware
{
    /**
     * Проверить, что пользователь — администратор
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

        // Только admin (role_id=1)
        if ($roleId !== 1) {
            Response::forbidden('У вас нет прав для доступа к этому разделу');
            return false;
        }

        return true;
    }
}
