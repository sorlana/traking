<?php
/**
 * Router — Простой маршрутизатор для приложения Traking
 * 
 * Возможности:
 * - Регистрация GET/POST маршрутов
 * - Параметры в URL (например: /tasks/{id})
 * - Middleware для маршрутов
 * - Группировка маршрутов с общим prefix и middleware
 * - Диспатчинг — определение текущего URL и вызов контроллера
 */

namespace Helpers;

class Router
{
    /** @var array Зарегистрированные маршруты */
    private array $routes = [];

    /** @var array Стек групп (для вложенных group()) */
    private array $groupStack = [];

    /** @var array Зарегистрированные middleware-классы ['имя' => 'Класс'] */
    private array $middlewareMap = [];

    /**
     * Регистрация GET-маршрута
     *
     * @param string $uri URI маршрута (например: /tasks/{id})
     * @param array|callable $action Обработчик [Controller::class, 'method'] или callable
     * @param array $middleware Список middleware для маршрута
     * @return self
     */
    public function get(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Регистрация POST-маршрута
     *
     * @param string $uri URI маршрута
     * @param array|callable $action Обработчик
     * @param array $middleware Список middleware для маршрута
     * @return self
     */
    public function post(string $uri, array|callable $action, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Группировка маршрутов с общими настройками (prefix, middleware)
     *
     * @param array $attributes Атрибуты группы: ['prefix' => '/admin', 'middleware' => ['auth']]
     * @param callable $callback Функция с определением маршрутов внутри группы
     * @return self
     */
    public function group(array $attributes, callable $callback): self
    {
        // Помещаем атрибуты группы в стек
        $this->groupStack[] = $attributes;

        // Вызываем callback — внутри него регистрируются маршруты
        $callback($this);

        // Убираем группу из стека
        array_pop($this->groupStack);

        return $this;
    }

    /**
     * Регистрация именованного middleware
     *
     * @param string $name Имя middleware (например: 'auth')
     * @param string $class Полное имя класса middleware
     * @return self
     */
    public function registerMiddleware(string $name, string $class): self
    {
        $this->middlewareMap[$name] = $class;
        return $this;
    }

    /**
     * Диспатчинг — определяет текущий URL и HTTP-метод,
     * находит подходящий маршрут и вызывает обработчик
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getCurrentUri();

        // Ищем подходящий маршрут
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['pattern'], $uri);

            if ($params !== false) {
                // Выполняем middleware
                if (!$this->runMiddleware($route['middleware'])) {
                    return; // Middleware прервал выполнение
                }

                // Вызываем обработчик маршрута
                $this->callAction($route['action'], $params);
                return;
            }
        }

        // Маршрут не найден — 404
        $this->notFound();
    }

    /**
     * Получить все зарегистрированные маршруты (для отладки)
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    // ========================================================================
    // Приватные методы
    // ========================================================================

    /**
     * Добавление маршрута в таблицу маршрутов
     */
    private function addRoute(string $method, string $uri, array|callable $action, array $middleware = []): self
    {
        // Собираем prefix из стека групп
        $prefix = $this->getGroupPrefix();

        // Собираем middleware из стека групп + переданные напрямую
        $groupMiddleware = $this->getGroupMiddleware();
        $allMiddleware = array_merge($groupMiddleware, $middleware);

        // Формируем полный URI с prefix
        $fullUri = rtrim($prefix . '/' . ltrim($uri, '/'), '/');
        if ($fullUri === '') {
            $fullUri = '/';
        }

        // Конвертируем URI в regex-паттерн для сопоставления
        $pattern = $this->uriToPattern($fullUri);

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $fullUri,
            'pattern'    => $pattern,
            'action'     => $action,
            'middleware'  => array_unique($allMiddleware),
        ];

        return $this;
    }

    /**
     * Получить текущий prefix из стека групп
     */
    private function getGroupPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix;
    }

    /**
     * Получить все middleware из стека групп
     */
    private function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return $middleware;
    }

    /**
     * Преобразует URI-шаблон в регулярное выражение
     * Например: /tasks/{id} → #^/tasks/([^/]+)$#
     *
     * @param string $uri URI с параметрами в фигурных скобках
     * @return string Регулярное выражение
     */
    private function uriToPattern(string $uri): string
    {
        // Экранируем спецсимволы кроме { и }
        $pattern = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($matches) {
            // Каждый параметр — именованная группа захвата
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $uri);

        return '#^' . $pattern . '$#';
    }

    /**
     * Проверяет, соответствует ли URI паттерну маршрута
     *
     * @param string $pattern Regex-паттерн маршрута
     * @param string $uri Текущий URI
     * @return array|false Массив параметров или false
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        if (preg_match($pattern, $uri, $matches)) {
            // Извлекаем только именованные параметры
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    /**
     * Определяет текущий URI из REQUEST_URI
     * Убирает query string, base_path (подпапку) и нормализует путь
     */
    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Убираем query string (?key=value)
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Убираем base_path (подпапку) из URI
        // Например: /traking/login → /login
        $basePath = defined('BASE_PATH') ? ($GLOBALS['config']['base_path'] ?? '') : '';
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        // Убираем /public из URI (если запрос прошёл через public/)
        if (str_starts_with($uri, '/public')) {
            $uri = substr($uri, 7); // убираем "/public"
        }

        // Убираем trailing slash (кроме корня)
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        return $uri;
    }

    /**
     * Выполняет цепочку middleware
     *
     * @param array $middlewareList Список имён middleware
     * @return bool true если все пропустили, false если кто-то прервал
     */
    private function runMiddleware(array $middlewareList): bool
    {
        foreach ($middlewareList as $name) {
            // Получаем класс middleware по имени
            $class = $this->middlewareMap[$name] ?? null;

            if ($class === null) {
                // Middleware не зарегистрирован — пропускаем с предупреждением
                error_log("Router: middleware '{$name}' не зарегистрирован");
                continue;
            }

            if (!class_exists($class)) {
                error_log("Router: класс middleware '{$class}' не найден");
                continue;
            }

            $instance = new $class();

            // Middleware должен иметь метод handle(), возвращающий bool
            if (method_exists($instance, 'handle')) {
                $result = $instance->handle();
                if ($result === false) {
                    return false; // Middleware прервал выполнение
                }
            }
        }

        return true;
    }

    /**
     * Вызывает обработчик маршрута (контроллер или callable)
     *
     * @param array|callable $action Обработчик
     * @param array $params Параметры из URL
     */
    private function callAction(array|callable $action, array $params): void
    {
        if (is_callable($action) && !is_array($action)) {
            // Если action — это замыкание (callable)
            call_user_func_array($action, array_values($params));
            return;
        }

        // action — массив [ClassName, 'method']
        [$controllerClass, $method] = $action;

        if (!class_exists($controllerClass)) {
            error_log("Router: контроллер '{$controllerClass}' не найден");
            $this->notFound();
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            error_log("Router: метод '{$method}' не найден в контроллере '{$controllerClass}'");
            $this->notFound();
            return;
        }

        // Вызываем метод контроллера с параметрами из URL (только значения, без ключей)
        call_user_func_array([$controller, $method], array_values($params));
    }

    /**
     * Ответ 404 — маршрут не найден
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 — Не найдено</title></head>';
        echo '<body style="font-family:sans-serif;text-align:center;padding:50px;">';
        echo '<h1>404</h1><p>Страница не найдена</p>';
        echo '<a href="/">← На главную</a>';
        echo '</body></html>';
    }
}
