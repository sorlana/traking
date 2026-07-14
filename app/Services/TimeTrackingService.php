<?php
/**
 * TimeTrackingService — Сервис учёта времени
 *
 * Обеспечивает бизнес-логику учёта затраченного времени:
 * валидация значений, проверка прав доступа, сохранение
 * и логирование изменений в activity_log.
 */

namespace Services;

use Models\Task;
use Helpers\Database;

class TimeTrackingService
{
    private Task $taskModel;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * Валидация значения затраченного времени
     *
     * Проверяет: значение > 0, <= 999.5, кратно 0.5.
     *
     * @param float $value Значение для проверки
     * @return array Массив ошибок (пустой если валидно)
     */
    public function validateTimeValue(float $value): array
    {
        $errors = [];

        // Проверка: значение должно быть положительным
        if ($value <= 0) {
            $errors[] = 'Время должно быть положительным числом';
        }

        // Проверка: значение не должно превышать 999.5
        if ($value > 999.5) {
            $errors[] = 'Время не может превышать 999.5 часов';
        }

        // Проверка: значение должно быть кратно 0.5
        // Умножаем на 2 и проверяем целочисленность результата
        if (fmod($value * 2, 1) != 0) {
            $errors[] = 'Время должно быть кратно 0.5 часа';
        }

        return $errors;
    }

    /**
     * Проверка возможности редактирования времени
     *
     * Условия: assigned_to == userId, статус задачи != closed,
     * родительская задача (если есть) не закрыта.
     *
     * @param array $task Данные задачи (должен содержать assigned_to, status_code, id)
     * @param int $userId ID текущего пользователя
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canEditTime(array $task, int $userId): array
    {
        // Проверка: пользователь должен быть назначенным исполнителем
        if (empty($task['assigned_to']) || (int) $task['assigned_to'] !== $userId) {
            return [
                'allowed' => false,
                'reason' => 'Только назначенный исполнитель может вносить время',
            ];
        }

        // Проверка: задача не должна быть закрыта
        $statusCode = $task['status_code'] ?? '';
        if ($statusCode === 'closed') {
            return [
                'allowed' => false,
                'reason' => 'Задача закрыта, редактирование времени недоступно',
            ];
        }

        // Проверка: родительская задача не должна быть закрыта
        if (!empty($task['id']) && $this->isParentClosed((int) $task['id'])) {
            return [
                'allowed' => false,
                'reason' => 'Родительская задача закрыта, редактирование времени недоступно',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Сохранить затраченное время для задачи
     *
     * Выполняет полный цикл: валидация → проверка доступа →
     * сохранение в БД → логирование в activity_log.
     *
     * @param int $taskId ID задачи
     * @param int $userId ID текущего пользователя
     * @param float $timeSpent Значение времени
     * @return array ['success' => bool, 'error' => string|null, 'time_spent' => float|null, 'total_time' => float|null]
     */
    public function saveTime(int $taskId, int $userId, float $timeSpent): array
    {
        // 1. Валидация значения
        $errors = $this->validateTimeValue($timeSpent);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => $errors[0],
                'time_spent' => null,
                'total_time' => null,
            ];
        }

        // 2. Получаем задачу с данными о статусе
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.*, ts.code as status_code
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return [
                'success' => false,
                'error' => 'Задача не найдена',
                'time_spent' => null,
                'total_time' => null,
            ];
        }

        // 3. Проверка доступа
        $accessCheck = $this->canEditTime($task, $userId);
        if (!$accessCheck['allowed']) {
            return [
                'success' => false,
                'error' => $accessCheck['reason'],
                'time_spent' => null,
                'total_time' => null,
            ];
        }

        // 4. Получаем старое значение
        $oldValue = $this->taskModel->getTimeSpent($taskId);

        // 5. Обновляем time_spent в базе данных
        $this->taskModel->update($taskId, ['time_spent' => $timeSpent]);

        // 6. Получаем суммарное время по дереву задач
        $totalTime = $this->getTotalTime($taskId);

        return [
            'success' => true,
            'error' => null,
            'time_spent' => $timeSpent,
            'total_time' => $totalTime,
        ];
    }

    /**
     * Получить суммарное время по задаче и её подзадачам
     *
     * Использует метод Task::getTotalTimeWithChildren для расчёта
     * суммы time_spent задачи и всех прямых дочерних задач.
     *
     * @param int $taskId ID задачи
     * @return float Суммарное время (0.0 если не задано)
     */
    public function getTotalTime(int $taskId): float
    {
        return $this->taskModel->getTotalTimeWithChildren($taskId);
    }

    /**
     * Проверка: закрыта ли родительская задача
     *
     * Ищет родительскую задачу по parent_id и проверяет
     * её статус на 'closed'.
     *
     * @param int $taskId ID задачи
     * @return bool true если родительская задача закрыта
     */
    public function isParentClosed(int $taskId): bool
    {
        $parent = $this->taskModel->getParent($taskId);

        if ($parent === null) {
            // Нет родительской задачи — ограничений нет
            return false;
        }

        return ($parent['status_code'] ?? '') === 'closed';
    }

    /**
     * Проверка: может ли руководитель вносить время
     * Условия: пользователь — менеджер проекта, задача не closed
     *
     * @param array $task Данные задачи
     * @param int $userId ID пользователя
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canManagerEditTime(array $task, int $userId): array
    {
        // Проверка: задача не должна быть закрыта
        $statusCode = $task['status_code'] ?? '';
        if ($statusCode === 'closed') {
            return ['allowed' => false, 'reason' => 'Задача закрыта'];
        }

        // Проверка: родительская задача не закрыта
        if (!empty($task['id']) && $this->isParentClosed((int) $task['id'])) {
            return ['allowed' => false, 'reason' => 'Родительская задача закрыта'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Сохранить время руководителя
     *
     * @param int $taskId ID задачи
     * @param int $userId ID руководителя
     * @param float $timeSpent Значение времени
     * @return array
     */
    public function saveManagerTime(int $taskId, int $userId, float $timeSpent): array
    {
        // Валидация
        $errors = $this->validateTimeValue($timeSpent);
        if (!empty($errors)) {
            return ['success' => false, 'error' => $errors[0], 'manager_time_spent' => null];
        }

        // Получаем задачу
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.*, ts.code as status_code FROM tasks t JOIN task_statuses ts ON t.status_id = ts.id WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return ['success' => false, 'error' => 'Задача не найдена', 'manager_time_spent' => null];
        }

        // Проверка доступа
        $accessCheck = $this->canManagerEditTime($task, $userId);
        if (!$accessCheck['allowed']) {
            return ['success' => false, 'error' => $accessCheck['reason'], 'manager_time_spent' => null];
        }

        // Сохраняем
        $this->taskModel->update($taskId, ['manager_time_spent' => $timeSpent]);

        return ['success' => true, 'error' => null, 'manager_time_spent' => $timeSpent];
    }

    /**
     * Получить суммарное время руководителя по задаче и всем вложенным (рекурсивно)
     */
    public function getManagerTotalTime(int $taskId): float
    {
        // Собираем все ID рекурсивно через модель Task
        $allIds = $this->collectAllChildIds($taskId);
        $allIds[] = $taskId;

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($allIds), '?'));
        $result = $db->fetch(
            "SELECT COALESCE(SUM(manager_time_spent), 0) as total FROM tasks WHERE id IN ($placeholders)",
            $allIds
        );
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Рекурсивный сбор ID всех дочерних задач
     */
    private function collectAllChildIds(int $parentId): array
    {
        $db = Database::getInstance();
        $children = $db->fetchAll("SELECT id FROM tasks WHERE parent_id = ?", [$parentId]);
        $ids = [];
        foreach ($children as $child) {
            $ids[] = (int) $child['id'];
            $ids = array_merge($ids, $this->collectAllChildIds((int) $child['id']));
        }
        return $ids;
    }
}
