<?php
/**
 * ProjectAccessMiddleware — Проверка доступа к проекту
 *
 * Этот middleware НЕ подключается через роутер (нет доступа к ID в конструкторе).
 * Вместо этого предоставляет статический метод для вызова из контроллера.
 *
 * Пример использования в контроллере:
 *   if (!ProjectAccessMiddleware::check($projectId)) {
 *       Response::forbidden('Нет доступа к проекту');
 *   }
 */

namespace Middleware;

use Helpers\Auth;
use Helpers\Database;

class ProjectAccessMiddleware
{
    /**
     * Проверить, что текущий пользователь подключён к проекту
     *
     * Логика:
     *   - Admin (role_id=1) видит все проекты
     *   - Остальные — только если есть запись в project_users
     *
     * @param int $projectId ID проекта
     * @return bool true если доступ разрешён
     */
    public static function check(int $projectId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);

        // Admin видит всё
        if ($roleId === 1) {
            return true;
        }

        // Проверяем наличие в project_users
        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT id FROM project_users WHERE project_id = ? AND user_id = ?",
            [$projectId, (int) $user['id']]
        );

        return $result !== null;
    }
}
