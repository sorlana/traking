<?php
/**
 * Компонент истории действий (таймлайн) — views/components/activity-log.php
 *
 * Показывает хронологию действий по задаче или проекту.
 * Подключается в карточке задачи и проекта.
 *
 * Ожидаемые переменные:
 *   $activityLog — массив записей из ActivityLog
 */

if (!isset($activityLog) || empty($activityLog)) {
    return;
}

// Карта типов действий → описание и иконка/цвет
$actionLabels = [
    'task_created' => ['label' => 'Создал(а) задачу', 'color' => 'bg-blue-500'],
    'task_closed' => ['label' => 'Закрыл(а) задачу', 'color' => 'bg-gray-500'],
    'status_changed' => ['label' => 'Изменил(а) статус', 'color' => 'bg-yellow-500'],
    'task_assigned' => ['label' => 'Назначил(а) исполнителя', 'color' => 'bg-indigo-500'],
    'task_reassigned' => ['label' => 'Переназначил(а) задачу', 'color' => 'bg-indigo-500'],
    'comment_added' => ['label' => 'Добавил(а) комментарий', 'color' => 'bg-green-500'],
    'file_uploaded' => ['label' => 'Загрузил(а) файл', 'color' => 'bg-purple-500'],
    'task_updated' => ['label' => 'Обновил(а) задачу', 'color' => 'bg-orange-500'],
    'time_logged' => ['label' => 'внёс(ла) время', 'color' => 'bg-teal-500'],
    'time_updated' => ['label' => 'изменил(а) время', 'color' => 'bg-teal-500'],
];
?>

<div class="bg-white rounded-lg shadow-sm border p-5">
    <h3 class="text-sm font-medium text-gray-500 mb-4">История действий</h3>

    <div class="relative">
        <!-- Вертикальная линия таймлайна -->
        <div class="absolute left-2.5 top-0 bottom-0 w-0.5 bg-gray-200"></div>

        <div class="space-y-4">
            <?php foreach ($activityLog as $entry): ?>
                <?php
                $meta = $actionLabels[$entry['action_type']] ?? ['label' => $entry['action_type'], 'color' => 'bg-gray-400'];
                $timestamp = strtotime($entry['created_at'] ?? '');
                $dateFormatted = $timestamp ? date('d.m.Y H:i', $timestamp) : '';
                ?>
                <div class="relative flex items-start gap-4 pl-7">
                    <!-- Точка таймлайна -->
                    <div class="absolute left-1 top-1.5 w-3 h-3 rounded-full <?= $meta['color'] ?> border-2 border-white"></div>

                    <!-- Содержимое -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium text-gray-900"><?= e($entry['user_name'] ?? 'Система') ?></span>
                            <span class="text-gray-500"><?= e($meta['label']) ?></span>
                        </p>

                        <?php if ($entry['action_type'] === 'time_logged' && !empty($entry['new_value'])): ?>
                            <!-- Первый ввод времени: «X ч» -->
                            <p class="text-xs text-gray-400 mt-0.5">
                                <span class="text-green-600"><?= e($entry['new_value']) ?> ч</span>
                            </p>
                        <?php elseif ($entry['action_type'] === 'time_updated' && !empty($entry['new_value'])): ?>
                            <!-- Изменение времени: «X ч → Y ч» -->
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?php if (!empty($entry['old_value'])): ?>
                                    <span class="line-through text-red-400"><?= e($entry['old_value']) ?> ч</span>
                                    →
                                <?php endif; ?>
                                <span class="text-green-600"><?= e($entry['new_value']) ?> ч</span>
                            </p>
                        <?php elseif (!empty($entry['old_value']) || !empty($entry['new_value'])): ?>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <?php if (!empty($entry['old_value']) && !empty($entry['new_value'])): ?>
                                    <span class="line-through text-red-400"><?= e($entry['old_value']) ?></span>
                                    →
                                    <span class="text-green-600"><?= e($entry['new_value']) ?></span>
                                <?php elseif (!empty($entry['new_value'])): ?>
                                    <?= e($entry['new_value']) ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <p class="text-xs text-gray-400 mt-0.5"><?= e($dateFormatted) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
