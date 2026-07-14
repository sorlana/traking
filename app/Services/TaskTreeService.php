<?php
/**
 * TaskTreeService — Сервис дерева задач
 *
 * Обеспечивает работу с иерархической структурой задач:
 * построение дерева, проверка возможности закрытия,
 * определение глубины вложенности.
 *
 * Использует PHP-рекурсию (совместимость с MySQL 5.7+).
 */

namespace Services;

use Models\Task;
use Helpers\Database;

class TaskTreeService
{
    private Task $taskModel;

    public function __construct()
    {
        $this->taskModel = new Task();
    }

    /**
     * Получить рекурсивное дерево задач начиная с указанной задачи
     *
     * @param int $rootTaskId ID корневой задачи
     * @return array Дерево задач (задача + вложенные children)
     */
    public function getTree(int $rootTaskId): array
    {
        $task = $this->taskModel->find($rootTaskId);
        if (!$task) {
            return [];
        }

        // Дополняем данными о статусе
        $db = Database::getInstance();
        $taskFull = $db->fetch(
            "SELECT t.*, ts.name as status_name, ts.code as status_code,
                    u.name as assigned_name
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE t.id = ?",
            [$rootTaskId]
        );

        if (!$taskFull) {
            return [];
        }

        $taskFull['children'] = $this->loadChildren($rootTaskId);

        return $taskFull;
    }

    /**
     * Получить дерево всех задач проекта (корневые с вложенными)
     *
     * @param int $projectId ID проекта
     * @return array Массив корневых задач с вложенными children
     */
    public function getProjectTree(int $projectId): array
    {
        $db = Database::getInstance();

        // Получаем корневые задачи проекта (без parent_id)
        $rootTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, ts.code as status_code,
                    u.name as assigned_name
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE t.project_id = ? AND t.parent_id IS NULL
             ORDER BY t.created_at ASC",
            [$projectId]
        );

        // Для каждой корневой задачи загружаем дочерние
        foreach ($rootTasks as &$task) {
            $task['children'] = $this->loadChildren((int) $task['id']);
        }
        unset($task);

        return $rootTasks;
    }

    /**
     * Проверка: можно ли закрыть задачу + список блокирующих подзадач
     *
     * @param int $taskId ID задачи
     * @return array ['can' => bool, 'blocking' => array]
     */
    public function canClose(int $taskId): array
    {
        $db = Database::getInstance();

        // Получаем все незакрытые подзадачи (рекурсивно)
        $blocking = $this->getBlockingChildren($taskId);

        return [
            'can' => empty($blocking),
            'blocking' => $blocking,
        ];
    }

    /**
     * Получить глубину вложенности задачи
     *
     * @param int $taskId ID задачи
     * @return int Глубина (0 = корневая задача)
     */
    public function getDepth(int $taskId): int
    {
        $depth = 0;
        $currentId = $taskId;

        while (true) {
            $task = $this->taskModel->find($currentId);
            if (!$task || empty($task['parent_id'])) {
                break;
            }
            $depth++;
            $currentId = (int) $task['parent_id'];

            // Защита от бесконечных циклов
            if ($depth > 50) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Рекурсивная загрузка дочерних задач
     *
     * @param int $parentId ID родительской задачи
     * @return array Массив дочерних задач с вложенными children
     */
    public function getChildrenTree(int $parentId): array
    {
        return $this->loadChildren($parentId);
    }

    /**
     * Рекурсивная загрузка дочерних задач (внутренний метод)
     *
     * @param int $parentId ID родительской задачи
     * @return array Массив дочерних задач с вложенными children
     */
    private function loadChildren(int $parentId): array
    {
        $children = $this->taskModel->getChildren($parentId);

        foreach ($children as &$child) {
            $child['children'] = $this->loadChildren((int) $child['id']);
        }
        unset($child);

        return $children;
    }

    /**
     * Получить блокирующие подзадачи (незавершённые) рекурсивно
     *
     * @param int $parentId ID родительской задачи
     * @return array Массив блокирующих задач
     */
    private function getBlockingChildren(int $parentId): array
    {
        $db = Database::getInstance();

        $children = $db->fetchAll(
            "SELECT t.id, t.title, ts.name as status_name, ts.code as status_code
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             WHERE t.parent_id = ?
               AND ts.code NOT IN ('done')",
            [$parentId]
        );

        $blocking = $children;

        // Также проверяем вложенные подзадачи у каждого ребёнка
        $allChildren = $db->fetchAll(
            "SELECT t.id FROM tasks t WHERE t.parent_id = ?",
            [$parentId]
        );

        foreach ($allChildren as $child) {
            $nested = $this->getBlockingChildren((int) $child['id']);
            $blocking = array_merge($blocking, $nested);
        }

        return $blocking;
    }
}
