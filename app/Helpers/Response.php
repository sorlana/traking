<?php
/**
 * Response — Формирование ответа клиенту
 *
 * Статические методы для отправки различных типов HTTP-ответов:
 * HTML (через View), JSON, редиректы, ошибки.
 *
 * Пример использования:
 *   Response::view('tasks/show', ['task' => $task]);
 *   Response::redirect('/login');
 *   Response::json(['success' => true]);
 *   Response::notFound('Задача не найдена');
 */

namespace Helpers;

class Response
{
    /**
     * Рендерит шаблон и отправляет HTML-ответ
     *
     * @param string $template Имя шаблона (например: 'tasks/show')
     * @param array $data Данные для передачи в шаблон
     * @return void
     */
    public static function view(string $template, array $data = []): void
    {
        echo View::make($template, $data);
    }

    /**
     * Редирект на указанный URL
     *
     * @param string $url URL для перенаправления
     * @param int $code HTTP-код (по умолчанию 302 — временный)
     * @return void
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    /**
     * Отправить JSON-ответ
     *
     * @param mixed $data Данные для сериализации в JSON
     * @param int $code HTTP-код ответа
     * @return void
     */
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Ответ 403 — Доступ запрещён
     *
     * @param string $message Сообщение об ошибке
     * @return void
     */
    public static function forbidden(string $message = 'Доступ запрещён'): void
    {
        http_response_code(403);
        echo self::renderError(403, $message);
        exit;
    }

    /**
     * Ответ 404 — Не найдено
     *
     * @param string $message Сообщение об ошибке
     * @return void
     */
    public static function notFound(string $message = 'Не найдено'): void
    {
        http_response_code(404);
        echo self::renderError(404, $message);
        exit;
    }

    /**
     * Редирект на предыдущую страницу (HTTP_REFERER)
     * Если referer отсутствует — редирект на главную
     *
     * @return void
     */
    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    /**
     * Формирует простую HTML-страницу ошибки
     *
     * @param int $code HTTP-код
     * @param string $message Текст ошибки
     * @return string HTML
     */
    private static function renderError(int $code, string $message): string
    {
        $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} — {$escapedMessage}</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f9fafb; }
        h1 { font-size: 4rem; color: #374151; margin-bottom: 0.5rem; }
        p { font-size: 1.25rem; color: #6b7280; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>{$code}</h1>
    <p>{$escapedMessage}</p>
    <a href="/">← На главную</a>
</body>
</html>
HTML;
    }
}
