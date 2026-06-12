<?php
/**
 * Model — Базовый класс модели (Active Record)
 *
 * Предоставляет базовые CRUD-операции для работы с таблицами БД.
 * Все модели наследуются от этого класса и указывают $table и $fillable.
 *
 * Пример использования:
 *   class User extends Model {
 *       protected string $table = 'users';
 *       protected string $primaryKey = 'id';
 *       protected array $fillable = ['login', 'email', 'password_hash', 'role_id'];
 *   }
 *
 *   $user = (new User())->find(1);
 *   $users = (new User())->findAll(['role_id' => 2], 'created_at DESC', 10);
 */

namespace Models;

use Helpers\Database;

class Model
{
    /** @var string Имя таблицы в БД */
    protected string $table = '';

    /** @var string Имя первичного ключа */
    protected string $primaryKey = 'id';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [];

    /**
     * Получить экземпляр Database
     *
     * @return Database
     */
    protected function db(): Database
    {
        return Database::getInstance();
    }

    /**
     * Найти запись по первичному ключу
     *
     * @param int|string $id Значение первичного ключа
     * @return array|null Ассоциативный массив записи или null
     */
    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return $this->db()->fetch($sql, [$id]);
    }

    /**
     * Найти все записи с условиями, сортировкой и лимитом
     *
     * @param array $conditions Условия [колонка => значение]
     * @param string|null $orderBy Сортировка (например: 'created_at DESC')
     * @param int|null $limit Ограничение количества записей
     * @return array Массив записей
     */
    public function findAll(array $conditions = [], ?string $orderBy = null, ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // Формируем условие WHERE
        if (!empty($conditions)) {
            $where = $this->buildWhere($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        // Сортировка
        if ($orderBy !== null) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Лимит
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db()->fetchAll($sql, $params);
    }

    /**
     * Создать новую запись в таблице
     *
     * @param array $data Данные для вставки [колонка => значение]
     * @return string ID созданной записи
     */
    public function create(array $data): string
    {
        // Фильтруем только разрешённые поля
        $filtered = $this->filterFillable($data);

        return $this->db()->insert($this->table, $filtered);
    }

    /**
     * Обновить запись по первичному ключу
     *
     * @param int|string $id Значение первичного ключа
     * @param array $data Данные для обновления [колонка => значение]
     * @return int Количество затронутых строк
     */
    public function update(int|string $id, array $data): int
    {
        // Фильтруем только разрешённые поля
        $filtered = $this->filterFillable($data);

        if (empty($filtered)) {
            return 0;
        }

        return $this->db()->update(
            $this->table,
            $filtered,
            "{$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Удалить запись по первичному ключу
     *
     * @param int|string $id Значение первичного ключа
     * @return int Количество удалённых строк
     */
    public function delete(int|string $id): int
    {
        return $this->db()->delete(
            $this->table,
            "{$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Найти записи по условиям (алиас для findAll без сортировки и лимита)
     *
     * @param array $conditions Условия [колонка => значение]
     * @return array Массив записей
     */
    public function where(array $conditions): array
    {
        return $this->findAll($conditions);
    }

    /**
     * Подсчитать количество записей с условиями
     *
     * @param array $conditions Условия [колонка => значение]
     * @return int Количество записей
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = $this->buildWhere($conditions, $params);
            $sql .= " WHERE {$where}";
        }

        $result = $this->db()->fetch($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    // ========================================================================
    // Приватные/защищённые методы
    // ========================================================================

    /**
     * Построить строку WHERE из массива условий
     *
     * Поддерживает простые условия (=) и NULL-проверки.
     *
     * @param array $conditions Условия [колонка => значение]
     * @param array &$params Массив параметров (пополняется)
     * @return string Строка условия (без ключевого слова WHERE)
     */
    protected function buildWhere(array $conditions, array &$params): string
    {
        $parts = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $parts[] = "{$column} IS NULL";
            } else {
                $parts[] = "{$column} = ?";
                $params[] = $value;
            }
        }

        return implode(' AND ', $parts);
    }

    /**
     * Фильтрует данные — оставляет только поля из $fillable
     *
     * @param array $data Исходные данные
     * @return array Отфильтрованные данные
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }
}
