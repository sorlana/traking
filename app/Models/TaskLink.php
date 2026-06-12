<?php
/**
 * TaskLink — Модель ссылок, прикреплённых к задачам
 *
 * Таблица: task_links
 * Хранит URL-ссылки с названиями, привязанные к задачам.
 */

namespace Models;

use Helpers\Database;

class TaskLink extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'task_links';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'task_id',
        'comment_id',
        'user_id',
        'url',
        'title',
    ];

    /**
     * Получить ссылки задачи с JOIN на users
     *
     * @param int $taskId ID задачи
     * @return array Массив ссылок с именами авторов
     */
    public function getByTask(int $taskId): array
    {
        $sql = "SELECT tl.*, u.name as user_name
                FROM task_links tl
                JOIN users u ON tl.user_id = u.id
                WHERE tl.task_id = ?
                ORDER BY tl.created_at DESC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }
}
