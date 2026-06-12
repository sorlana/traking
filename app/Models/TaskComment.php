<?php
/**
 * TaskComment — Модель комментариев к задачам
 *
 * Таблица: task_comments
 * Позволяет создавать, получать и проверять доступ к комментариям.
 * Поддерживает вложенные комментарии через parent_comment_id.
 */

namespace Models;

use Helpers\Database;
use Helpers\Auth;

class TaskComment extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'task_comments';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'task_id',
        'user_id',
        'comment_text',
        'parent_comment_id',
    ];

    /**
     * Получить комментарии задачи с JOIN на users
     *
     * @param int $taskId ID задачи
     * @return array Массив комментариев с именами авторов
     */
    public function getByTask(int $taskId): array
    {
        $sql = "SELECT tc.*, u.name as user_name, u.login as user_login
                FROM task_comments tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.task_id = ?
                ORDER BY tc.created_at ASC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }

    /**
     * Проверка: может ли пользователь видеть комментарий
     *
     * Видимость определяется через доступ к задаче (TaskAccessMiddleware).
     *
     * @param int $commentId ID комментария
     * @param int $userId ID пользователя
     * @param int $roleId ID роли пользователя
     * @return bool true если пользователь может видеть комментарий
     */
    public function canUserView(int $commentId, int $userId, int $roleId): bool
    {
        // Администратор видит все комментарии
        if ($roleId === 1) {
            return true;
        }

        // Получаем комментарий и связанную задачу
        $comment = $this->find($commentId);
        if ($comment === null) {
            return false;
        }

        // Проверяем доступ к задаче через TaskAccessMiddleware
        return \Middleware\TaskAccessMiddleware::check((int) $comment['task_id']);
    }
}
