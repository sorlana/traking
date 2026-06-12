<?php
/**
 * ProjectUser — Модель участников проекта
 *
 * Таблица: project_users (связь many-to-many между проектами и пользователями)
 * Предоставляет методы для добавления/удаления участников.
 */

namespace Models;

use Helpers\Database;

class ProjectUser extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'project_users';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = ['project_id', 'user_id', 'project_role'];

    /**
     * Добавить пользователя в проект
     *
     * @param int $projectId ID проекта
     * @param int $userId ID пользователя
     * @param string $role Роль в проекте (manager/executor)
     * @return string ID созданной записи
     */
    public function addUser(int $projectId, int $userId, string $role): string
    {
        return $this->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'project_role' => $role,
        ]);
    }

    /**
     * Удалить пользователя из проекта
     *
     * @param int $projectId ID проекта
     * @param int $userId ID пользователя
     * @return int Количество удалённых строк
     */
    public function removeUser(int $projectId, int $userId): int
    {
        return $this->db()->delete(
            $this->table,
            "project_id = ? AND user_id = ?",
            [$projectId, $userId]
        );
    }

    /**
     * Получить всех участников проекта
     *
     * @param int $projectId ID проекта
     * @return array Массив участников с данными пользователя
     */
    public function getByProject(int $projectId): array
    {
        $sql = "SELECT pu.*, u.name, u.email, u.login, u.role_id
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ?
                ORDER BY pu.project_role ASC, u.name ASC";

        return $this->db()->fetchAll($sql, [$projectId]);
    }
}
