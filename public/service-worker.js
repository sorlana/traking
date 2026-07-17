/**
 * Service Worker для Flowtask PWA
 * Минимальный — только push-уведомления и offline fallback.
 * Без precache чтобы гарантировать мгновенную активацию.
 */

// Установка — мгновенная активация
self.addEventListener('install', () => self.skipWaiting());

// Активация — забираем контроль + чистим старые кэши
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then(names => Promise.all(names.map(n => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

// Fetch — просто network, без кэширования (для надёжности)
self.addEventListener('fetch', (event) => {
    // Не перехватываем — браузер сам обработает
});

// ============================================================================
// Push-уведомления
// ============================================================================

self.addEventListener('push', (event) => {
    let data = { title: 'Flowtask', body: 'Новое сообщение', url: '/traking/' };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            data.body = event.data.text() || 'Новое сообщение';
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Flowtask', {
            body: data.body || 'Новое сообщение',
            icon: data.icon || '/traking/icons/push-icon.php?v=2',
            badge: '/traking/icons/push-badge.php',
            data: { url: data.url || '/traking/' },
            vibrate: [200, 100, 200],
            requireInteraction: true,
            tag: 'flowtask-' + Date.now(),
            renotify: true,
        })
    );
});

// Клик по уведомлению — открыть задачу
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/traking/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if (client.url.includes(url) && 'focus' in client) return client.focus();
            }
            return clients.openWindow(url);
        })
    );
});

// Переподписка при смене endpoint
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil((async () => {
        try {
            const basePath = new URL(self.registration.scope).pathname.replace(/\/$/, '');
            let subscription = event.newSubscription;

            if (!subscription) {
                const keyResp = await fetch(basePath + '/push/vapid-key', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const { publicKey } = await keyResp.json();
                const padding = '='.repeat((4 - publicKey.length % 4) % 4);
                const base64 = (publicKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                const raw = atob(base64);
                const key = Uint8Array.from(raw, c => c.charCodeAt(0));

                subscription = await self.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: key,
                });
            }

            const sub = subscription.toJSON();
            const fd = new FormData();
            fd.append('endpoint', sub.endpoint);
            fd.append('p256dh', sub.keys.p256dh);
            fd.append('auth', sub.keys.auth);

            await fetch(basePath + '/push/subscribe', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
        } catch (e) {
            console.error('[SW] pushsubscriptionchange failed:', e);
        }
    })());
});
