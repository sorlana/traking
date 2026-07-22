<?php
/**
 * Страница уведомлений — views/notifications/index.php
 *
 * Список уведомлений пользователя с фильтром (все/непрочитанные)
 * и кнопкой «Отметить все прочитанными».
 */
$layout = 'layouts/app';

// Форматирование относительного времени
if (!function_exists('formatTimeAgo')) {
    function formatTimeAgo(string $datetime): string
    {
        if (empty($datetime)) return '';
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
        if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
        if ($diff < 604800) return floor($diff / 86400) . ' дн. назад';
        return date('d.m.Y H:i', $timestamp);
    }
}

// Иконки по типу уведомления
$typeIcons = [
    'task_assigned' => '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'comment_added' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
    'status_changed' => '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
    'file_uploaded' => '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>',
];
?>

<div class="space-y-6">
    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">
            Уведомления
            <?php if ($unreadCount > 0): ?>
                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-sm font-medium ml-2">
                    <?= $unreadCount ?>
                </span>
            <?php endif; ?>
        </h1>

        <div class="flex items-center gap-3">
            <!-- Фильтр -->
            <div class="flex rounded-md border border-gray-300 overflow-hidden">
                <a href="<?= url('/notifications') ?>"
                   class="px-3 py-1.5 text-sm <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                    Все
                </a>
                <a href="<?= url('/notifications') ?>?filter=unread"
                   class="px-3 py-1.5 text-sm border-l <?= $filter === 'unread' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                    Непрочитанные
                </a>
            </div>

            <!-- Отметить все прочитанными -->
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="<?= url('/notifications/read-all') ?>">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                        Прочитать все
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Список уведомлений -->
    <?php if (empty($notifications)): ?>
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-gray-500">
                <?= $filter === 'unread' ? 'Нет непрочитанных уведомлений' : 'Уведомлений пока нет' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border divide-y overflow-hidden">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $isRead = (bool) $notification['is_read'];
                $icon = $typeIcons[$notification['type']] ?? $typeIcons['task_assigned'];
                $link = $notification['task_id'] ? url('/tasks/' . (int) $notification['task_id']) : '#';
                $timeAgo = formatTimeAgo($notification['created_at'] ?? '');
                ?>
                <div class="flex items-start gap-4 p-4 <?= $isRead ? 'bg-white' : 'bg-blue-50' ?> hover:bg-gray-50 transition">
                    <!-- Иконка -->
                    <div class="flex-shrink-0 mt-0.5">
                        <?= $icon ?>
                    </div>

                    <!-- Содержимое -->
                    <div class="flex-1 min-w-0">
                        <a href="<?= $link ?>" class="block">
                            <p class="text-sm <?= $isRead ? 'text-gray-600' : 'text-gray-900 font-medium' ?> truncate">
                                <?= e($notification['title']) ?>
                            </p>
                            <?php if (!empty($notification['message'])): ?>
                                <p class="text-xs text-gray-500 mt-0.5"><?= e($notification['message']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">
                                <?= e($timeAgo) ?>
                            </p>
                        </a>
                    </div>

                    <!-- Кнопка прочитать -->
                    <?php if (!$isRead): ?>
                        <form method="POST" action="<?= url('/notifications/' . (int) $notification['id'] . '/read') ?>" class="flex-shrink-0">
                            <?= csrf_field() ?>
                            <button type="submit" title="Отметить прочитанным"
                                    class="p-1 text-gray-400 hover:text-blue-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
