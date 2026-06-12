<?php
/**
 * ActivityLog — Модель журнала действий
 *
 * Хранит историю всех действий в системе: создание задач, смена статусов,
 * назначение исполнителей, комментарии, загрузка файлов и т.д.
 */

namespace Models;

use Helpers\Database;

class ActivityLog extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'activity_log';

    /** @var array Поля для массового заполнения */
    protected array $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'action_type',
        'old_value',
        'new_value',
    ];

    /**
     * Получить историю действий по задаче
     *
     * @param int $taskId ID задачи
     * @param int $limit Лимит записей
     * @return array Список действий
     */
    public function getByTask(int $taskId, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.task_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?";
        return $this->db()->fetchAll($sql, [$taskId, $limit]);
    }

    /**
     * Получить историю действий по проекту
     *
     * @param int $projectId ID проекта
     * @param int $limit Лимит записей
     * @return array Список действий
     */
    public function getByProject(int $projectId, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name, t.title as task_title
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tasks t ON al.task_id = t.id
                WHERE al.project_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?";
        return $this->db()->fetchAll($sql, [$projectId, $limit]);
    }

    /**
     * Получить все действия (для дашборда администратора)
     *
     * @param int $limit Лимит записей
     * @return array Список действий
     */
    public function getAll(int $limit = 100): array
    {
        $sql = "SELECT al.*, u.name as user_name, t.title as task_title, p.title as project_title
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tasks t ON al.task_id = t.id
                LEFT JOIN projects p ON al.project_id = p.id
                ORDER BY al.created_at DESC
                LIMIT ?";
        return $this->db()->fetchAll($sql, [$limit]);
    }
}
