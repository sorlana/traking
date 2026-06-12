<?php
/**
 * AuthMiddleware — Проверка авторизации пользователя
 *
 * Логика:
 *   1. Проверяем, есть ли пользователь в сессии (Auth::check())
 *   2. Если нет — проверяем cookie auth_token через AuthService::verifyToken
 *   3. Если нет валидного токена — redirect /login, return false
 *   4. Если ОК — return true (пропускаем к контроллеру)
 *
 * Используется для защиты маршрутов, требующих авторизации:
 *   $router->group(['middleware' => ['auth']], function ($router) { ... });
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Response;
use Services\AuthService;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * Проверить авторизацию пользователя
     *
     * @return bool true — пользователь авторизован, false — нет доступа
     */
    public function handle(): bool
    {
        // 1. Проверяем сессию — пользователь уже авторизован?
        if (Auth::check()) {
            return true;
        }

        // 2. Проверяем cookie auth_token — может быть «запомнен»
        $plainToken = $_COOKIE['auth_token'] ?? null;

        if ($plainToken !== null && $plainToken !== '') {
            $authService = new AuthService();
            $user = $authService->verifyToken($plainToken);

            if ($user !== null) {
                // Токен валиден — пользователь восстановлен в сессии
                return true;
            }
        }

        // 3. Нет авторизации — редирект на страницу входа
        Response::redirect('/login');
        return false;
    }
}
