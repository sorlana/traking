<?php
/**
 * AuthToken — Модель токенов авторизации
 *
 * Работает с таблицей auth_tokens. Управляет:
 * - Генерацией и хранением токенов (хэш SHA-256)
 * - Проверкой срока действия
 * - Очисткой просроченных токенов
 *
 * Схема работы:
 *   1. createToken() генерирует случайный токен (64 символа hex)
 *   2. В БД сохраняется SHA-256 хэш токена
 *   3. Plain-text токен отправляется клиенту в cookie
 *   4. При проверке: хэшируем токен из cookie и ищем в БД
 *
 * Пример использования:
 *   $authToken = new AuthToken();
 *   $plainToken = $authToken->createToken($userId, $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
 *   // $plainToken → в cookie
 */

namespace Models;

class AuthToken extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'auth_tokens';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'user_agent',
        'ip_address',
        'last_used_at',
    ];

    /**
     * Создать новый токен авторизации
     *
     * Генерирует криптографически безопасный токен, сохраняет его хэш в БД,
     * и возвращает plain-text токен для отправки клиенту в cookie.
     *
     * @param int $userId ID пользователя
     * @param string $userAgent User-Agent браузера
     * @param string $ip IP-адрес клиента
     * @return string Plain-text токен (64 символа hex) для cookie
     */
    public function createToken(int $userId, string $userAgent, string $ip): string
    {
        // Генерируем случайный токен — 32 байта → 64 hex-символа
        $plainToken = bin2hex(random_bytes(32));

        // Хэшируем для хранения в БД (не храним plain-text)
        $tokenHash = hash('sha256', $plainToken);

        // Срок действия — 3 месяца от текущего момента
        $expiresAt = date('Y-m-d H:i:s', time() + 7776000); // 90 дней

        // Сохраняем в БД
        $this->create([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'user_agent' => mb_substr($userAgent, 0, 512), // Обрезаем длинные UA
            'ip_address' => $ip,
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);

        return $plainToken;
    }

    /**
     * Найти токен по его хэшу
     *
     * @param string $tokenHash SHA-256 хэш токена
     * @return array|null Данные токена или null
     */
    public function findByTokenHash(string $tokenHash): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token_hash = ? LIMIT 1";
        return $this->db()->fetch($sql, [$tokenHash]);
    }

    /**
     * Проверить, истёк ли срок действия токена
     *
     * @param array $token Данные токена из БД
     * @return bool true если токен просрочен
     */
    public function isExpired(array $token): bool
    {
        $expiresAt = strtotime($token['expires_at'] ?? '');
        return $expiresAt === false || $expiresAt < time();
    }

    /**
     * Обновить время последнего использования токена
     *
     * @param int $tokenId ID записи токена
     * @return void
     */
    public function updateLastUsed(int $tokenId): void
    {
        $sql = "UPDATE {$this->table} SET last_used_at = NOW() WHERE id = ?";
        $this->db()->query($sql, [$tokenId]);
    }

    /**
     * Удалить все токены пользователя (при смене пароля, logout из всех устройств)
     *
     * @param int $userId ID пользователя
     * @return void
     */
    public function deleteByUserId(int $userId): void
    {
        $this->db()->delete($this->table, 'user_id = ?', [$userId]);
    }

    /**
     * Удалить все просроченные токены (очистка мусора)
     *
     * Рекомендуется вызывать через cron раз в сутки.
     *
     * @return void
     */
    public function deleteExpired(): void
    {
        $this->db()->delete($this->table, 'expires_at < NOW()', []);
    }
}
