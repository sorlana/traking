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
     * @return array Структура: ['projects' => [...], 'boardData' => [...], 'timeData' => [...]]
     */
    public function getBoardData(int $userId, int $roleId): array
    {
        $projectModel = new Project();
        $projects = $projectModel->getUserProjects($userId, $roleId);

        $boardData = [];
        $timeData = [];
        $db = Database::getInstance();

        foreach ($projects as $project) {
            $boardData[$project['id']] = $this->getProjectBoardTasks(
                (int) $project['id'], $userId, $roleId
            );

            // Расчёт фактического времени по проекту (сумма time_spent всех задач)
            $actualTime = $db->fetch(
                "SELECT COALESCE(SUM(time_spent), 0) as total FROM tasks WHERE project_id = ?",
                [(int) $project['id']]
            );

            // Время руководителя по проекту
            $managerTime = $db->fetch(
                "SELECT COALESCE(SUM(manager_time_spent), 0) as total FROM tasks WHERE project_id = ?",
                [(int) $project['id']]
            );

            $timeData[$project['id']] = [
                'estimated' => (float) ($project['estimated_hours'] ?? 0),
                'actual' => (float) ($actualTime['total'] ?? 0),
                'manager' => (float) ($managerTime['total'] ?? 0),
            ];
        }

        return [
            'projects' => $projects,
            'boardData' => $boardData,
            'timeData' => $timeData,
        ];
    }

    /**
     * Получить задачи проекта для доски, сгруппированные по статусам
     *
     * @param int $projectId ID проекта
     * @param int $userId ID пользователя
     * @param int $roleId Роль (2 или 3)
     * @return array ['in_progress' => [...], 'revision' => [...], 'done' => [...], 'closed' => [...]]
     */
    public function getProjectBoardTasks(int $projectId, int $userId, int $roleId): array
    {
        $db = Database::getInstance();

        $sql = "SELECT t.id, t.title, t.priority, t.deadline, t.assigned_to,
                       t.parent_id, t.sort_order,
                       ts.code AS status_code, ts.name AS status_name,
                       u.name AS assigned_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                  AND ts.code IN ('in_progress', 'revision', 'done', 'closed')";
        $params = [$projectId];

        // В обычных проектах Executor видит назначенные задачи.
        // В собственном приватном проекте — все задачи проекта.
        if ($roleId === 3) {
            $sql .= " AND (
                t.assigned_to = ?
                OR EXISTS (
                    SELECT 1
                    FROM projects owner_project
                    JOIN users project_creator ON project_creator.id = owner_project.created_by
                    WHERE owner_project.id = t.project_id
                      AND owner_project.created_by = ?
                      AND project_creator.role_id = ?
                )
            )";
            $params[] = $userId;
            $params[] = $userId;
            $params[] = \Helpers\Auth::ROLE_EXECUTOR;
        }

        $sql .= " ORDER BY t.sort_order ASC, t.created_at DESC";

        $tasks = $db->fetchAll($sql, $params);

        return $this->groupTasksByStatus($tasks);
    }

    /**
     * Построение дерева задач и группировка корневых веток по статусу.
     *
     * Дочерние задачи остаются внутри родительской ветки даже при другом статусе.
     * Если родитель недоступен из-за ролевого фильтра, задача становится корневой.
     *
     * @param array $tasks Плоский массив задач
     * @return array ['in_progress' => [...], 'revision' => [...], 'done' => [...], 'closed' => [...]]
     */
    public function groupTasksByStatus(array $tasks): array
    {
        $grouped = [
            'in_progress' => [],
            'revision' => [],
            'done' => [],
            'closed' => [],
        ];

        $tasksById = [];
        $childrenByParent = [];
        $rootIds = [];

        foreach ($tasks as $task) {
            $tasksById[(int) $task['id']] = $task;
        }

        foreach ($tasks as $task) {
            $taskId = (int) $task['id'];
            $parentId = !empty($task['parent_id']) ? (int) $task['parent_id'] : null;

            if ($parentId !== null && $parentId !== $taskId && isset($tasksById[$parentId])) {
                $childrenByParent[$parentId][] = $taskId;
            } else {
                $rootIds[] = $taskId;
            }
        }

        $visited = [];
        $buildBranch = function (int $taskId) use (&$buildBranch, &$visited, $tasksById, $childrenByParent): ?array {
            if (isset($visited[$taskId]) || !isset($tasksById[$taskId])) {
                return null;
            }

            $visited[$taskId] = true;
            $task = $tasksById[$taskId];
            $task['children'] = [];

            foreach ($childrenByParent[$taskId] ?? [] as $childId) {
                $child = $buildBranch($childId);
                if ($child !== null) {
                    $task['children'][] = $child;
                }
            }

            return $task;
        };

        foreach ($rootIds as $rootId) {
            $root = $buildBranch($rootId);
            $status = $root['status_code'] ?? '';
            if ($root !== null && isset($grouped[$status])) {
                $grouped[$status][] = $root;
            }
        }

        // Защита от некорректных циклических связей: задача не должна исчезать с доски.
        foreach (array_keys($tasksById) as $taskId) {
            if (isset($visited[$taskId])) {
                continue;
            }
            $root = $buildBranch($taskId);
            $status = $root['status_code'] ?? '';
            if ($root !== null && isset($grouped[$status])) {
                $grouped[$status][] = $root;
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
