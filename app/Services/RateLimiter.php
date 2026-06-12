<?php
/**
 * RateLimiter — Защита от brute force атак
 *
 * Простая реализация rate limiting на основе JSON-файла.
 * Подходит для shared-хостинга без Redis/Memcached.
 *
 * Хранение: storage/logs/rate_limits.json
 * Формат: { "ключ": { "attempts": [timestamp1, timestamp2, ...] } }
 *
 * По умолчанию: 5 попыток за 15 минут.
 *
 * Пример использования:
 *   $limiter = new RateLimiter();
 *   $key = 'login_' . $_SERVER['REMOTE_ADDR'];
 *   if ($limiter->tooManyAttempts($key)) {
 *       echo 'Подождите 15 минут';
 *   }
 *   $limiter->hit($key); // Зафиксировать попытку
 *   $limiter->clear($key); // Очистить после успешного входа
 */

namespace Services;

class RateLimiter
{
    /** @var int Максимальное количество попыток */
    private int $maxAttempts = 5;

    /** @var int Период блокировки в минутах */
    private int $decayMinutes = 15;

    /** @var string Путь к файлу хранения */
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = BASE_PATH . '/storage/logs/rate_limits.json';
    }

    /**
     * Проверить, превышен ли лимит попыток
     *
     * @param string $key Уникальный ключ (например: 'login_192.168.1.1')
     * @return bool true если превышен лимит
     */
    public function tooManyAttempts(string $key): bool
    {
        $data = $this->loadData();
        $attempts = $this->getRecentAttempts($data, $key);

        return count($attempts) >= $this->maxAttempts;
    }

    /**
     * Зафиксировать попытку
     *
     * @param string $key Уникальный ключ
     * @return void
     */
    public function hit(string $key): void
    {
        $data = $this->loadData();

        if (!isset($data[$key])) {
            $data[$key] = ['attempts' => []];
        }

        // Добавляем текущий timestamp
        $data[$key]['attempts'][] = time();

        // Очищаем старые записи (за пределами окна)
        $cutoff = time() - ($this->decayMinutes * 60);
        $data[$key]['attempts'] = array_values(
            array_filter($data[$key]['attempts'], fn($ts) => $ts > $cutoff)
        );

        $this->saveData($data);
    }

    /**
     * Очистить все попытки для ключа (после успешного входа)
     *
     * @param string $key Уникальный ключ
     * @return void
     */
    public function clear(string $key): void
    {
        $data = $this->loadData();
        unset($data[$key]);
        $this->saveData($data);
    }

    /**
     * Получить недавние попытки (в пределах окна блокировки)
     *
     * @param array $data Все данные из файла
     * @param string $key Уникальный ключ
     * @return array Массив timestamp-ов
     */
    private function getRecentAttempts(array $data, string $key): array
    {
        if (!isset($data[$key]['attempts'])) {
            return [];
        }

        $cutoff = time() - ($this->decayMinutes * 60);

        return array_filter(
            $data[$key]['attempts'],
            fn($ts) => $ts > $cutoff
        );
    }

    /**
     * Загрузить данные из JSON-файла
     *
     * @return array
     */
    private function loadData(): array
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $content = file_get_contents($this->storagePath);

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Сохранить данные в JSON-файл
     *
     * Использует блокировку файла для предотвращения race conditions.
     *
     * @param array $data Данные для сохранения
     * @return void
     */
    private function saveData(array $data): void
    {
        // Очищаем полностью просроченные ключи
        $cutoff = time() - ($this->decayMinutes * 60);
        foreach ($data as $key => $entry) {
            $recent = array_filter(
                $entry['attempts'] ?? [],
                fn($ts) => $ts > $cutoff
            );
            if (empty($recent)) {
                unset($data[$key]);
            } else {
                $data[$key]['attempts'] = array_values($recent);
            }
        }

        // Создаём директорию если не существует
        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Записываем с блокировкой файла
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->storagePath, $json, LOCK_EX);
    }
}
