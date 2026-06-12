<?php
/**
 * BaseMiddleware — Абстрактный базовый класс для всех middleware
 *
 * Каждый middleware должен наследовать этот класс и реализовать метод handle().
 * Метод handle() возвращает true если запрос может продолжить обработку,
 * или false если запрос должен быть прерван (например, пользователь не авторизован).
 *
 * Пример реализации:
 *   class AuthMiddleware extends BaseMiddleware {
 *       public function handle(): bool {
 *           if (!Auth::check()) {
 *               Response::redirect('/login');
 *               return false;
 *           }
 *           return true;
 *       }
 *   }
 */

namespace Middleware;

abstract class BaseMiddleware
{
    /**
     * Обработать запрос
     *
     * @return bool true — пропустить дальше, false — прервать обработку
     */
    abstract public function handle(): bool;
}
