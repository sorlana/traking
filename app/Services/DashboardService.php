<?php
/**
 * DashboardService — Сервис для единого дашборда (Manager и Executor)
 *
 * Формирует данные для канбан-доски: проекты пользователя и задачи,
 * сгруппированные по статусам (in_progress, revision, done).
 * Фильтрация по роли: Manager видит все задачи, Executor — только свои.
 */

namespace Services;

use Helpers\Database;
use Models\Project;

class DashboardService
{
    /**
     * Получить данные для единого дашборда
     *
     * @param int $userId ID текущего пользователя
     * @param int $roleId Роль пользователя (2 = Manager, 3 = Executor)
     * @return array Структура: ['projects' => [...], 'boardData' => [...]]
     */
    public function getBoardData(int $userId, int $roleId): array
    {
        $projectModel = new Project();
        $projects = $projectModel->getUserProjects($userId, $roleId);

        $boardData = [];
        foreach ($projects as $project) {
            $boardData[$project['id']] = $this->getProjectBoardTasks(
                (int) $project['id'], $userId, $roleId
            );
        }

        return [
            'projects' => $projects,
            'boardData' => $boardData,
        ];
    }

    /**
     * Получить задачи проекта для доски, сгруппированные по статусам
     *
     * @param int $projectId ID проекта
     * @param int $userId ID пользователя
     * @param int $roleId Роль (2 или 3)
     * @return array ['in_progress' => [...], 'revision' => [...], 'done' => [...]]
     */
    public function getProjectBoardTasks(int $projectId, int $userId, int $roleId): array
    {
        $db = Database::getInstance();

        $sql = "SELECT t.id, t.title, t.priority, t.deadline, t.assigned_to,
                       ts.code AS status_code, ts.name AS status_name,
                       u.name AS assigned_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                  AND ts.code IN ('in_progress', 'revision', 'done')";
        $params = [$projectId];

        // Для Executor — только назначенные на него задачи
        if ($roleId === 3) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.deadline ASC";

        $tasks = $db->fetchAll($sql, $params);

        return $this->groupTasksByStatus($tasks);
    }

    /**
     * Группировка задач по статусу
     *
     * @param array $tasks Плоский массив задач
     * @return array ['in_progress' => [...], 'revision' => [...], 'done' => [...]]
     */
    public function groupTasksByStatus(array $tasks): array
    {
        $grouped = [
            'in_progress' => [],
            'revision' => [],
            'done' => [],
        ];

        foreach ($tasks as $task) {
            $status = $task['status_code'] ?? '';
            if (isset($grouped[$status])) {
                $grouped[$status][] = $task;
            }
        }

        return $grouped;
    }

    /**
     * Фильтрация задач по роли пользователя
     *
     * @param array $tasks Массив задач
     * @param int $userId ID пользователя
     * @param int $roleId Роль (2 = все задачи, 3 = только assigned_to = userId)
     * @return array Отфильтрованный массив задач
     */
    public function filterTasksByRole(array $tasks, int $userId, int $roleId): array
    {
        // Manager видит все задачи
        if ($roleId === 2) {
            return $tasks;
        }

        // Executor видит только назначенные на него
        return array_values(array_filter($tasks, function ($task) use ($userId) {
            return (int) ($task['assigned_to'] ?? 0) === $userId;
        }));
    }
}
