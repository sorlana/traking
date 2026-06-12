<?php
/**
 * Database — Singleton PDO-подключение к базе данных
 *
 * Реализует паттерн Singleton для единственного экземпляра подключения.
 * Подключение создаётся лениво — при первом обращении к getConnection().
 * Использует настройки из config/database.php.
 *
 * Пример использования:
 *   $db = Database::getInstance();
 *   $users = $db->fetchAll("SELECT * FROM users WHERE role_id = ?", [1]);
 */

namespace Helpers;

class Database
{
    /** @var self|null Единственный экземпляр класса */
    private static ?self $instance = null;

    /** @var \PDO|null PDO-подключение (создаётся лениво) */
    private ?\PDO $pdo = null;

    /** @var array Конфигурация подключения */
    private array $config;

    /**
     * Приватный конструктор — запрещает создание экземпляров извне
     */
    private function __construct()
    {
        $this->config = require BASE_PATH . '/config/database.php';
    }

    /**
     * Запрет клонирования
     */
    private function __clone() {}

    /**
     * Запрет десериализации
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Десериализация Database запрещена');
    }

    /**
     * Получить единственный экземпляр Database (Singleton)
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Получить PDO-подключение (создаётся при первом вызове)
     *
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options'] ?? []
            );
        }

        return $this->pdo;
    }

    /**
     * Выполнить SQL-запрос с параметрами (INSERT, UPDATE, DELETE)
     *
     * @param string $sql SQL-запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Получить одну строку из результата запроса
     *
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return array|null Ассоциативный массив или null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Получить все строки из результата запроса
     *
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return array Массив ассоциативных массивов
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Вставить запись в таблицу
     *
     * @param string $table Имя таблицы
     * @param array $data Ассоциативный массив [колонка => значение]
     * @return string ID вставленной записи
     */
    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->lastInsertId();
    }

    /**
     * Обновить записи в таблице
     *
     * @param string $table Имя таблицы
     * @param array $data Ассоциативный массив [колонка => новое_значение]
     * @param string $where Условие WHERE (например: "id = ?")
     * @param array $whereParams Параметры для условия WHERE
     * @return int Количество затронутых строк
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Удалить записи из таблицы
     *
     * @param string $table Имя таблицы
     * @param string $where Условие WHERE (например: "id = ?")
     * @param array $whereParams Параметры для условия WHERE
     * @return int Количество удалённых строк
     */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Получить ID последней вставленной записи
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }
}
