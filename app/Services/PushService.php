<?php
/**
 * PushService — Сервис отправки Web Push уведомлений
 *
 * Реализует подписку/отписку пользователей и отправку push-уведомлений
 * участникам задачи при новых сообщениях в чате.
 * Использует Web Push Protocol (RFC 8030) с VAPID (RFC 8292).
 */

namespace Services;

use Helpers\Database;

class PushService
{
    private array $config;

    public function __construct()
    {
        $this->config = require BASE_PATH . '/config/push.php';
    }

    /**
     * Подписать пользователя на push
     */
    public function subscribe(int $userId, string $endpoint, string $p256dh, string $auth): void
    {
        $db = Database::getInstance();
        
        // Удаляем старую подписку с тем же endpoint (если переподписка)
        $db->delete('push_subscriptions', 'endpoint = ?', [$endpoint]);
        
        $db->insert('push_subscriptions', [
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'p256dh_key' => $p256dh,
            'auth_key' => $auth,
        ]);
    }

    /**
     * Отписать пользователя
     */
    public function unsubscribe(string $endpoint): void
    {
        $db = Database::getInstance();
        $db->delete('push_subscriptions', 'endpoint = ?', [$endpoint]);
    }

    /**
     * Отправить push всем подписчикам пользователя (кроме отправителя)
     * Перед отправкой проверяет настройки пользователя (push, DND, расписание)
     */
    public function sendToUser(int $userId, string $title, string $body, ?string $url = null): void
    {
        // Проверяем настройки пользователя
        if (!$this->canSendToUser($userId)) return;

        $db = Database::getInstance();
        $subscriptions = $db->fetchAll(
            "SELECT * FROM push_subscriptions WHERE user_id = ?",
            [$userId]
        );

        foreach ($subscriptions as $sub) {
            $this->sendPush($sub, $title, $body, $url);
        }
    }

    /**
     * Отправить push участникам задачи (кроме отправителя)
     * Обёрнуто в try-catch — если таблица не существует, просто пропускаем
     */
    public function sendToTaskParticipants(int $taskId, int $excludeUserId, string $title, string $body, ?string $url = null): void
    {
        try {
            $this->doSendToTaskParticipants($taskId, $excludeUserId, $title, $body, $url);
        } catch (\Throwable $e) {
            // Если таблица push_subscriptions не существует или другая ошибка — пропускаем
            error_log('PushService error: ' . $e->getMessage());
        }
    }

    private function doSendToTaskParticipants(int $taskId, int $excludeUserId, string $title, string $body, ?string $url = null): void
    {
        $db = Database::getInstance();
        
        // Получаем задачу
        $task = $db->fetch("SELECT assigned_to, created_by, project_id FROM tasks WHERE id = ?", [$taskId]);
        if (!$task) return;

        // Собираем получателей: assigned_to + created_by + все руководители проекта
        $recipients = [];
        if ($task['assigned_to'] && (int)$task['assigned_to'] !== $excludeUserId) {
            $recipients[] = (int)$task['assigned_to'];
        }
        if ($task['created_by'] && (int)$task['created_by'] !== $excludeUserId) {
            $recipients[] = (int)$task['created_by'];
        }
        
        // Руководители проекта
        $managers = $db->fetchAll(
            "SELECT user_id FROM project_users WHERE project_id = ? AND project_role = 'manager'",
            [(int)$task['project_id']]
        );
        foreach ($managers as $m) {
            if ((int)$m['user_id'] !== $excludeUserId) {
                $recipients[] = (int)$m['user_id'];
            }
        }

        $recipients = array_unique($recipients);

        // Отправляем push каждому получателю
        foreach ($recipients as $recipientId) {
            $this->sendToUser($recipientId, $title, $body, $url);
        }
    }

    /**
     * Проверить, можно ли отправить push-уведомление пользователю
     * Учитывает: push_enabled, dnd_enabled, schedule (время + дни)
     */
    private function canSendToUser(int $userId): bool
    {
        $db = Database::getInstance();

        try {
            $settings = $db->fetch("SELECT * FROM user_settings WHERE user_id = ?", [$userId]);
        } catch (\Throwable $e) {
            // Таблица может не существовать — разрешаем отправку
            return true;
        }

        if (!$settings) return true; // Нет настроек — отправляем по умолчанию

        // Push выключен
        if (!(int) $settings['push_enabled']) return false;

        // Режим "не беспокоить"
        if ((int) $settings['dnd_enabled']) return false;

        // Расписание
        if ((int) $settings['schedule_enabled']) {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Moscow'));
            $currentDay = (int) $now->format('N'); // 1=пн, 7=вс
            $currentTime = $now->format('H:i:s');

            $allowedDays = explode(',', $settings['schedule_days'] ?? '');
            if (!in_array((string) $currentDay, $allowedDays)) return false;

            if ($currentTime < $settings['schedule_start'] || $currentTime > $settings['schedule_end']) return false;
        }

        return true;
    }

    /**
     * Отправить один push-запрос
     * Использует Web Push Protocol (RFC 8030) с VAPID (RFC 8292)
     * и шифрование payload по RFC 8291 (ECDH + AES128GCM)
     */
    private function sendPush(array $subscription, string $title, string $body, ?string $url = null): void
    {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => '/favicon.svg',
        ], JSON_UNESCAPED_UNICODE);

        $endpoint = $subscription['endpoint'];
        $userPublicKey = $this->base64urlDecode($subscription['p256dh_key']);
        $userAuthToken = $this->base64urlDecode($subscription['auth_key']);

        // Шифруем payload по RFC 8291 (aes128gcm)
        $encrypted = $this->encryptPayload($payload, $userPublicKey, $userAuthToken);
        if (!$encrypted) {
            error_log('PushService: Failed to encrypt payload for endpoint: ' . $endpoint);
            return;
        }

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
        ];

        // Создаём JWT для VAPID авторизации
        $jwt = $this->createVapidJwt($endpoint);
        if ($jwt) {
            $headers[] = 'Authorization: vapid t=' . $jwt . ', k=' . $this->config['public_key'];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Логируем результат для отладки
        error_log("PushService: endpoint={$endpoint}, httpCode={$httpCode}, response={$response}, curlError={$curlError}");

        // Если подписка протухла (410 Gone) — удаляем
        if ($httpCode === 410 || $httpCode === 404) {
            $db = Database::getInstance();
            $db->delete('push_subscriptions', 'id = ?', [(int)$subscription['id']]);
        }
    }

    /**
     * Шифрование payload по RFC 8291 (aes128gcm content encoding)
     *
     * @param string $payload Текст для шифрования
     * @param string $userPublicKey Публичный ключ подписчика (65 байт, uncompressed P-256)
     * @param string $userAuthToken Auth secret подписчика (16 байт)
     * @return string|null Зашифрованный payload или null при ошибке
     */
    private function encryptPayload(string $payload, string $userPublicKey, string $userAuthToken): ?string
    {
        if (strlen($userPublicKey) !== 65 || strlen($userAuthToken) !== 16) {
            error_log('PushService: Invalid key lengths: pub=' . strlen($userPublicKey) . ' auth=' . strlen($userAuthToken));
            return null;
        }

        // Генерируем ephemeral ECDH key pair (P-256)
        $localKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        if (!$localKey) {
            error_log('PushService: Failed to generate EC key: ' . openssl_error_string());
            return null;
        }

        $localKeyDetails = openssl_pkey_get_details($localKey);
        $localPublicKey = $this->getUncompressedPublicKey($localKeyDetails);
        if (!$localPublicKey || strlen($localPublicKey) !== 65) {
            error_log('PushService: Invalid local public key length: ' . strlen($localPublicKey ?? ''));
            return null;
        }

        // ECDH: вычисляем shared secret
        $sharedSecret = $this->computeECDHSecret($localKey, $userPublicKey);
        if (!$sharedSecret) {
            error_log('PushService: ECDH failed: ' . openssl_error_string());
            return null;
        }

        // RFC 8291 key derivation
        // IKM = HKDF(auth_secret, ecdh_secret, "WebPush: info\0" || ua_public || as_public, 32)
        $ikm_info = "WebPush: info\0" . $userPublicKey . $localPublicKey;
        $prk_key = hash_hmac('sha256', $sharedSecret, $userAuthToken, true);
        $ikm = substr(hash_hmac('sha256', $ikm_info . "\x01", $prk_key, true), 0, 32);

        // Salt (random 16 bytes)
        $salt = random_bytes(16);

        // PRK for content encryption
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        // CEK: HKDF-Expand(PRK, "Content-Encoding: aes128gcm\0\1", 16)
        $cek_info = "Content-Encoding: aes128gcm\0\x01";
        $cek = substr(hash_hmac('sha256', $cek_info, $prk, true), 0, 16);

        // Nonce: HKDF-Expand(PRK, "Content-Encoding: nonce\0\1", 12)
        $nonce_info = "Content-Encoding: nonce\0\x01";
        $nonce = substr(hash_hmac('sha256', $nonce_info, $prk, true), 0, 12);

        // Padding delimiter + payload (RFC 8188: plaintext record = data + padding delimiter)
        $paddedPayload = $payload . "\x02";

        // AES-128-GCM шифрование
        $tag = '';
        $encrypted = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );
        if ($encrypted === false) {
            error_log('PushService: AES-GCM encrypt failed: ' . openssl_error_string());
            return null;
        }

        // aes128gcm header: salt(16) || rs(4) || idlen(1) || keyid(65)
        // followed by: ciphertext || tag
        $rs = pack('N', 4096);
        $idlen = chr(65);

        return $salt . $rs . $idlen . $localPublicKey . $encrypted . $tag;
    }

    /**
     * Получить uncompressed public key (65 bytes: 04 + x + y) из деталей ключа
     */
    private function getUncompressedPublicKey(array $keyDetails): ?string
    {
        if (!isset($keyDetails['ec']['x']) || !isset($keyDetails['ec']['y'])) return null;
        // x и y — бинарные строки, нужно дополнить до 32 байт каждый
        $x = $keyDetails['ec']['x'];
        $y = $keyDetails['ec']['y'];
        $x = str_pad($x, 32, "\0", STR_PAD_LEFT);
        $y = str_pad($y, 32, "\0", STR_PAD_LEFT);
        // Обрезаем до 32 если вдруг больше (leading zero byte)
        $x = substr($x, -32);
        $y = substr($y, -32);
        return "\x04" . $x . $y;
    }

    /**
     * Вычислить ECDH shared secret
     */
    private function computeECDHSecret($localPrivateKey, string $peerPublicKeyRaw): ?string
    {
        // Проверяем наличие функции
        if (!function_exists('openssl_pkey_derive')) {
            error_log('PushService: openssl_pkey_derive not available (PHP 7.3+ required)');
            return null;
        }

        // Создаём PEM из raw public key для openssl
        $peerPem = $this->rawPublicKeyToPem($peerPublicKeyRaw);
        if (!$peerPem) return null;

        $peerKey = openssl_pkey_get_public($peerPem);
        if (!$peerKey) {
            error_log('PushService: Failed to parse peer public key: ' . openssl_error_string());
            return null;
        }

        $shared = openssl_pkey_derive($localPrivateKey, $peerKey);
        if ($shared === false) {
            error_log('PushService: openssl_pkey_derive failed: ' . openssl_error_string());
            return null;
        }

        return $shared;
    }

    /**
     * Конвертировать raw uncompressed P-256 public key (65 bytes) в PEM
     */
    private function rawPublicKeyToPem(string $rawKey): ?string
    {
        if (strlen($rawKey) !== 65 || $rawKey[0] !== "\x04") return null;

        // DER-структура для EC public key (P-256, uncompressed)
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00"
             . $rawKey;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----";

        return $pem;
    }

    /**
     * HKDF (extract + expand, single-step)
     */
    private function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        return substr(hash_hmac('sha256', $info . "\1", $prk, true), 0, $length);
    }

    /**
     * Создать JWT токен для VAPID авторизации
     */
    private function createVapidJwt(string $endpoint): ?string
    {
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        $header = $this->base64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = $this->base64url(json_encode([
            'aud' => $audience,
            'exp' => time() + 86400,
            'sub' => $this->config['subject'],
        ]));

        $signingInput = $header . '.' . $payload;

        // Декодируем приватный ключ
        $privateKeyRaw = $this->base64urlDecode($this->config['private_key']);
        
        // Создаём EC key из raw bytes (32 байта приватный ключ P-256)
        $pem = $this->createEcPem($privateKeyRaw);
        if (!$pem) return null;

        $key = openssl_pkey_get_private($pem);
        if (!$key) return null;

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        // Конвертируем DER-подпись в raw R+S (64 байта)
        $rawSig = $this->derToRaw($signature);
        if (!$rawSig) return null;

        return $signingInput . '.' . $this->base64url($rawSig);
    }

    /**
     * Создать PEM-формат EC-ключа из raw-байтов приватного ключа P-256
     */
    private function createEcPem(string $privateKeyRaw): ?string
    {
        if (strlen($privateKeyRaw) !== 32) return null;

        // Формируем DER-структуру для EC private key (P-256)
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $privateKeyRaw
             . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";

        $pem = "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----";

        return $pem;
    }

    /**
     * Конвертировать DER-подпись в raw R+S формат (64 байта для P-256)
     */
    private function derToRaw(string $der): ?string
    {
        // Парсим DER SEQUENCE → извлекаем R и S (каждый 32 байта для P-256)
        $hex = bin2hex($der);
        if (substr($hex, 0, 2) !== '30') return null;

        $offset = 4; // skip SEQUENCE tag + length
        // R
        if (substr($hex, $offset, 2) !== '02') return null;
        $offset += 2;
        $rLen = hexdec(substr($hex, $offset, 2)) * 2;
        $offset += 2;
        $r = substr($hex, $offset, $rLen);
        $offset += $rLen;
        // S
        if (substr($hex, $offset, 2) !== '02') return null;
        $offset += 2;
        $sLen = hexdec(substr($hex, $offset, 2)) * 2;
        $offset += 2;
        $s = substr($hex, $offset, $sLen);

        // Pad/trim to 32 bytes each
        $r = str_pad(ltrim($r, '0'), 64, '0', STR_PAD_LEFT);
        $s = str_pad(ltrim($s, '0'), 64, '0', STR_PAD_LEFT);
        // Берём последние 64 hex-символа (32 байта)
        $r = substr($r, -64);
        $s = substr($s, -64);

        return hex2bin($r . $s);
    }

    /**
     * Base64url кодирование (без padding)
     */
    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64url декодирование
     */
    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
