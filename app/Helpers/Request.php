<?php
/**
 * Request — Обёртка над суперглобальными массивами запроса
 *
 * Предоставляет удобный доступ к данным HTTP-запроса:
 * $_GET, $_POST, $_FILES, $_COOKIE, заголовкам и метаинформации.
 *
 * Пример использования:
 *   $request = new Request();
 *   $email = $request->post('email');
 *   $page = $request->get('page', 1);
 *   if ($request->isAjax()) { ... }
 */

namespace Helpers;

class Request
{
    /**
     * Получить HTTP-метод запроса (GET, POST)
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Получить значение из $_GET
     *
     * @param string $key Имя параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Получить значение из $_POST
     *
     * @param string $key Имя параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Получить значение из $_POST или $_GET (POST имеет приоритет)
     *
     * @param string $key Имя параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Получить все параметры запроса (POST + GET)
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Получить загруженный файл из $_FILES
     *
     * @param string $key Имя поля файла
     * @return array|null Информация о файле или null
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Получить значение из $_COOKIE
     *
     * @param string $key Имя cookie
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Проверить, является ли запрос AJAX (XMLHttpRequest)
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Получить IP-адрес клиента
     *
     * @return string
     */
    public function ip(): string
    {
        // Проверяем заголовки прокси
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Берём первый IP из списка (реальный клиент)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Получить User-Agent клиента
     *
     * @return string
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Получить текущий URI запроса (без query string)
     *
     * @return string
     */
    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Убираем query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }
}
