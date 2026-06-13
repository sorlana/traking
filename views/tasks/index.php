<?php
/**
 * Список задач — views/tasks/index.php
 *
 * Показывает таблицу задач с фильтрами.
 * Для executor — только его задачи.
 * Для admin/manager — все задачи из доступных проектов.
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

// Карта приоритетов → цвета и лейблы
$priorityLabels = [
    'low' => ['label' => 'Низкий', 'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high' => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
];

// Карта статусов → цвета
$statusColors = [
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'revision' => 'bg-orange-100 text-orange-800',
    'done' => 'bg-green-100 text-green-800',
];
?>

<div class="space-y-6">
    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <?php if ($project ?? null): ?>
                    Задачи проекта «<?= e($project['title']) ?>»
                <?php else: ?>
                    Задачи
                <?php endif; ?>
            </h1>
            <?php if ($project ?? null): ?>
                <a href="<?= url('/projects/' . (int) $project['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800">← К проекту</a>
            <?php endif; ?>
        </div>

        <?php if (can('create_task', $project['id'] ?? null)): ?>
            <a href="<?= url('/tasks/create') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Создать задачу
            </a>
        <?php endif; ?>
    </div>

    <!-- Фильтры -->
    <form method="GET" action="<?= url('/tasks') ?>" class="bg-white rounded-lg shadow-sm border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <!-- Статус -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Все</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= ($filters['status'] ?? '') === $s['code'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Приоритет -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                <select name="priority" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Все</option>
                    <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Низкий</option>
                    <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Высокий</option>
                    <option value="urgent" <?= ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Срочный</option>
                </select>
            </div>

            <!-- Исполнитель (для admin/manager) -->
            <?php if ($roleId <= 2): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="assigned_to" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Все</option>
                    <?php foreach ($executors as $exec): ?>
                        <option value="<?= (int) $exec['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $exec['id'] ? 'selected' : '' ?>>
                            <?= e($exec['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Срок -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Все</option>
                    <option value="today" <?= ($filters['deadline'] ?? '') === 'today' ? 'selected' : '' ?>>Сегодня</option>
                    <option value="week" <?= ($filters['deadline'] ?? '') === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                    <option value="overdue" <?= ($filters['deadline'] ?? '') === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                </select>
            </div>

            <!-- Проект (если не фильтруем по одному проекту) -->
            <?php if (!($project ?? null) && !empty($projects)): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                <select name="project_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Все проекты</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($filters['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Кнопки -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">
                    Фильтр
                </button>
                <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">
                    Сбросить
                </a>
            </div>
        </div>

        <?php if ($project ?? null): ?>
            <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
        <?php endif; ?>
    </form>

    <!-- Таблица задач -->
    <?php if (empty($tasks)): ?>
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500">Задачи не найдены</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Название</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Статус</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Приоритет</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Исполнитель</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Срок</th>
                            <?php if (!($project ?? null)): ?>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden xl:table-cell">Проект</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $isOverdue = !empty($task['deadline'])
                                && strtotime($task['deadline']) < strtotime(date('Y-m-d'))
                                && ($task['status_code'] ?? '') !== 'done';
                            $prio = $priorityLabels[$task['priority'] ?? 'medium'] ?? $priorityLabels['medium'];
                            $statusClass = $statusColors[$task['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <tr class="hover:bg-gray-50 transition <?= $isOverdue ? 'bg-red-50' : '' ?>">
                                <td class="px-4 py-3">
                                    <a href="<?= url('/tasks/' . (int) $task['id']) ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                        <?= e($task['title']) ?>
                                    </a>
                                    <?php if ($isOverdue): ?>
                                        <span class="ml-1 text-xs text-red-600 font-medium">Просрочено</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                        <?= e($task['status_name'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>">
                                        <?= $prio['label'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden md:table-cell">
                                    <?= e($task['assigned_name'] ?? '—') ?>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-600' ?>">
                                    <?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : '—' ?>
                                </td>
                                <?php if (!($project ?? null)): ?>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="text-gray-600 hover:text-blue-600 text-xs">
                                        <?= e($task['project_title'] ?? '') ?>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-sm text-gray-400">Найдено задач: <?= count($tasks) ?></p>
    <?php endif; ?>
</div>
