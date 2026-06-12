<?php
/**
 * Notification — Модель уведомлений
 *
 * Уведомления о назначении задач, комментариях, смене статусов и т.д.
 * Каждое уведомление привязано к пользователю и может ссылаться на задачу/проект/комментарий.
 */

namespace Models;

use Helpers\Database;

class Notification extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'notifications';

    /** @var array Поля для массового заполнения */
    protected array $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'project_id',
        'task_id',
        'comment_id',
        'is_read',
        'read_at',
    ];

    /**
     * Получить уведомления пользователя
     *
     * @param int $userId ID пользователя
     * @param bool $onlyUnread Только непрочитанные
     * @param int $limit Лимит записей
     * @return array Список уведомлений
     */
    public function getByUser(int $userId, bool $onlyUnread = false, int $limit = 50): array
    {
        $sql = "SELECT n.*, t.title as task_title, p.title as project_title
                FROM {$this->table} n
                LEFT JOIN tasks t ON n.task_id = t.id
                LEFT JOIN projects p ON n.project_id = p.id
                WHERE n.user_id = ?";
        $params = [$userId];

        if ($onlyUnread) {
            $sql .= " AND n.is_read = 0";
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * Подсчитать количество непрочитанных уведомлений
     *
     * @param int $userId ID пользователя
     * @return int Количество непрочитанных
     */
    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND is_read = 0";
        $result = $this->db()->fetch($sql, [$userId]);
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Пометить уведомление как прочитанное
     *
     * @param int $id ID уведомления
     * @return int Количество обновлённых записей
     */
    public function markRead(int $id): int
    {
        return $this->db()->update(
            $this->table,
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            "id = ?",
            [$id]
        );
    }

    /**
     * Пометить все уведомления пользователя как прочитанные
     *
     * @param int $userId ID пользователя
     * @return int Количество обновлённых записей
     */
    public function markAllRead(int $userId): int
    {
        return $this->db()->update(
            $this->table,
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            "user_id = ? AND is_read = 0",
            [$userId]
        );
    }
}
