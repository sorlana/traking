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
     *   - Проект исполнителя видит только создавший его исполнитель
     *   - В остальных проектах нужна запись в project_users
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

        // Admin сохраняет системный доступ.
        if ($roleId === 1) {
            return true;
        }

        $db = Database::getInstance();
        $project = $db->fetch(
            "SELECT p.created_by, creator.role_id AS creator_role_id
             FROM projects p
             JOIN users creator ON creator.id = p.created_by
             WHERE p.id = ?",
            [$projectId]
        );

        if ($project === null) {
            return false;
        }

        // Проекты, созданные исполнителем, приватны для их создателя.
        if ((int) $project['creator_role_id'] === Auth::ROLE_EXECUTOR) {
            return $roleId === Auth::ROLE_EXECUTOR
                && (int) $project['created_by'] === (int) $user['id'];
        }

        // Проверяем наличие в project_users для обычного проекта.
        $result = $db->fetch(
            "SELECT id FROM project_users WHERE project_id = ? AND user_id = ?",
            [$projectId, (int) $user['id']]
        );

        return $result !== null;
    }

    /**
     * Проект создан исполнителем и является его приватным проектом.
     */
    public static function isExecutorOwnedProject(int $projectId): bool
    {
        return self::executorOwnerId($projectId) !== null;
    }

    /**
     * ID исполнителя-владельца приватного проекта.
     */
    public static function executorOwnerId(int $projectId): ?int
    {
        $result = Database::getInstance()->fetch(
            "SELECT p.created_by
             FROM projects p
             JOIN users creator ON creator.id = p.created_by
             WHERE p.id = ? AND creator.role_id = ?",
            [$projectId, Auth::ROLE_EXECUTOR]
        );

        return $result !== null ? (int) $result['created_by'] : null;
    }

    /**
     * Право управлять проектом: admin, руководитель обычного доступного проекта
     * или исполнитель-создатель собственного приватного проекта.
     */
    public static function canManage(int $projectId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId === Auth::ROLE_ADMIN) {
            return true;
        }

        $db = Database::getInstance();
        $project = $db->fetch(
            "SELECT p.created_by, creator.role_id AS creator_role_id
             FROM projects p
             JOIN users creator ON creator.id = p.created_by
             WHERE p.id = ?",
            [$projectId]
        );

        if ($project === null) {
            return false;
        }

        if ((int) $project['creator_role_id'] === Auth::ROLE_EXECUTOR) {
            return $roleId === Auth::ROLE_EXECUTOR
                && (int) $project['created_by'] === (int) $user['id'];
        }

        return $roleId === Auth::ROLE_MANAGER && self::check($projectId);
    }
}
