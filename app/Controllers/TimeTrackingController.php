<?php
/**
 * TimeTrackingController — Контроллер учёта времени
 *
 * Обрабатывает запросы на сохранение/обновление затраченного времени по задачам.
 * Эндпоинт: POST /tasks/{id}/time
 *
 * Доступ: авторизованные пользователи с проверкой прав через TaskAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Middleware\TaskAccessMiddleware;
use Services\TimeTrackingService;

class TimeTrackingController extends Controller
{
    private TimeTrackingService $timeTrackingService;

    public function __construct()
    {
        $this->timeTrackingService = new TimeTrackingService();
    }

    /**
     * Сохранить/обновить затраченное время
     * POST /tasks/{id}/time
     *
     * Принимает JSON: { "time_spent": float }
     * Возвращает JSON: { "success": true, "time_spent": float, "total_time": float }
     * Или ошибку: { "error": string }
     *
     * @param string $id ID задачи
     * @return void
     */
    public function store(string $id): void
    {
        $taskId = (int) $id;
        $userId = Auth::id();

        // Проверка авторизации
        if ($userId === null) {
            $this->json(['error' => 'Необходима авторизация'], 401);
            return;
        }

        // Проверка доступа к задаче
        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['error' => 'Нет доступа к задаче'], 403);
            return;
        }

        // Получаем JSON-тело запроса
        $input = json_decode(file_get_contents('php://input'), true);

        // Проверка: передано ли значение time_spent и является ли оно числом
        if (!isset($input['time_spent']) || !is_numeric($input['time_spent'])) {
            $this->json(['error' => 'Введите корректное числовое значение'], 422);
            return;
        }

        $timeSpent = (float) $input['time_spent'];

        try {
            // Определяем тип: исполнитель или руководитель
            $type = $input['type'] ?? 'executor';

            if ($type === 'manager') {
                // Сохраняем время руководителя
                $result = $this->timeTrackingService->saveManagerTime($taskId, $userId, $timeSpent);
                if ($result['success']) {
                    $this->json([
                        'success' => true,
                        'manager_time_spent' => $result['manager_time_spent'],
                    ]);
                } else {
                    $httpCode = $this->resolveErrorCode($result['error']);
                    $this->json(['error' => $result['error']], $httpCode);
                }
            } else {
                // Сохраняем время исполнителя
                $result = $this->timeTrackingService->saveTime($taskId, $userId, $timeSpent);
                if ($result['success']) {
                    $this->json([
                        'success' => true,
                        'time_spent' => $result['time_spent'],
                        'total_time' => $result['total_time'],
                    ]);
                } else {
                    $httpCode = $this->resolveErrorCode($result['error']);
                    $this->json(['error' => $result['error']], $httpCode);
                }
            }
        } catch (\Throwable $e) {
            $this->json(['error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Определить HTTP-код ответа по тексту ошибки из сервиса
     *
     * @param string|null $error Текст ошибки
     * @return int HTTP-код
     */
    private function resolveErrorCode(?string $error): int
    {
        if ($error === null) {
            return 500;
        }

        // Ошибки доступа — 403
        $accessErrors = [
            'Только назначенный исполнитель может вносить время',
            'Задача закрыта, редактирование времени недоступно',
            'Родительская задача закрыта, редактирование времени недоступно',
        ];

        if (in_array($error, $accessErrors, true)) {
            return 403;
        }

        // Задача не найдена — 404
        if ($error === 'Задача не найдена') {
            return 404;
        }

        // Ошибки валидации — 422
        return 422;
    }
}
