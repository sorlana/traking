<?php
/**
 * Project — Модель проекта
 *
 * Таблица: projects
 * Предоставляет методы для работы с проектом:
 * получение участников, задач, документов, статуса, статистики.
 */

namespace Models;

use Helpers\Database;

class Project extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'projects';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = ['title', 'description', 'deadline', 'estimated_hours', 'status_id', 'created_by', 'closed_at'];

    /**
     * Получить участников проекта
     *
     * @param int $projectId ID проекта
     * @param string|null $role Фильтр по роли в проекте (manager/executor)
     * @return array Массив участников с данными пользователя
     */
    public function getUsers(int $projectId, ?string $role = null): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.login, u.role_id, pu.project_role, pu.created_at as joined_at
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ?";
        $params = [$projectId];

        if ($role !== null) {
            $sql .= " AND pu.project_role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY pu.project_role ASC, u.name ASC";

        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * Получить задачи проекта
     *
     * @param int $projectId ID проекта
     * @param int|null $parentId Если null — корневые задачи (parent_id IS NULL)
     * @return array Массив задач
     */
    public function getTasks(int $projectId, ?int $parentId = null): array
    {
        $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code,
                       u.name as assigned_name
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?";
        $params = [$projectId];

        if ($parentId === null) {
            $sql .= " AND t.parent_id IS NULL";
        } else {
            $sql .= " AND t.parent_id = ?";
            $params[] = $parentId;
        }

        $sql .= " ORDER BY t.sort_order ASC, t.created_at DESC";

        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * Получить документы проекта
     *
     * @param int $projectId ID проекта
     * @return array Массив документов
     */
    public function getDocuments(int $projectId): array
    {
        $sql = "SELECT pd.*, u.name as uploader_name
                FROM project_documents pd
                JOIN users u ON pd.uploaded_by = u.id
                WHERE pd.project_id = ?
                ORDER BY pd.created_at DESC";

        return $this->db()->fetchAll($sql, [$projectId]);
    }

    /**
     * Получить статус проекта
     *
     * @param int $projectId ID проекта
     * @return array|null Данные статуса или null
     */
    public function getStatus(int $projectId): ?array
    {
        $sql = "SELECT ps.* FROM project_statuses ps
                JOIN projects p ON p.status_id = ps.id
                WHERE p.id = ?";

        return $this->db()->fetch($sql, [$projectId]);
    }

    /**
     * Проверить, является ли пользователь участником проекта
     *
     * @param int $projectId ID проекта
     * @param int $userId ID пользователя
     * @return bool
     */
    public function hasUser(int $projectId, int $userId): bool
    {
        $sql = "SELECT id FROM project_users WHERE project_id = ? AND user_id = ?";
        $result = $this->db()->fetch($sql, [$projectId, $userId]);

        return $result !== null;
    }

    /**
     * Получить проекты пользователя по его роли в проекте
     *
     * @param int $userId ID пользователя
     * @param int $roleId Роль пользователя в системе
     * @return array Массив проектов
     */
    public function getUserProjects(int $userId, int $roleId): array
    {
        $sql = "SELECT p.*, ps.name as status_name, ps.code as status_code, pu.project_role
                FROM projects p
                JOIN project_statuses ps ON p.status_id = ps.id
                JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                JOIN users creator ON creator.id = p.created_by
                WHERE creator.role_id <> ? OR p.created_by = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC";

        return $this->db()->fetchAll($sql, [$userId, \Helpers\Auth::ROLE_EXECUTOR, $userId]);
    }

    /**
     * Получить статистику задач проекта (количество по статусам)
     *
     * @param int $projectId ID проекта
     * @return array Массив ['total' => N, 'statuses' => [...], 'open' => N, 'closed' => N (завершённые)]
     */
    public function getTaskStats(int $projectId): array
    {
        $sql = "SELECT ts.code, ts.name, COUNT(t.id) as count
                FROM tasks t
                JOIN task_statuses ts ON t.status_id = ts.id
                WHERE t.project_id = ?
                GROUP BY ts.id, ts.code, ts.name
                ORDER BY ts.sort_order";

        $rows = $this->db()->fetchAll($sql, [$projectId]);

        $total = 0;
        $closed = 0;
        $statuses = [];

        foreach ($rows as $row) {
            $total += (int) $row['count'];
            if ($row['code'] === 'done') {
                $closed += (int) $row['count'];
            }
            $statuses[] = $row;
        }

        return [
            'total' => $total,
            'open' => $total - $closed,
            'closed' => $closed,
            'statuses' => $statuses,
        ];
    }
}
