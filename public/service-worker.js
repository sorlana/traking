/**
 * Service Worker для Traking PWA
 * - Кэширование статических ресурсов
 * - Offline-fallback страница
 */

const CACHE_NAME = 'traking-v76';
const OFFLINE_URL = '/offline.html';

// Ресурсы для предварительного кэширования
const PRECACHE_URLS = [
    './',
    './offline.html',
    './assets/css/app.css',
    './assets/js/app.js',
    './manifest.json',
    './favicon.svg',
    './icons/flowtask_logo.svg'
];

// Установка — кэшируем статику (ошибка кэширования не блокирует установку)
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS).catch(e => {
                console.warn('[SW] Precache failed (non-blocking):', e);
            });
        })
    );
    self.skipWaiting();
});

// Активация — удаляем старые кэши
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch — стратегия Network First для страниц, Cache First для статики
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Только GET-запросы
    if (request.method !== 'GET') return;

    // Для навигационных запросов — Network First
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    // Для статики (css, js, img) — Cache First
    if (request.url.match(/\.(css|js|png|jpg|jpeg|svg|ico|woff2?)$/)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                return cached || fetch(request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // Для всего остального — Network First
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// ============================================================================
// Push-уведомления
// ============================================================================

// Обработка входящего push-сообщения
self.addEventListener('push', (event) => {
    let data = { title: 'Traking', body: 'Новое сообщение', url: '/' };
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch(e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: '/traking/icons/push-icon.php',
        badge: '/traking/icons/push-badge.php',
        data: { url: data.url || '/' },
        vibrate: [200, 100, 200],
        requireInteraction: true,
        tag: 'flowtask-msg-' + (data.url || '').replace(/\D/g, ''),
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Браузер может заменить push endpoint после обновления, очистки данных или
// восстановления приложения. Синхронизируем новую подписку с сервером.
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil((async () => {
        try {
            let subscription = event.newSubscription;

            if (!subscription) {
                let applicationServerKey = event.oldSubscription?.options?.applicationServerKey;

                if (!applicationServerKey) {
                    const basePath = new URL(self.registration.scope).pathname.replace(/\/$/, '');
                    const keyResponse = await fetch(basePath + '/push/vapid-key', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const keyData = await keyResponse.json();
                    const padding = '='.repeat((4 - keyData.publicKey.length % 4) % 4);
                    const base64 = (keyData.publicKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const rawData = atob(base64);
                    applicationServerKey = Uint8Array.from(rawData, char => char.charCodeAt(0));
                }

                subscription = await self.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey,
                });
            }

            const data = subscription.toJSON();
            const formData = new FormData();
            formData.append('endpoint', data.endpoint);
            formData.append('p256dh', data.keys.p256dh);
            formData.append('auth', data.keys.auth);

            const basePath = new URL(self.registration.scope).pathname.replace(/\/$/, '');
            const response = await fetch(basePath + '/push/subscribe', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: formData,
            });

            const result = await response.json().catch(() => null);
            if (!response.ok || !result?.success) {
                throw new Error('Subscription sync failed: HTTP ' + response.status);
            }
        } catch (error) {
            console.error('[SW] Push subscription recovery failed:', error);
        }
    })());
});

// Клик по push-уведомлению — открыть задачу
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Если есть открытая вкладка — переключиться на неё
            for (const client of clientList) {
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Иначе — открыть новую
            return clients.openWindow(url);
        })
    );
});
