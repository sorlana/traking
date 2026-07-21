<?php
/**
 * TaskAccessMiddleware — Проверка доступа к задаче
 *
 * Этот middleware НЕ подключается через роутер (нет доступа к ID в конструкторе).
 * Вместо этого предоставляет статический метод для вызова из контроллера.
 *
 * Пример использования в контроллере:
 *   if (!TaskAccessMiddleware::check($taskId)) {
 *       Response::forbidden('Нет доступа к задаче');
 *   }
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Database;

class TaskAccessMiddleware
{
    /**
     * Проверить доступ к задаче
     *
     * Логика:
     *   - Admin (role_id=1) видит все задачи
     *   - Manager (role_id=2) видит задачи проектов, к которым подключён
     *   - Executor (role_id=3) видит все задачи собственного приватного проекта
     *   - В обычных проектах Executor видит назначенные задачи
     *     (assigned_to или task_participants)
     *
     * @param int $taskId ID задачи
     * @return bool true если доступ разрешён
     */
    public static function check(int $taskId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $userId = (int) $user['id'];

        // Admin сохраняет системный доступ.
        if ($roleId === 1) {
            return true;
        }

        $db = Database::getInstance();

        // Получаем задачу вместе с владельцем проекта.
        $task = $db->fetch(
            "SELECT t.*, p.created_by AS project_created_by,
                    creator.role_id AS project_creator_role_id
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             JOIN users creator ON creator.id = p.created_by
             WHERE t.id = ?",
            [$taskId]
        );
        if ($task === null) {
            return false;
        }

        // В приватном проекте исполнителя все задачи доступны только владельцу.
        if ((int) $task['project_creator_role_id'] === Auth::ROLE_EXECUTOR) {
            return $roleId === Auth::ROLE_EXECUTOR
                && (int) $task['project_created_by'] === $userId;
        }

        // Manager — проверяем что он подключён к проекту задачи
        if ($roleId === 2) {
            return ProjectAccessMiddleware::check((int) $task['project_id']);
        }

        // Executor — видит только если назначен на задачу
        if ($roleId === 3) {
            // Назначен как assigned_to
            if ((int) ($task['assigned_to'] ?? 0) === $userId) {
                return true;
            }

            // Или есть в task_participants
            $participant = $db->fetch(
                "SELECT id FROM task_participants WHERE task_id = ? AND user_id = ?",
                [$taskId, $userId]
            );
            return $participant !== null;
        }

        return false;
    }
}
