/**
 * Service Worker для Traking PWA
 * - Кэширование статических ресурсов
 * - Offline-fallback страница
 */

const CACHE_NAME = 'traking-v45';
const OFFLINE_URL = '/offline.html';

// Ресурсы для предварительного кэширования
const PRECACHE_URLS = [
    '/',
    '/offline.html',
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png'
];

// Установка — кэшируем статику
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_URLS);
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
        icon: '/favicon.svg',
        badge: '/favicon.svg',
        data: { url: data.url || '/' },
        vibrate: [200, 100, 200],
        requireInteraction: false,
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
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
