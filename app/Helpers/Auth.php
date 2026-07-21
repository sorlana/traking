<?php
/**
 * Auth — Работа с текущим авторизованным пользователем
 *
 * Хранит информацию о текущем пользователе в сессии.
 * Предоставляет методы проверки ролей и состояния авторизации.
 *
 * Пример использования:
 *   if (Auth::check()) {
 *       $user = Auth::user();
 *       echo $user['login'];
 *   }
 *   if (Auth::isAdmin()) { ... }
 */

namespace Helpers;

class Auth
{
    /** @var string Ключ в сессии для хранения данных пользователя */
    private const SESSION_KEY = 'auth_user';

    /** @var int ID роли «Администратор» */
    public const ROLE_ADMIN = 1;

    /** @var int ID роли «Руководитель» */
    public const ROLE_MANAGER = 2;

    /** @var int ID роли «Исполнитель» */
    public const ROLE_EXECUTOR = 3;

    /**
     * Получить данные текущего авторизованного пользователя
     *
     * @return array|null Массив с данными пользователя или null
     */
    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Проверить, авторизован ли пользователь
     *
     * @return bool
     */
    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Получить ID текущего пользователя
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    /**
     * Проверить, является ли текущий пользователь администратором
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && (int) ($user['role_id'] ?? 0) === self::ROLE_ADMIN;
    }

    /**
     * Проверить, является ли текущий пользователь руководителем
     *
     * @return bool
     */
    public static function isManager(): bool
    {
        $user = self::user();
        return $user && (int) ($user['role_id'] ?? 0) === self::ROLE_MANAGER;
    }

    /**
     * Проверить, является ли текущий пользователь исполнителем
     *
     * @return bool
     */
    public static function isExecutor(): bool
    {
        $user = self::user();
        return $user && (int) ($user['role_id'] ?? 0) === self::ROLE_EXECUTOR;
    }

    /**
     * Установить текущего пользователя (после успешной авторизации)
     *
     * @param array $user Данные пользователя из БД
     * @return void
     */
    public static function setUser(array $user): void
    {
        Session::set(self::SESSION_KEY, $user);
    }

    /**
     * Выйти из системы — удалить данные пользователя из сессии
     *
     * @return void
     */
    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
    }
}
