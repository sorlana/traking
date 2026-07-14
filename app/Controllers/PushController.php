<?php
/**
 * PushController — Контроллер подписки на Web Push уведомления
 *
 * Обрабатывает: подписку, отписку, выдачу VAPID-ключа.
 */

namespace Controllers;

use Helpers\Auth;
use Services\PushService;

class PushController extends Controller
{
    /**
     * POST /push/subscribe — подписать текущего пользователя на push
     */
    public function subscribe(): void
    {
        $endpoint = $_POST['endpoint'] ?? '';
        $p256dh = $_POST['p256dh'] ?? '';
        $auth = $_POST['auth'] ?? '';

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            $this->json(['error' => 'Неполные данные подписки'], 422);
            return;
        }

        $pushService = new PushService();
        $pushService->subscribe(Auth::id(), $endpoint, $p256dh, $auth);

        $this->json(['success' => true]);
    }

    /**
     * POST /push/unsubscribe — отписать
     */
    public function unsubscribe(): void
    {
        $endpoint = $_POST['endpoint'] ?? '';

        if (empty($endpoint)) {
            $this->json(['error' => 'Не указан endpoint'], 422);
            return;
        }

        $pushService = new PushService();
        $pushService->unsubscribe($endpoint);

        $this->json(['success' => true]);
    }

    /**
     * GET /push/vapid-key — получить публичный VAPID-ключ
     */
    public function vapidKey(): void
    {
        $config = require BASE_PATH . '/config/push.php';
        $this->json(['publicKey' => $config['public_key']]);
    }

    /**
     * GET /push/test — отправить тестовый push текущему пользователю
     */
    public function test(): void
    {
        $userId = Auth::id();
        $db = \Helpers\Database::getInstance();
        
        // Проверяем подписки в БД
        $subscriptions = $db->fetchAll(
            "SELECT id, endpoint, created_at FROM push_subscriptions WHERE user_id = ?",
            [$userId]
        );
        
        if (empty($subscriptions)) {
            $this->json(['success' => false, 'error' => 'Нет подписок для текущего пользователя (user_id=' . $userId . '). Разреши уведомления в приложении.']);
            return;
        }

        $pushService = new PushService();
        
        try {
            $pushService->sendToUser($userId, 'Тест уведомления', 'Если видишь это — push работает!', url('/dashboard'));
            $this->json([
                'success' => true,
                'message' => 'Push отправлен',
                'subscriptions_count' => count($subscriptions),
                'endpoints' => array_map(fn($s) => substr($s['endpoint'], 0, 80) . '...', $subscriptions),
                'hint' => 'Проверь error_log на сервере для HTTP-кодов ответа от FCM',
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
