<?php
/**
 * Основной layout приложения Traking
 *
 * Используется всеми страницами после авторизации.
 * Содержит: head, навигацию, flash-сообщения, контент, footer.
 */

$currentUser = \Helpers\Auth::user();
$basePath = rtrim($GLOBALS['config']['base_path'] ?? '', '/');
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
// Убираем query string
if (($qPos = strpos($currentPath, '?')) !== false) {
    $currentPath = substr($currentPath, 0, $qPos);
}
// Убираем base_path
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}

// Получаем настройку звука для текущего пользователя
$_soundEnabled = 0;
$_pushEnabled = 0;
if ($currentUser) {
    try {
        $db = \Helpers\Database::getInstance();
        $_userSettings = $db->fetch("SELECT sound_enabled, push_enabled FROM user_settings WHERE user_id = ?", [(int)$currentUser['id']]);
        $_soundEnabled = (int) ($_userSettings['sound_enabled'] ?? 0);
        $_pushEnabled = (int) ($_userSettings['push_enabled'] ?? 1);
    } catch (\Throwable $e) {
        $_soundEnabled = 0;
        $_pushEnabled = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($title ?? 'Traking') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <style>[x-cloak]{display:none!important}</style>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: { 50: '#f7f7f8', 100: '#f1f2f3', 200: '#e2e4e7', 300: '#4f6bed', 400: '#4f6bed', 500: '#4f6bed', 600: '#4f6bed', 700: '#4f6bed', 800: '#4f6bed', 900: '#4f6bed' },
                    blue: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#4f6bed', 400: '#4f6bed', 500: '#4f6bed', 600: '#4f6bed', 700: '#4f6bed', 800: '#4f6bed', 900: '#4f6bed' }
                }
            }
        }
    }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="manifest" href="<?= asset('/manifest.json') ?>?v=6">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>?v=2">
    <link rel="apple-touch-icon" href="<?= asset('/icons/push-icon.php') ?>?v=2">
    <!-- iOS: белая заставка вместо авто-генерируемой с иконкой -->
    <link rel="apple-touch-startup-image" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'><rect fill='%23fff' width='1' height='1'/></svg>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>?v=57">
</head>
<body class="app-shell min-h-screen flex flex-col bg-white">

    <a href="#main-content" class="skip-link">Перейти к основному содержимому</a>

    <!-- Навигация -->
    <nav class="app-header bg-white border-b fixed top-0 left-0 right-0 z-50" x-data="{ mobileOpen: false }" aria-label="Основная навигация">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-10 md:h-16">
                <!-- Логотип -->
                <div class="flex items-center gap-10">
                    <a href="<?= url('/dashboard') ?>" class="hidden md:flex items-center" aria-label="Flowtask">
                        <img src="<?= asset('/icons/flowtask_logo.svg') ?>?v=2" alt="Flowtask" class="h-8 w-auto">
                    </a>
                    <a href="<?= url('/dashboard') ?>" class="md:hidden flex items-center" aria-label="Flowtask">
                        <img src="<?= asset('/favicon.svg') ?>?v=2" alt="Flowtask" class="h-8 w-8">
                    </a>

                    <!-- Навигационные ссылки (десктоп) -->
                    <div class="hidden md:flex items-center gap-7">
                        <?php if (\Helpers\Auth::isAdmin()): ?>
                            <a href="<?= url('/admin/users') ?>"
                               <?= str_starts_with($currentPath, '/admin/users') ? 'aria-current="page"' : '' ?>
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/admin/users') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Пользователи
                            </a>
                        <?php else: ?>
                            <a href="<?= url('/dashboard') ?>"
                               <?= str_starts_with($currentPath, '/dashboard') ? 'aria-current="page"' : '' ?>
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/dashboard') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Дашборд
                            </a>

                            <a href="<?= url('/tasks/last') ?>"
                               <?= str_starts_with($currentPath, '/tasks') ? 'aria-current="page"' : '' ?>
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/tasks') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Задачи
                            </a>

                            <a href="<?= url('/projects') ?>"
                               <?= str_starts_with($currentPath, '/projects') ? 'aria-current="page"' : '' ?>
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/projects') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Проекты
                            </a>

                            <a href="<?= url('/calendar') ?>"
                               <?= str_starts_with($currentPath, '/calendar') ? 'aria-current="page"' : '' ?>
                               class="text-sm font-medium <?= str_starts_with($currentPath, '/calendar') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">
                                Календарь
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая часть: помощь + настройки + уведомления + пользователь + выход -->
                <div class="hidden md:flex items-center gap-1">
                    <!-- Помощь -->
                    <a href="<?= url('/help') ?>" class="desktop-header-icon p-1 relative" title="Помощь" aria-label="Помощь">
                        <svg class="w-5 h-5" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </a>
                    <!-- Настройки -->
                    <a href="<?= url('/settings') ?>" class="desktop-header-icon p-1 relative" title="Настройки" aria-label="Настройки">
                        <svg class="w-5 h-5" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>

                    <!-- Колокольчик уведомлений (десктоп) -->
                    <?php
                    $_dndSettings = \Helpers\Database::getInstance()->fetch(
                        "SELECT dnd_enabled FROM user_settings WHERE user_id = ?", [\Helpers\Auth::id()]
                    );
                    $GLOBALS['_user_dnd'] = (int) ($_dndSettings['dnd_enabled'] ?? 0);
                    ?>
                    <?php include BASE_PATH . '/views/components/notification-bell.php'; ?>

                    <span class="ml-6 text-sm text-gray-600">
                        <?= e($currentUser['name'] ?? $currentUser['login'] ?? '') ?>
                    </span>
                    <span class="ml-2 text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                        <?php
                        $roleLabels = [1 => 'Админ', 2 => 'Руководитель', 3 => 'Исполнитель'];
                        echo $roleLabels[(int)($currentUser['role_id'] ?? 0)] ?? '';
                        ?>
                    </span>
                    <div class="relative ml-2 flex items-center pl-3">
                        <span class="absolute left-0 h-6 w-px bg-gray-300" aria-hidden="true"></span>
                        <a href="<?= url('/logout') ?>"
                           class="desktop-header-icon p-1 relative"
                           title="Выйти" aria-label="Выйти">
                            <svg class="h-5 w-5" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Мобильный хедер: имя + роль + выход -->
                <div class="md:hidden flex items-center gap-2">
                    <span class="text-xs text-gray-500"><?= e($currentUser['name'] ?? '') ?></span>
                    <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded"><?= $roleLabels[(int)($currentUser['role_id'] ?? 0)] ?? '' ?></span>
                    <a href="<?= url('/logout') ?>" class="text-xs text-red-500">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Нижняя навигация (мобильная) -->
    <nav class="mobile-bottom-nav md:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-50" x-data="{ moreOpen: false }" @keydown.escape.window="moreOpen = false" aria-label="Мобильная навигация">
        <div class="flex items-center h-14">
            <?php if (\Helpers\Auth::isAdmin()): ?>
                <a href="<?= url('/admin/users') ?>" <?= str_contains($currentPath, '/admin') ? 'aria-current="page"' : '' ?> class="flex-1 flex flex-col items-center gap-0.5 py-1 <?= str_contains($currentPath, '/admin') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-xs">Пользователи</span>
                </a>
            <?php else: ?>
                <a href="<?= url('/dashboard') ?>" <?= str_contains($currentPath, '/dashboard') ? 'aria-current="page"' : '' ?> class="flex-1 flex flex-col items-center gap-0.5 py-1 <?= str_contains($currentPath, '/dashboard') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-xs">Главная</span>
                </a>
                <a href="<?= url('/tasks/last') ?>" <?= str_contains($currentPath, '/tasks') ? 'aria-current="page"' : '' ?> class="flex-1 flex flex-col items-center gap-0.5 py-1 <?= str_contains($currentPath, '/tasks') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-xs">Задачи</span>
                </a>
                <a href="<?= url('/projects') ?>" <?= str_contains($currentPath, '/projects') ? 'aria-current="page"' : '' ?> class="flex-1 flex flex-col items-center gap-0.5 py-1 <?= str_contains($currentPath, '/projects') ? 'text-blue-600' : 'text-gray-500' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-xs">Проекты</span>
                </a>
            <?php endif; ?>
            <!-- Уведомления -->
            <a href="<?= url('/notifications') ?>" <?= str_contains($currentPath, '/notifications') ? 'aria-current="page"' : '' ?> class="flex-1 flex flex-col items-center gap-0.5 py-1 relative <?= str_contains($currentPath, '/notifications') ? 'text-blue-600' : 'text-gray-500' ?>"
               :aria-label="unread > 0 ? `Уведомления: непрочитанных ${unread}` : 'Уведомления'"
               x-data="{ unread: 0 }" x-init="fetch(BASE_URL + '/ajax/notifications/count', {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(d=>{unread=d.count||0}); setInterval(()=>{fetch(BASE_URL+'/ajax/notifications/count',{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(d=>{unread=d.count||0})},30000)">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="unread > 0" x-cloak class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full" style="display:none" aria-hidden="true"></span>
                </div>
                <span class="text-xs">Уведомления</span>
            </a>
            <!-- Ещё -->
            <button type="button" @click="moreOpen = !moreOpen" :aria-expanded="moreOpen.toString()" aria-controls="mobile-more-menu" class="flex-1 flex flex-col items-center gap-0.5 py-1 <?= str_contains($currentPath, '/settings') || str_contains($currentPath, '/help') || str_contains($currentPath, '/calendar') ? 'text-blue-600' : 'text-gray-500' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                <span class="text-xs">Ещё</span>
            </button>
            <!-- Выпадающее меню «Ещё» -->
            <div id="mobile-more-menu" x-show="moreOpen" @click.outside="moreOpen = false" x-cloak x-transition
                 aria-label="Дополнительная навигация"
                 class="absolute bottom-14 right-2 overflow-hidden bg-white rounded-lg shadow-lg border py-2 min-w-[160px]" style="display:none">
                <?php if (!\Helpers\Auth::isAdmin()): ?>
                    <a href="<?= url('/calendar') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm <?= str_contains($currentPath, '/calendar') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                        Календарь
                    </a>
                <?php endif; ?>
                <a href="<?= url('/settings') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Настройки
                </a>
                <a href="<?= url('/help') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Помощь
                </a>
            </div>
        </div>
    </nav>

    <!-- Flash-сообщения (поверх интерфейса, fixed) -->
    <?php $flashSuccess = \Helpers\Session::getFlash('success'); ?>
    <?php $flashError = \Helpers\Session::getFlash('error'); ?>

    <?php if ($flashSuccess): ?>
        <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] w-full max-w-md px-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" role="status" aria-live="polite">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 shadow-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?= e($flashSuccess) ?></span>
                </div>
                <button type="button" @click="show = false" class="text-green-500 hover:text-green-700 ml-2" aria-label="Закрыть уведомление">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] w-full max-w-md px-4" x-data="{ show: true }" x-show="show" x-transition role="alert" aria-live="assertive">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 shadow-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?= e($flashError) ?></span>
                </div>
                <button type="button" @click="show = false" class="text-red-500 hover:text-red-700 ml-2" aria-label="Закрыть уведомление">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Основное содержимое -->
    <main id="main-content" tabindex="-1" class="flex-1 max-w-7xl w-full mx-auto px-4 py-4 pb-28 md:pb-4 pt-14 md:pt-20">
        <?= $content ?>
    </main>

    <!-- Единое подтверждение удаления сущностей -->
    <div id="delete-confirm-modal" hidden class="hidden fixed inset-0 z-[100] items-center justify-center p-4" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-950/45 backdrop-blur-[1px]" data-delete-modal-cancel aria-hidden="true"></div>
        <section class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl sm:p-6"
                 role="alertdialog" aria-modal="true" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-message" tabindex="-1">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="delete-confirm-title" class="text-lg font-semibold text-gray-900">Подтвердите удаление</h2>
                    <p id="delete-confirm-message" class="mt-2 text-sm leading-6 text-gray-600"></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-delete-modal-cancel class="ui-btn ui-btn-secondary justify-center">Отмена</button>
                <button type="button" data-delete-modal-confirm class="ui-btn justify-center border-blue-600 bg-blue-600 text-white hover:border-blue-700 hover:bg-blue-700 focus-visible:ring-blue-500">Удалить</button>
            </div>
        </section>
    </div>

    <!-- Toast-уведомления (контейнер) -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2" role="status" aria-live="polite" aria-atomic="true"></div>

    <!-- Base URL для JS-компонентов -->
    <script>
    const BASE_URL = '<?= rtrim(url('/'), '/') ?>';
    const PUSH_ENABLED = <?= $_pushEnabled ? 'true' : 'false' ?>;
    </script>

    <!-- Общий JS (CSRF, fetch-утилиты, toast, Service Worker) -->
    <script src="<?= asset('/assets/js/app.js') ?>?v=16"></script>

    <!-- Динамический theme-color для модалок -->
    <script>
    // Пусто — не используем MutationObserver, он ломает интерфейс
    </script>

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

</body>
</html>
