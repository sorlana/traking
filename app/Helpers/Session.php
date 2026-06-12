<?php
/**
 * Session — Работа с сессиями PHP
 *
 * Обёртка над $_SESSION с поддержкой flash-сообщений (одноразовых данных).
 * Flash-данные доступны только при следующем запросе и удаляются после чтения.
 *
 * Пример использования:
 *   Session::set('user_id', 42);
 *   $userId = Session::get('user_id');
 *   Session::flash('success', 'Задача создана!');
 *   $message = Session::getFlash('success'); // получить и удалить
 */

namespace Helpers;

class Session
{
    /** @var string Префикс для flash-сообщений в сессии */
    private const FLASH_PREFIX = '_flash_';

    /**
     * Получить значение из сессии
     *
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Установить значение в сессии
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Проверить наличие ключа в сессии
     *
     * @param string $key Ключ
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Удалить значение из сессии
     *
     * @param string $key Ключ
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Установить flash-сообщение (одноразовое, доступно до первого чтения)
     *
     * Если $value = null, работает как getFlash() — возвращает и удаляет.
     *
     * @param string $key Ключ flash-сообщения
     * @param mixed $value Значение (null для получения)
     * @return mixed|null Значение при чтении, null при записи
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value === null) {
            return self::getFlash($key);
        }

        $_SESSION[self::FLASH_PREFIX . $key] = $value;
        return null;
    }

    /**
     * Получить flash-сообщение и удалить его из сессии
     *
     * @param string $key Ключ flash-сообщения
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $flashKey = self::FLASH_PREFIX . $key;

        if (isset($_SESSION[$flashKey])) {
            $value = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $value;
        }

        return $default;
    }

    /**
     * Полностью уничтожить сессию
     *
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        // Удаляем cookie сессии
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
