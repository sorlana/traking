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
}
