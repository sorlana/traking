<?php
/**
 * Основной layout приложения Traking
 *
 * Используется всеми страницами после авторизации.
 * Содержит: head, навигацию, flash-сообщения, контент, footer.
 */

$currentUser = \Helpers\Auth::user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';

// Получаем настройку звука для текущего пользователя
$_soundEnabled = 0;
if ($currentUser) {
    try {
        $db = \Helpers\Database::getInstance();
        $_userSettings = $db->fetch("SELECT sound_enabled FROM user_settings WHERE user_id = ?", [(int)$currentUser['id']]);
        $_soundEnabled = (int) ($_userSettings['sound_enabled'] ?? 0);
    } catch (\Throwable $e) {
        $_soundEnabled = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Traking') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= url('/icons/icon-192x192.png') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">

    <!-- Навигация -->
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-10 md:h-16">
                <!-- Логотип -->
                <div class="flex items-center gap-6">
                    <a href="<?= url('/dashboard') ?>" class="text-xl font-bold text-blue-700">Traking</a>

                    <!-- Навигационные ссылки (десктоп) -->
                    <div class="hidden md:flex items-center gap-4">
                        <?php if (\Helpers\Auth::isAdmin()): ?>
                            <a href="<?= url('/admin/users') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/admin/users') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Пользователи
                            </a>
                        <?php else: ?>
                            <a href="<?= url('/dashboard') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/dashboard') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Дашборд
                            </a>

                            <a href="<?= url('/tasks/last') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/tasks') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Задачи
                            </a>

                            <a href="<?= url('/projects') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/projects') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Проекты
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая часть: помощь + настройки + пользователь + выход -->
                <div class="hidden md:flex items-center gap-4">
                    <!-- Помощь -->
                    <a href="<?= url('/help') ?>" class="text-gray-400 hover:text-gray-600 p-1 relative" title="Помощь">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </a>
                    <!-- Настройки -->
                    <a href="<?= url('/settings') ?>" class="text-gray-400 hover:text-gray-600 p-1 relative" title="Настройки">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>

                    <?php
                    // Получаем количество непрочитанных уведомлений (для мобильной навигации)
                    $_dndSettings = \Helpers\Database::getInstance()->fetch(
                        "SELECT dnd_enabled FROM user_settings WHERE user_id = ?", [\Helpers\Auth::id()]
                    );
                    $GLOBALS['_user_dnd'] = (int) ($_dndSettings['dnd_enabled'] ?? 0);
                    $_unreadCount = 0;
                    try {
                        $_unreadRow = \Helpers\Database::getInstance()->fetch(
                            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", [\Helpers\Auth::id()]
                        );
                        $_unreadCount = (int) ($_unreadRow['cnt'] ?? 0);
                    } catch (\Throwable $e) {}
                    ?>

                    <span class="text-sm text-gray-600">
                        <?= e($currentUser['name'] ?? $currentUser['login'] ?? '') ?>
                    </span>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                        <?php
                        $roleLabels = [1 => 'Админ', 2 => 'Руководитель', 3 => 'Исполнитель'];
                        echo $roleLabels[(int)($currentUser['role_id'] ?? 0)] ?? '';
                        ?>
                    </span>
                    <a href="<?= url('/logout') ?>"
                       class="text-sm text-red-600 hover:text-red-700 font-medium">
                        Выйти
                    </a>
                </div>

                <!-- Мобильный хедер: имя + выход -->
                <div class="md:hidden flex items-center gap-2">
                    <span class="text-xs text-gray-500"><?= e($currentUser['name'] ?? '') ?></span>
                    <a href="<?= url('/logout') ?>" class="text-xs text-red-500">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Нижняя навигация (мобильная) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-50">
        <div class="flex justify-around items-center h-14">
            <?php if (\Helpers\Auth::isAdmin()): ?>
                <a href="<?= url('/admin/users') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= str_contains($currentPath, '/admin') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Пользователи</span>
                </a>
            <?php else: ?>
                <a href="<?= url('/dashboard') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= str_contains($currentPath, '/dashboard') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-xs">Главная</span>
                </a>
                <a href="<?= url('/tasks/last') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= str_contains($currentPath, '/tasks') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-xs">Задачи</span>
                </a>
                <a href="<?= url('/projects') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= str_contains($currentPath, '/projects') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Проекты</span>
                </a>
            <?php endif; ?>
            <!-- Уведомления -->
            <a href="<?= url('/notifications') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 relative <?= str_contains($currentPath, '/notifications') ? 'text-blue-600' : 'text-gray-500' ?>"
               x-data="{ unread: 0 }" x-init="fetch(BASE_URL + '/ajax/notifications/count', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(d=>{unread=d.count||0}); setInterval(()=>{fetch(BASE_URL+'/ajax/notifications/count',{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(d=>{unread=d.count||0})},30000)">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="unread > 0" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                </div>
                <span class="text-xs">Уведомления</span>
            </a>
            <a href="<?= url('/settings') ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= str_contains($currentPath, '/settings') ? 'text-blue-600' : 'text-gray-500' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-xs">Настройки</span>
            </a>
        </div>
    </nav>

    <!-- Flash-сообщения (поверх интерфейса, fixed) -->
    <?php $flashSuccess = \Helpers\Session::getFlash('success'); ?>
    <?php $flashError = \Helpers\Session::getFlash('error'); ?>

    <?php if ($flashSuccess): ?>
        <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] w-full max-w-md px-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 shadow-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?= e($flashSuccess) ?></span>
                </div>
                <button @click="show = false" class="text-green-500 hover:text-green-700 ml-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] w-full max-w-md px-4" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 shadow-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?= e($flashError) ?></span>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 ml-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Основное содержимое -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 py-4 pb-20 md:pb-4">
        <?= $content ?>
    </main>

    <!-- Toast-уведомления (контейнер) -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- Base URL для JS-компонентов -->
    <script>const BASE_URL = '<?= rtrim(url('/'), '/') ?>';</script>

    <!-- Общий JS (CSRF, fetch-утилиты, toast, Service Worker) -->
    <script src="<?= url('/assets/js/app.js') ?>?v=2"></script>

    <!-- Мигающая фавиконка (inline для гарантированной работы) -->
    <script>
    window.FaviconBlinker = {
        originalHref: null,
        blinkInterval: null,
        isBlinking: false,
        alertFavicon: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#EF4444"/><path d="M16 4L8 28h3.5l4.5-12 4.5 12H24L16 4z" fill="white"/><path d="M16 4L14.5 28h3L16 4z" fill="#EF4444"/></svg>'),
        start() {
            if (this.isBlinking) return;
            this.isBlinking = true;
            const link = document.querySelector('link[rel="icon"]');
            if (!link) return;
            this.originalHref = link.href;
            let visible = true;
            this.blinkInterval = setInterval(() => {
                link.href = visible ? this.alertFavicon : this.originalHref;
                visible = !visible;
            }, 800);
            // Также меняем title
            this._titleInterval = setInterval(() => {
                document.title = document.title.startsWith('💬') ? document.title.substring(2) : '💬 ' + document.title;
            }, 1000);
        },
        stop() {
            if (!this.isBlinking) return;
            this.isBlinking = false;
            if (this.blinkInterval) { clearInterval(this.blinkInterval); this.blinkInterval = null; }
            if (this._titleInterval) { clearInterval(this._titleInterval); this._titleInterval = null; }
            const link = document.querySelector('link[rel="icon"]');
            if (link && this.originalHref) link.href = this.originalHref;
            // Убираем 💬 из title
            if (document.title.startsWith('💬')) document.title = document.title.substring(2);
        }
    };
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') FaviconBlinker.stop();
    });
    </script>

    <!-- Звуковое уведомление -->
    <script>
    window.NotificationSound = {
        enabled: <?= $_soundEnabled ? 'true' : 'false' ?>,
        audioCtx: null,
        /**
         * Воспроизвести звук уведомления (синтезированный, без файла)
         */
        play() {
            if (!this.enabled) return;
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                const ctx = this.audioCtx;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.3);
            } catch(e) {}
        }
    };
    </script>

    <!-- Подписка на Web Push уведомления -->
    <script>
    // Подписка на push-уведомления
    async function subscribeToPush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Получаем VAPID public key
            const response = await fetch(BASE_URL + '/push/vapid-key', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const { publicKey } = await response.json();
            if (!publicKey || publicKey === 'ВСТАВЬ_СВОЙ_PUBLIC_KEY') return;
            
            // Конвертируем base64url в Uint8Array
            const applicationServerKey = urlBase64ToUint8Array(publicKey);
            
            // Подписываемся
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey,
            });
            
            const sub = subscription.toJSON();
            
            // Отправляем подписку на сервер
            const formData = new FormData();
            formData.append('endpoint', sub.endpoint);
            formData.append('p256dh', sub.keys.p256dh);
            formData.append('auth', sub.keys.auth);
            
            await fetch(BASE_URL + '/push/subscribe', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
        } catch(e) {
            // Пользователь отклонил или ошибка — молча пропускаем
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Запрашиваем разрешение и подписываемся
    if ('Notification' in window && Notification.permission === 'default') {
        // Подписываемся через 3 секунды после загрузки (не сразу, чтобы не раздражать)
        setTimeout(() => {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    subscribeToPush();
                }
            });
        }, 3000);
    } else if ('Notification' in window && Notification.permission === 'granted') {
        subscribeToPush();
    }
    </script>
</body>
</html>
