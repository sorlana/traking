<?php
/**
 * User — Модель пользователя
 *
 * Работает с таблицей users. Предоставляет методы для:
 * - Поиск по логину/email
 * - Проверка пароля
 * - Получение роли через JOIN
 * - Обновление last_login_at
 *
 * Пример использования:
 *   $userModel = new User();
 *   $user = $userModel->findByLogin('admin');
 *   if ($user && $userModel->verifyPassword('secret', $user['password_hash'])) { ... }
 */

namespace Models;

class User extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'users';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'name',
        'email',
        'login',
        'password_hash',
        'role_id',
        'status',
        'last_login_at',
    ];

    /**
     * Найти пользователя по логину ИЛИ email
     *
     * Используется при авторизации — пользователь может ввести
     * как свой логин, так и email.
     *
     * @param string $login Логин или email
     * @return array|null Данные пользователя или null
     */
    public function findByLogin(string $login): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE login = ? OR email = ? LIMIT 1";
        return $this->db()->fetch($sql, [$login, $login]);
    }

    /**
     * Найти пользователя по email
     *
     * @param string $email Email-адрес
     * @return array|null Данные пользователя или null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        return $this->db()->fetch($sql, [$email]);
    }

    /**
     * Получить роль пользователя через JOIN с таблицей roles
     *
     * @param int $userId ID пользователя
     * @return array|null Данные роли ['id', 'name', 'slug'] или null
     */
    public function getRole(int $userId): ?array
    {
        $sql = "SELECT r.* FROM roles r 
                JOIN {$this->table} u ON u.role_id = r.id 
                WHERE u.id = ? LIMIT 1";
        return $this->db()->fetch($sql, [$userId]);
    }

    /**
     * Проверить пароль пользователя
     *
     * @param string $password Введённый пароль (plain-text)
     * @param string $hash Хэш пароля из БД
     * @return bool true если пароль верный
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Проверить, активен ли пользователь
     *
     * @param array $user Данные пользователя
     * @return bool true если статус = 'active'
     */
    public function isActive(array $user): bool
    {
        return ($user['status'] ?? '') === 'active';
    }

    /**
     * Обновить время последнего входа
     *
     * @param int $userId ID пользователя
     * @return void
     */
    public function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = ?";
        $this->db()->query($sql, [$userId]);
    }
}
