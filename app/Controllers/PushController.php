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
    log("=== Диагностика ===");
    log("navigator.serviceWorker: " + ("serviceWorker" in navigator));
    log("window.PushManager: " + ("PushManager" in window));
    log("window.Notification: " + ("Notification" in window));
    log("display-mode standalone: " + window.matchMedia("(display-mode: standalone)").matches);
    log("navigator.standalone (iOS): " + (navigator.standalone === true));
    log("User-Agent: " + navigator.userAgent.substring(0, 80));
    log("==================");
    log("");
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
            // Диагностика VAPID JWT
            $jwtDebug = $pushService->debugVapidJwt($subscriptions[0]['endpoint']);
            
            // Отправляем и собираем результат с HTTP-кодами
            $results = $pushService->sendToUserDebug($userId, 'Тест уведомления', 'Если видишь это — push работает!', url('/dashboard'));
            $this->json([
                'success' => true,
                'message' => 'Push отправлен',
                'subscriptions_count' => count($subscriptions),
                'vapid_debug' => $jwtDebug,
                'push_results' => $results,
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * GET /push/test-raw — отправить push БЕЗ payload (пустой) для диагностики
     * Если пустой push приходит, а зашифрованный нет — проблема в шифровании
     */
    public function testRaw(): void
    {
        $userId = Auth::id();
        $db = \Helpers\Database::getInstance();
        $config = require BASE_PATH . '/config/push.php';
        
        $subscriptions = $db->fetchAll(
            "SELECT * FROM push_subscriptions WHERE user_id = ?",
            [$userId]
        );
        
        if (empty($subscriptions)) {
            $this->json(['error' => 'Нет подписок']);
            return;
        }

        $sub = $subscriptions[0];
        $endpoint = $sub['endpoint'];
        
        // Создаём VAPID JWT
        $pushService = new \Services\PushService();
        
        // Отправляем пустой push (без payload, без шифрования)
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        $headers = [
            'TTL: 60',
            'Content-Length: 0',
        ];
        
        // VAPID auth
        $jwt = $pushService->debugVapidJwt($endpoint);
        if ($jwt['jwt_created']) {
            // Нужен сам JWT строкой — пересоздадим
            $jwtToken = $pushService->getVapidJwtString($endpoint);
            if ($jwtToken) {
                $headers[] = 'Authorization: vapid t=' . $jwtToken . ', k=' . $config['public_key'];
            }
        }
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->json([
            'test' => 'empty push (no payload)',
            'httpCode' => $httpCode,
            'response' => substr($response, 0, 200),
            'curlError' => $curlError,
            'endpoint' => substr($endpoint, 0, 80),
            'note' => 'Если httpCode=201 и уведомление пришло — проблема в шифровании. Если не пришло — проблема в SW на устройстве.',
        ]);
    }

    /**
     * GET /push/test-task/{id} — симулирует отправку push как при сообщении в задаче
     */
    public function testTask(string $id): void
    {
        $taskId = (int) $id;
        $userId = Auth::id();
        $db = \Helpers\Database::getInstance();

        $task = $db->fetch("SELECT assigned_to, created_by, project_id, title FROM tasks WHERE id = ?", [$taskId]);
        if (!$task) {
            $this->json(['error' => 'Задача не найдена']);
            return;
        }

        // Собираем получателей (как в doSendToTaskParticipants)
        $recipients = [];
        if ($task['assigned_to'] && (int)$task['assigned_to'] !== $userId) {
            $recipients[] = (int)$task['assigned_to'];
        }
        if ($task['created_by'] && (int)$task['created_by'] !== $userId) {
            $recipients[] = (int)$task['created_by'];
        }
        $managers = $db->fetchAll(
            "SELECT user_id FROM project_users WHERE project_id = ? AND project_role = 'manager'",
            [(int)$task['project_id']]
        );
        foreach ($managers as $m) {
            if ((int)$m['user_id'] !== $userId) {
                $recipients[] = (int)$m['user_id'];
            }
        }
        $recipients = array_unique($recipients);

        // Проверяем canSend для каждого
        $pushService = new PushService();
        $results = [];
        foreach ($recipients as $recipientId) {
            $settings = $db->fetch("SELECT push_enabled, dnd_enabled, schedule_enabled FROM user_settings WHERE user_id = ?", [$recipientId]);
            $subs = $db->fetchAll("SELECT id, endpoint FROM push_subscriptions WHERE user_id = ?", [$recipientId]);
            $results[] = [
                'user_id' => $recipientId,
                'settings' => $settings,
                'subscriptions' => count($subs),
                'endpoints' => array_map(fn($s) => substr($s['endpoint'], 0, 50), $subs),
            ];
        }

        $this->json([
            'task_id' => $taskId,
            'sender_id' => $userId,
            'task_title' => $task['title'],
            'recipients' => $recipients,
            'details' => $results,
        ]);
    }

    /**
     * GET /push/diag — всегда показывает диагностику устройства (без проверки подписок)
     */
    public function diag(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $baseUrl = rtrim(url('/'), '/');
        echo <<<HTML
<!DOCTYPE html>
<html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Push Диагностика</title></head>
<body style="font-family:system-ui;padding:20px;max-width:600px;margin:0 auto;">
<h2>Диагностика Push</h2>
<button onclick="runDiag()" style="padding:12px 24px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;width:100%;">Проверить и подписаться</button>
<button onclick="resetAndSubscribe()" style="padding:12px 24px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;width:100%;margin-top:8px;">Сбросить и подписаться заново</button>
<pre id="log" style="margin-top:16px;background:#f3f4f6;padding:12px;border-radius:8px;font-size:11px;overflow:auto;white-space:pre-wrap;min-height:200px;"></pre>
<script>
const BASE_URL = "{$baseUrl}";
function log(msg) { document.getElementById("log").textContent += msg + "\\n"; }

async function runDiag() {
    document.getElementById("log").textContent = "";
    log("=== Диагностика устройства ===");
    log("time: " + new Date().toISOString());
    log("href: " + location.href);
    log("origin: " + location.origin);
    log("protocol: " + location.protocol);
    log("isSecureContext: " + window.isSecureContext);
    log("top-level: " + (window.top === window.self));
    log("referrer: " + (document.referrer || "(empty)"));
    log("serviceWorker: " + ("serviceWorker" in navigator));
    log("PushManager: " + ("PushManager" in window));
    log("Notification: " + ("Notification" in window));
    log("Notification.permission: " + (("Notification" in window) ? Notification.permission : "n/a"));
    log("standalone: " + window.matchMedia("(display-mode: standalone)").matches);
    log("iOS standalone: " + (navigator.standalone === true));
    log("platform: " + navigator.platform);
    log("maxTouchPoints: " + navigator.maxTouchPoints);
    log("online: " + navigator.onLine);
    log("cookies: " + navigator.cookieEnabled);
    log("UA: " + navigator.userAgent);
    log("");

    try {
        localStorage.setItem("push_diag", "ok");
        log("localStorage: " + (localStorage.getItem("push_diag") === "ok"));
        localStorage.removeItem("push_diag");
    } catch (e) {
        log("localStorage: false (" + e.name + ": " + e.message + ")");
    }

    await inspectResource(BASE_URL + "/manifest.json", "manifest");
    await inspectResource(BASE_URL + "/service-worker.js", "service-worker file");
    log("");

    if (!window.isSecureContext) {
        log("❌ Страница не является secure context — Service Worker намеренно скрыт браузером");
        return;
    }
    if (!("serviceWorker" in navigator)) {
        log("❌ HTTPS и файлы доступны, но WebKit скрыл Service Worker API");
        log("Причина находится на устройстве: WebView/MDM/Lockdown/системная политика WebKit");
        return;
    }
    if (!("PushManager" in window)) { log("❌ PushManager недоступен — push невозможен на этом устройстве/браузере"); return; }

    log("✓ API доступны. Регистрирую SW...");
    try {
        const reg = await navigator.serviceWorker.register(BASE_URL + "/service-worker.js", { scope: BASE_URL + "/" });
        log("✓ SW зарегистрирован: " + reg.scope);
        log("  active: " + !!reg.active);
        log("  waiting: " + !!reg.waiting);
        log("  installing: " + !!reg.installing);
    } catch(e) {
        log("❌ SW register error: " + e.message);
        return;
    }

    log("Жду активации SW...");
    try {
        const registration = await Promise.race([
            navigator.serviceWorker.ready,
            new Promise((_, r) => setTimeout(() => r(new Error("timeout 10s")), 10000))
        ]);
        log("✓ SW активен");

        if ("Notification" in window) {
            log("Notification.permission: " + Notification.permission);
            if (Notification.permission === "default") {
                log("Запрашиваю разрешение...");
                const perm = await Notification.requestPermission();
                log("Результат: " + perm);
                if (perm !== "granted") { log("❌ Разрешение не дано"); return; }
            } else if (Notification.permission === "denied") {
                log("❌ Уведомления заблокированы в браузере"); return;
            }
        } else {
            log("Notification API отсутствует (iOS?) — попробую подписаться напрямую");
        }

        log("Получаю VAPID ключ...");
        const resp = await fetch(BASE_URL + "/push/vapid-key", {headers:{"X-Requested-With":"XMLHttpRequest"}});
        const { publicKey } = await resp.json();
        log("✓ VAPID: " + (publicKey ? publicKey.substring(0,20) + "..." : "ПУСТО"));
        if (!publicKey) { log("❌ Нет VAPID ключа"); return; }

        log("Подписываюсь на push...");
        const key = urlBase64ToUint8Array(publicKey);
        const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
        const sub = subscription.toJSON();
        log("✓ Подписка: " + sub.endpoint.substring(0, 60) + "...");

        log("Сохраняю на сервер...");
        const fd = new FormData();
        fd.append("endpoint", sub.endpoint);
        fd.append("p256dh", sub.keys.p256dh);
        fd.append("auth", sub.keys.auth);
        const saveResp = await fetch(BASE_URL + "/push/subscribe", {method:"POST", headers:{"X-Requested-With":"XMLHttpRequest"}, body:fd});
        log("Сервер: " + saveResp.status);
        log("");
        log("✅ Готово! Push подключён на этом устройстве.");
    } catch(e) {
        log("❌ Ошибка: " + e.message);
    }
}

async function inspectResource(url, label) {
    try {
        const response = await fetch(url + "?diag=" + Date.now(), {
            cache: "no-store",
            credentials: "same-origin"
        });
        log(label + ": HTTP " + response.status
            + ", type=" + (response.headers.get("content-type") || "(none)")
            + ", redirected=" + response.redirected
            + ", final=" + response.url);
    } catch (e) {
        log(label + ": FETCH ERROR " + e.name + ": " + e.message);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    const rawData = window.atob(base64);
    return Uint8Array.from(rawData, c => c.charCodeAt(0));
}

async function resetAndSubscribe() {
    document.getElementById("log").textContent = "";
    log("=== Сброс и переподписка ===");

    try {
        // Удаляем все SW регистрации
        const regs = await navigator.serviceWorker.getRegistrations();
        log("SW регистраций найдено: " + regs.length);
        for (const reg of regs) {
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await sub.unsubscribe();
                log("Старая подписка удалена");
            }
            await reg.unregister();
            log("SW удалён: " + reg.scope);
        }

        log("Жду 2 сек...");
        await new Promise(r => setTimeout(r, 2000));

        log("Регистрирую новый SW...");
        const reg = await navigator.serviceWorker.register(BASE_URL + "/service-worker.js", { scope: BASE_URL + "/" });
        log("✓ SW: " + reg.scope);

        // Ждём активации
        let attempts = 0;
        while (!reg.active && attempts < 20) {
            await new Promise(r => setTimeout(r, 500));
            attempts++;
        }
        if (!reg.active) { log("❌ SW не активировался"); return; }
        log("✓ SW активен");

        // Разрешение
        if ("Notification" in window && Notification.permission === "default") {
            const perm = await Notification.requestPermission();
            if (perm !== "granted") { log("❌ Разрешение отклонено"); return; }
        }

        // VAPID
        const resp = await fetch(BASE_URL + "/push/vapid-key", {headers:{"X-Requested-With":"XMLHttpRequest"}});
        const { publicKey } = await resp.json();
        log("VAPID: " + publicKey.substring(0, 20) + "...");

        // Подписка
        log("Подписываюсь...");
        const key = urlBase64ToUint8Array(publicKey);
        const subscription = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
        const sub = subscription.toJSON();
        log("✓ Endpoint: " + sub.endpoint.substring(0, 50) + "...");

        // Сохраняем
        const fd = new FormData();
        fd.append("endpoint", sub.endpoint);
        fd.append("p256dh", sub.keys.p256dh);
        fd.append("auth", sub.keys.auth);
        const saveResp = await fetch(BASE_URL + "/push/subscribe", {method:"POST", headers:{"X-Requested-With":"XMLHttpRequest"}, body:fd});
        log("Сервер: " + saveResp.status);
        log("\\n✅ Готово! Push подключён.");
    } catch(e) {
        log("❌ Ошибка: " + e.message);
    }
}
</script>
</body></html>
HTML;
        exit;
    }
}
