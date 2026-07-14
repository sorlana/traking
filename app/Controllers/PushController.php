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
            // Показываем страницу с кнопкой подписки
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="font-family:system-ui;padding:20px;">';
            echo '<h3>Нет подписок (user_id=' . $userId . ')</h3>';
            echo '<p>Нажми кнопку чтобы подписаться:</p>';
            echo '<button onclick="doSubscribe()" style="padding:12px 24px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;">Подписаться на Push</button>';
            echo '<pre id="log" style="margin-top:20px;background:#f3f4f6;padding:12px;border-radius:8px;font-size:12px;overflow:auto;"></pre>';
            echo '<script>const BASE_URL="' . rtrim(url('/'), '/') . '";</script>';
            echo '<script>
function log(msg) { document.getElementById("log").textContent += msg + "\\n"; }

async function doSubscribe() {
    log("Starting...");
    
    if (!("serviceWorker" in navigator)) { log("ERROR: No serviceWorker support"); return; }
    if (!("PushManager" in window)) { log("ERROR: No PushManager support"); return; }
    
    log("Notification.permission: " + Notification.permission);
    
    if (Notification.permission === "default") {
        const perm = await Notification.requestPermission();
        log("Permission result: " + perm);
        if (perm !== "granted") { log("ERROR: Permission denied"); return; }
    } else if (Notification.permission === "denied") {
        log("ERROR: Notifications blocked. Reset in browser settings.");
        return;
    }
    
    log("Registering SW...");
    try {
        const reg = await navigator.serviceWorker.register(BASE_URL + "/service-worker.js", { scope: BASE_URL + "/" });
        log("SW registered: " + reg.scope);
        
        // Ждём активации
        await navigator.serviceWorker.ready;
        log("SW ready");
    } catch(e) {
        log("SW ERROR: " + e.message);
        return;
    }
    
    const registration = await navigator.serviceWorker.ready;
    
    log("Getting VAPID key...");
    const resp = await fetch(BASE_URL + "/push/vapid-key", { headers: {"X-Requested-With":"XMLHttpRequest"} });
    const { publicKey } = await resp.json();
    log("VAPID key: " + (publicKey ? publicKey.substring(0,20) + "..." : "EMPTY"));
    
    if (!publicKey) { log("ERROR: No VAPID key"); return; }
    
    log("Subscribing to push...");
    try {
        const applicationServerKey = urlBase64ToUint8Array(publicKey);
        const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey });
        const sub = subscription.toJSON();
        log("Push subscription OK: " + sub.endpoint.substring(0, 50) + "...");
        
        log("Sending to server...");
        const formData = new FormData();
        formData.append("endpoint", sub.endpoint);
        formData.append("p256dh", sub.keys.p256dh);
        formData.append("auth", sub.keys.auth);
        
        const saveResp = await fetch(BASE_URL + "/push/subscribe", { method: "POST", headers: {"X-Requested-With":"XMLHttpRequest"}, body: formData });
        const saveResult = await saveResp.json();
        log("Server response: " + JSON.stringify(saveResult));
        log("\\n✅ ГОТОВО! Перезагрузи эту страницу для теста push.");
    } catch(e) {
        log("SUBSCRIBE ERROR: " + e.message);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
}
</script>';
            echo '</body></html>';
            exit;
        }

        $pushService = new PushService();
        
        try {
            // Отправляем и собираем результат с HTTP-кодами
            $results = $pushService->sendToUserDebug($userId, 'Тест уведомления', 'Если видишь это — push работает!', url('/dashboard'));
            $this->json([
                'success' => true,
                'message' => 'Push отправлен',
                'subscriptions_count' => count($subscriptions),
                'endpoints' => array_map(fn($s) => substr($s['endpoint'], 0, 80) . '...', $subscriptions),
                'push_results' => $results,
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
