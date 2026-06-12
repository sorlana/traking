<?php
/**
 * Дашборд администратора — views/dashboard/admin.php
 *
 * Виджеты: статистика, последние действия, просроченные задачи.
 */
$layout = 'layouts/app';
?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Дашборд</h1>

    <!-- Виджеты-статистика -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Проекты -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Проекты</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $totalProjects ?></p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Задачи -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Задачи</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $totalTasks ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Пользователи -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Пользователи</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $totalUsers ?></p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Активность сегодня -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Активность сегодня</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $todayActivity ?></p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Последние действия -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Последние действия</h3>

            <?php if (empty($recentActivity)): ?>
                <p class="text-sm text-gray-400">Действий пока нет</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($recentActivity as $entry): ?>
                        <?php
                        $actionLabels = [
                            'task_created' => 'создал(а) задачу',
                            'task_closed' => 'закрыл(а) задачу',
                            'status_changed' => 'изменил(а) статус',
                            'task_assigned' => 'назначил(а) исполнителя',
                            'task_reassigned' => 'переназначил(а) задачу',
                            'comment_added' => 'добавил(а) комментарий',
                            'file_uploaded' => 'загрузил(а) файл',
                            'task_updated' => 'обновил(а) задачу',
                        ];
                        $actionText = $actionLabels[$entry['action_type']] ?? $entry['action_type'];
                        ?>
                        <div class="flex items-start gap-3 text-sm">
                            <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-gray-600">
                                    <?= mb_substr($entry['user_name'] ?? '?', 0, 1) ?>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-700">
                                    <span class="font-medium"><?= e($entry['user_name'] ?? 'Система') ?></span>
                                    <?= e($actionText) ?>
                                    <?php if (!empty($entry['task_title'])): ?>
                                        <a href="/tasks/<?= (int) $entry['task_id'] ?>" class="text-blue-600 hover:text-blue-800">
                                            <?= e($entry['task_title']) ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-400"><?= date('d.m.Y H:i', strtotime($entry['created_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Просроченные задачи -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-4">
                Просроченные задачи
                <?php if (!empty($overdueTasks)): ?>
                    <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs ml-1"><?= count($overdueTasks) ?></span>
                <?php endif; ?>
            </h3>

            <?php if (empty($overdueTasks)): ?>
                <p class="text-sm text-gray-400">Просроченных задач нет 🎉</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($overdueTasks as $task): ?>
                        <div class="flex items-center gap-3 p-2 bg-red-50 rounded">
                            <div class="flex-1 min-w-0">
                                <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                    <?= e($task['title']) ?>
                                </a>
                                <p class="text-xs text-gray-500">
                                    <?= e($task['project_title'] ?? '') ?> • <?= e($task['assigned_name'] ?? 'Не назначен') ?>
                                </p>
                            </div>
                            <span class="text-xs text-red-600 font-medium flex-shrink-0">
                                <?= date('d.m', strtotime($task['deadline'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
