<?php
/**
 * Основной layout приложения Traking
 *
 * Используется всеми страницами после авторизации.
 * Содержит: head, навигацию, flash-сообщения, контент, footer.
 */

$currentUser = \Helpers\Auth::user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
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
    <link rel="apple-touch-icon" href="<?= url('/icons/icon-192x192.svg') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body class="h-screen bg-gray-100 flex flex-col overflow-hidden">

    <!-- Навигация -->
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
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

                            <a href="<?= url('/projects') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/projects') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Проекты
                            </a>

                            <a href="<?= url('/tasks') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/tasks') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Задачи
                            </a>

                            <a href="<?= url('/notifications') ?>"
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/notifications') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Уведомления
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая часть: настройки + колокольчик + пользователь + выход -->
                <div class="hidden md:flex items-center gap-4">
                    <!-- Настройки -->
                    <a href="<?= url('/settings') ?>" class="text-gray-400 hover:text-gray-600 p-1 relative" title="Настройки">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>

                    <!-- Колокольчик уведомлений -->
                    <?php
                    // Получаем DND-статус для компонента колокольчика
                    $_dndSettings = \Helpers\Database::getInstance()->fetch(
                        "SELECT dnd_enabled FROM user_settings WHERE user_id = ?", [\Helpers\Auth::id()]
                    );
                    $GLOBALS['_user_dnd'] = (int) ($_dndSettings['dnd_enabled'] ?? 0);
                    ?>
                    <?php include BASE_PATH . '/views/components/notification-bell.php'; ?>

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

                <!-- Мобильный бургер -->
                <button @click="mobileOpen = !mobileOpen"
                        class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Мобильное меню -->
            <div x-show="mobileOpen" x-transition class="md:hidden pb-4 border-t">
                <div class="pt-3 space-y-2">
                    <?php if (\Helpers\Auth::isAdmin()): ?>
                        <a href="<?= url('/admin/users') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Пользователи
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/dashboard') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Дашборд
                        </a>

                        <a href="<?= url('/projects') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Проекты
                        </a>

                        <a href="<?= url('/tasks') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Задачи
                        </a>

                        <a href="<?= url('/notifications') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Уведомления
                        </a>
                    <?php endif; ?>
                    </a>

                    <div class="border-t pt-3 mt-3">
                        <a href="<?= url('/settings') ?>" class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Настройки
                        </a>
                        <div class="px-3 py-1 text-sm text-gray-500">
                            <?= e($currentUser['name'] ?? $currentUser['login'] ?? '') ?>
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded ml-1">
                                <?= $roleLabels[(int)($currentUser['role_id'] ?? 0)] ?? '' ?>
                            </span>
                        </div>
                        <a href="<?= url('/logout') ?>" class="block px-3 py-2 rounded text-sm font-medium text-red-600 hover:bg-red-50">
                            Выйти
                        </a>
                    </div>
                </div>
            </div>
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

    <!-- Основное содержимое (без прокрутки — прокрутка внутри чата и боковой панели) -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 py-4 overflow-hidden">
        <?= $content ?>
    </main>

    <!-- Toast-уведомления (контейнер) -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- Base URL для JS-компонентов -->
    <script>const BASE_URL = '<?= rtrim(url('/'), '/') ?>';</script>

    <!-- Общий JS (CSRF, fetch-утилиты, toast, Service Worker) -->
    <script src="<?= url('/assets/js/app.js') ?>"></script>

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
