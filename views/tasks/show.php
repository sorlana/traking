<?php
/**
 * Карточка задачи — views/tasks/show.php
 *
 * Главный рабочий экран задачи:
 * информация, подзадачи, комментарии, файлы, кнопки действий.
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

// Карта приоритетов
$priorityLabels = [
    'low' => ['label' => 'Низкий', 'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high' => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
];

// Карта статусов → цвета
$statusColors = [
    'new' => 'bg-blue-100 text-blue-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'review' => 'bg-purple-100 text-purple-800',
    'done' => 'bg-green-100 text-green-800',
    'closed' => 'bg-gray-100 text-gray-800',
    'cancelled' => 'bg-red-100 text-red-800',
];

// Цвета точек статусов для дочерних задач
$statusDots = [
    'new' => 'bg-blue-500',
    'in_progress' => 'bg-yellow-500',
    'review' => 'bg-purple-500',
    'done' => 'bg-green-500',
    'closed' => 'bg-gray-400',
    'cancelled' => 'bg-red-400',
];

$isOverdue = !empty($task['deadline'])
    && strtotime($task['deadline']) < strtotime(date('Y-m-d'))
    && !in_array($task['status_code'] ?? '', ['closed', 'cancelled', 'done']);

$prio = $priorityLabels[$task['priority'] ?? 'medium'] ?? $priorityLabels['medium'];
$statusClass = $statusColors[$task['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-800';

$canEdit = can('create_task', (int) $task['project_id']);
?>

<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
        <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="hover:text-blue-600">
            <?= e($task['project_title'] ?? 'Проект') ?>
        </a>
        <?php if ($parent ?? null): ?>
            <span>→</span>
            <a href="<?= url('/tasks/' . (int) $parent['id']) ?>" class="hover:text-blue-600">
                <?= e($parent['title']) ?>
            </a>
        <?php endif; ?>
        <span>→</span>
        <span class="text-gray-800 font-medium"><?= e($task['title']) ?></span>
    </nav>

    <!-- Заголовок + действия -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= e($task['title']) ?></h1>
            <?php if ($isOverdue): ?>
                <span class="inline-block mt-1 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                    ⚠ Просрочена
                </span>
            <?php endif; ?>
        </div>

        <?php if ($canEdit): ?>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>"
               class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                Редактировать
            </a>
            <a href="<?= url('/tasks/create') ?>?project_id=<?= (int) $task['project_id'] ?>&parent_id=<?= (int) $task['id'] ?>"
               class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200 transition">
                + Подзадача
            </a>
            <?php if ($task['status_code'] !== 'closed' && $task['status_code'] !== 'cancelled'): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>"
                      onsubmit="return confirm('Закрыть задачу?')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-green-100 text-green-700 rounded-md text-sm hover:bg-green-200 transition"
                            <?= !$canClose['can'] ? 'disabled title="Есть незавершённые подзадачи"' : '' ?>>
                        Закрыть задачу
                    </button>
                </form>
            <?php endif; ?>
            <?php if (\Helpers\Auth::isAdmin() || (int) $task['created_by'] === \Helpers\Auth::id()): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/delete') ?>"
                      onsubmit="return confirm('Удалить задачу «<?= e($task['title']) ?>» и все подзадачи? Это действие нельзя отменить!')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-red-50 text-red-700 rounded-md text-sm hover:bg-red-100 transition">
                        Удалить
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Левая колонка: описание + подзадачи + комментарии -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Описание -->
            <?php if (!empty($task['description'])): ?>
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Описание</h3>
                <div class="text-gray-700 whitespace-pre-wrap"><?= e($task['description']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Подзадачи -->
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Подзадачи (<?= count($children) ?>)</h3>
                    <?php if ($canEdit): ?>
                        <a href="<?= url('/tasks/create') ?>?project_id=<?= (int) $task['project_id'] ?>&parent_id=<?= (int) $task['id'] ?>"
                           class="text-sm text-blue-600 hover:text-blue-800">+ Добавить</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($children)): ?>
                    <p class="text-sm text-gray-400">Подзадач нет</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($children as $child): ?>
                            <?php
                            $childOverdue = !empty($child['deadline'])
                                && strtotime($child['deadline']) < strtotime(date('Y-m-d'))
                                && !in_array($child['status_code'] ?? '', ['closed', 'cancelled', 'done']);
                            $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400';
                            ?>
                            <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                <a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1">
                                    <?= e($child['title']) ?>
                                </a>
                                <span class="text-xs text-gray-500 hidden sm:inline">
                                    <?= e($child['assigned_name'] ?? '') ?>
                                </span>
                                <?php if (!empty($child['deadline'])): ?>
                                    <span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?>">
                                        <?= date('d.m', strtotime($child['deadline'])) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs px-1.5 py-0.5 rounded <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= e($child['status_name'] ?? '') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Чат задачи (мессенджер-интерфейс: комментарии + файлы) -->
            <?php include BASE_PATH . '/views/components/task-chat.php'; ?>

            <!-- История действий -->
            <?php include BASE_PATH . '/views/components/activity-log.php'; ?>
        </div>

        <!-- Правая колонка: информация + действия -->
        <div class="space-y-6">

            <!-- Информация о задаче -->
            <div class="bg-white rounded-lg shadow-sm border p-5 space-y-4">
                <h3 class="text-sm font-medium text-gray-500 border-b pb-2">Информация</h3>

                <!-- Статус -->
                <div>
                    <label class="text-xs text-gray-400">Статус</label>
                    <?php if ($canEdit && $task['status_code'] !== 'closed'): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="mt-1" x-data>
                            <?= csrf_field() ?>
                            <select name="status_id" onchange="this.form.submit()"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" <?= (int) $task['status_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                        <?= e($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <div class="mt-1">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= e($task['status_name'] ?? '') ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Приоритет -->
                <div>
                    <label class="text-xs text-gray-400">Приоритет</label>
                    <div class="mt-1">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>">
                            <?= $prio['label'] ?>
                        </span>
                    </div>
                </div>

                <!-- Исполнитель -->
                <div>
                    <label class="text-xs text-gray-400">Исполнитель</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['assigned_name'] ?? 'Не назначен') ?></p>
                </div>

                <!-- Автор -->
                <div>
                    <label class="text-xs text-gray-400">Автор</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['creator_name'] ?? '—') ?></p>
                </div>

                <!-- Срок -->
                <div>
                    <label class="text-xs text-gray-400">Срок</label>
                    <p class="text-sm mt-1 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-800' ?>">
                        <?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : 'Не установлен' ?>
                    </p>
                </div>

                <!-- Создана -->
                <div>
                    <label class="text-xs text-gray-400">Создана</label>
                    <p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></p>
                </div>

                <?php if ($task['closed_at']): ?>
                <div>
                    <label class="text-xs text-gray-400">Закрыта</label>
                    <p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['closed_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Переназначение исполнителя -->
            <?php if ($canEdit && $task['status_code'] !== 'closed'): ?>
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 border-b pb-2 mb-3">Переназначить</h3>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>">
                    <?= csrf_field() ?>
                    <select name="assigned_to" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-2">
                        <option value="">Не назначен</option>
                        <?php foreach ($projectUsers as $pu): ?>
                            <option value="<?= (int) $pu['id'] ?>" <?= (int) ($task['assigned_to'] ?? 0) === (int) $pu['id'] ? 'selected' : '' ?>>
                                <?= e($pu['name']) ?> (<?= $pu['project_role'] === 'manager' ? 'руководитель' : 'исполнитель' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">
                        Назначить
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Блокирующие подзадачи (если задачу нельзя закрыть) -->
            <?php if (!$canClose['can'] && !empty($canClose['blocking'])): ?>
            <div class="bg-orange-50 rounded-lg border border-orange-200 p-4">
                <h4 class="text-sm font-medium text-orange-800 mb-2">Блокирующие подзадачи</h4>
                <p class="text-xs text-orange-600 mb-2">Задачу нельзя закрыть, пока не завершены:</p>
                <ul class="space-y-1">
                    <?php foreach (array_slice($canClose['blocking'], 0, 10) as $blocking): ?>
                        <li class="text-xs text-orange-700">
                            • <?= e($blocking['title']) ?> <span class="text-orange-500">(<?= e($blocking['status_name']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (count($canClose['blocking']) > 10): ?>
                        <li class="text-xs text-orange-500">...и ещё <?= count($canClose['blocking']) - 10 ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
