<?php
/**
 * Task — Модель задачи
 *
 * Таблица: tasks
 * Поддерживает дерево подзадач через parent_id (Adjacency List).
 * Предоставляет методы для получения связанных данных:
 * подзадачи, комментарии, файлы, ссылки, исполнитель.
 */

namespace Models;

use Helpers\Database;

class Task extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'tasks';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'project_id',
        'parent_id',
        'title',
        'description',
        'status_id',
        'priority',
        'deadline',
        'created_by',
        'assigned_to',
        'closed_at',
    ];

    /**
     * Получить родительскую задачу
     *
     * @param int $taskId ID задачи
     * @return array|null Данные родительской задачи или null
     */
    public function getParent(int $taskId): ?array
    {
        $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                WHERE t.id = (SELECT parent_id FROM tasks WHERE id = ? LIMIT 1)";

        return $this->db()->fetch($sql, [$taskId]);
    }

    /**
     * Получить дочерние задачи (подзадачи) с JOIN на статус и исполнителя
     *
     * @param int $taskId ID родительской задачи
     * @return array Массив дочерних задач
     */
    public function getChildren(int $taskId): array
    {
        $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code,
                       u.name as assigned_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.parent_id = ?
                ORDER BY t.created_at ASC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }

    /**
     * Получить комментарии задачи с JOIN на users
     *
     * @param int $taskId ID задачи
     * @return array Массив комментариев
     */
    public function getComments(int $taskId): array
    {
        $sql = "SELECT tc.*, u.name as user_name, u.login as user_login
                FROM task_comments tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.task_id = ?
                ORDER BY tc.created_at ASC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }

    /**
     * Получить файлы задачи
     *
     * @param int $taskId ID задачи
     * @return array Массив файлов
     */
    public function getFiles(int $taskId): array
    {
        $sql = "SELECT tf.*, u.name as uploader_name
                FROM task_files tf
                JOIN users u ON tf.uploaded_by = u.id
                WHERE tf.task_id = ?
                ORDER BY tf.created_at DESC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }

    /**
     * Получить ссылки задачи
     *
     * @param int $taskId ID задачи
     * @return array Массив ссылок
     */
    public function getLinks(int $taskId): array
    {
        $sql = "SELECT tl.*, u.name as user_name
                FROM task_links tl
                JOIN users u ON tl.user_id = u.id
                WHERE tl.task_id = ?
                ORDER BY tl.created_at DESC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }

    /**
     * Получить назначенного исполнителя задачи
     *
     * @param int $taskId ID задачи
     * @return array|null Данные исполнителя или null
     */
    public function getAssignee(int $taskId): ?array
    {
        $sql = "SELECT u.id, u.name, u.email, u.login, u.role_id
                FROM users u
                JOIN tasks t ON t.assigned_to = u.id
                WHERE t.id = ?";

        return $this->db()->fetch($sql, [$taskId]);
    }

    /**
     * Проверка: можно ли завершить задачу (нет открытых подзадач)
     *
     * Задача не может быть завершена, если есть дочерние задачи
     * со статусом, отличным от done.
     *
     * @param int $taskId ID задачи
     * @return bool true если задачу можно завершить
     */
    public function canBeClosed(int $taskId): bool
    {
        $sql = "SELECT COUNT(*) as cnt
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                WHERE t.parent_id = ?
                  AND ts.code != 'done'";

        $result = $this->db()->fetch($sql, [$taskId]);

        return (int) ($result['cnt'] ?? 0) === 0;
    }

    /**
     * Проверка просроченности задачи
     *
     * @param array $task Данные задачи (массив с полями deadline, status_code)
     * @return bool true если задача просрочена
     */
    public function isOverdue(array $task): bool
    {
        if (empty($task['deadline'])) {
            return false;
        }

        // Завершённые задачи не считаются просроченными
        $statusCode = $task['status_code'] ?? '';
        if ($statusCode === 'done') {
            return false;
        }

        return strtotime($task['deadline']) < strtotime(date('Y-m-d'));
    }

    /**
     * Получить задачи проекта с фильтрами
     *
     * @param int $projectId ID проекта
     * @param array $filters Фильтры: status, assigned_to, priority, deadline, overdue
     * @return array Массив задач
     */
    public function getByProject(int $projectId, array $filters = []): array
    {
        $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code,
                       u.name as assigned_name, creator.name as creator_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE t.project_id = ?";
        $params = [$projectId];

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $sql .= " AND ts.code = ?";
            $params[] = $filters['status'];
        }

        // Фильтр по исполнителю
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = (int) $filters['assigned_to'];
        }

        // Фильтр по приоритету
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Фильтр по сроку
        if (!empty($filters['deadline'])) {
            if ($filters['deadline'] === 'overdue') {
                $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
            } elseif ($filters['deadline'] === 'week') {
                $sql .= " AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($filters['deadline'] === 'today') {
                $sql .= " AND t.deadline = CURDATE()";
            }
        }

        // Фильтр: только просроченные
        if (!empty($filters['overdue'])) {
            $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
        }

        $sql .= " ORDER BY t.created_at DESC";

        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * Получить задачи пользователя (назначенные на него)
     *
     * @param int $userId ID пользователя
     * @param array $filters Фильтры: status, priority, deadline, project_id, overdue
     * @return array Массив задач
     */
    public function getUserTasks(int $userId, array $filters = []): array
    {
        $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code,
                       u.name as assigned_name, p.title as project_title,
                       creator.name as creator_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                JOIN projects p ON t.project_id = p.id
                WHERE t.assigned_to = ?";
        $params = [$userId];

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $sql .= " AND ts.code = ?";
            $params[] = $filters['status'];
        }

        // Фильтр по приоритету
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Фильтр по проекту
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = ?";
            $params[] = (int) $filters['project_id'];
        }

        // Фильтр по сроку
        if (!empty($filters['deadline'])) {
            if ($filters['deadline'] === 'overdue') {
                $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
            } elseif ($filters['deadline'] === 'week') {
                $sql .= " AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($filters['deadline'] === 'today') {
                $sql .= " AND t.deadline = CURDATE()";
            }
        }

        // Фильтр: только просроченные
        if (!empty($filters['overdue'])) {
            $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
        }

        $sql .= " ORDER BY t.created_at DESC";

        return $this->db()->fetchAll($sql, $params);
    }
}
