<?php
/**
 * CsrfMiddleware — Проверка CSRF-токена для POST-запросов
 *
 * Защищает от Cross-Site Request Forgery атак.
 * Проверяет наличие валидного _token в POST-данных формы.
 * AJAX-запросы (с заголовком X-Requested-With) пропускаются —
 * браузер не отправит такой заголовок при CSRF-атаке (CORS-защита).
 */

namespace Middleware;

use Helpers\Response;

class CsrfMiddleware extends BaseMiddleware
{
    /**
     * Обработать запрос — проверить CSRF-токен
     *
     * @return bool true — пропустить дальше, false — прервать обработку
     */
    public function handle(): bool
    {
        // CSRF проверяется только для POST-запросов
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }

        // AJAX-запросы с заголовком X-Requested-With пропускаем —
        // браузер блокирует кросс-доменные запросы с кастомными заголовками (CORS)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        // Проверяем CSRF-токен из формы
        if (!csrf_verify()) {
            Response::forbidden('Ошибка безопасности (CSRF). Перезагрузите страницу и попробуйте снова.');
            return false;
        }

        return true;
    }
}
