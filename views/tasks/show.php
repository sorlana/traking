<?php
/**
 * Карточка задачи — views/tasks/show.php
 *
 * Новая структура layout:
 * 1. Breadcrumb
 * 2. Шапка: Заголовок + Switch (Доработки/Готово) + кнопки действий
 * 3. Двухколоночная сетка:
 *    - Левая (2/3): Описание + Чат задачи
 *    - Правая (1/3): Вкладки (Подзадачи, Информация, История)
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

// Карта приоритетов
$priorityLabels = [
    'low'    => ['label' => 'Низкий',  'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high'   => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
];

// Карта статусов → цвета
$statusColors = [
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'revision'    => 'bg-orange-100 text-orange-800',
    'done'        => 'bg-green-100 text-green-800',
    'closed'      => 'bg-gray-100 text-gray-800',
];

// Цвета точек статусов для дочерних задач
$statusDots = [
    'in_progress' => 'bg-yellow-500',
    'revision'    => 'bg-orange-500',
    'done'        => 'bg-green-500',
    'closed'      => 'bg-gray-400',
];

$isOverdue = !empty($task['deadline'])
    && strtotime($task['deadline']) < strtotime(date('Y-m-d'))
    && !in_array($task['status_code'] ?? '', ['done', 'closed']);

$prio = $priorityLabels[$task['priority'] ?? 'medium'] ?? $priorityLabels['medium'];
$statusClass = $statusColors[$task['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-800';

$canEdit = can('create_task', (int) $task['project_id']);
// Исполнитель может менять статус своей задачи
$canChangeStatus = $canEdit || ((int)($task['assigned_to'] ?? 0) === \Helpers\Auth::id());
$isExecutor = \Helpers\Auth::isExecutor();

// ID статусов для switch
$doneStatusId = 0;
$revisionStatusId = 0;
foreach ($statuses as $s) {
    if ($s['code'] === 'done') $doneStatusId = (int) $s['id'];
    if ($s['code'] === 'revision') $revisionStatusId = (int) $s['id'];
}
$isDone = ($task['status_code'] === 'done');
$isRevision = ($task['status_code'] === 'revision');
$isClosed = ($task['status_code'] === 'closed');
?>

<div class="space-y-6">

    <!-- ===== Breadcrumb ===== -->
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

    <!-- ===== Шапка: Заголовок + Switch + Кнопки действий ===== -->
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <!-- Заголовок + бейдж просрочки -->
            <div class="flex items-center gap-3 min-w-0">
                <h1 class="text-xl font-bold text-gray-800 truncate"><?= e($task['title']) ?></h1>
                <?php if ($isOverdue): ?>
                    <span class="flex-shrink-0 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                        ⚠ Просрочена
                    </span>
                <?php endif; ?>
            </div>

            <!-- Switch статуса + Кнопки действий -->
            <div class="flex items-center gap-4 flex-shrink-0 flex-wrap">

                <!-- Switch: Доработки / Готово -->
                <?php if ($isClosed): ?>
                    <!-- Задача закрыта — бейдж «Сделано» -->
                    <span class="inline-block px-3 py-1.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                        Сделано
                    </span>
                <?php elseif ($canChangeStatus): ?>
                    <div class="inline-flex rounded-full overflow-hidden border border-gray-200 shadow-sm">
                        <?php if ($isExecutor): ?>
                            <!-- Исполнитель: левая серая, правая — зелёная -->
                            <span class="px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-400">
                                Доработки
                            </span>
                            <?php if (!$isDone): ?>
                                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status_id" value="<?= $doneStatusId ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-green-500 hover:bg-green-600 text-white transition cursor-pointer">
                                        Готово
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-xs font-medium bg-green-500 text-white">
                                    Готово
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Руководитель: левая — оранжевая, правая серая -->
                            <?php if (!$isRevision): ?>
                                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status_id" value="<?= $revisionStatusId ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-orange-500 hover:bg-orange-600 text-white transition cursor-pointer">
                                        Доработки
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-xs font-medium bg-orange-500 text-white">
                                    Доработки ✓
                                </span>
                            <?php endif; ?>
                            <span class="px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-400">
                                Готово
                            </span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Нет прав — просто бейдж -->
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                        <?= e($task['status_name'] ?? '') ?>
                    </span>
                <?php endif; ?>

                <!-- Кнопки действий -->
                <?php if ($canEdit): ?>
                    <div class="flex items-center gap-2">
                        <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>"
                           class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                            Редактировать
                        </a>
                        <?php if ($task['status_code'] === 'done' && !$isExecutor): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>"
                                  onsubmit="return confirm('Закрыть задачу и перевести в архив?')" class="inline">
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
        </div>
    </div>

    <!-- ===== Двухколоночная сетка ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ===== Левая колонка (2/3): Описание + Чат ===== -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Описание -->
            <?php if (!empty($task['description'])): ?>
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Описание</h3>
                <div class="text-gray-700 whitespace-pre-wrap"><?= e($task['description']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Чат задачи (мессенджер-интерфейс: комментарии + файлы) -->
            <?php include BASE_PATH . '/views/components/task-chat.php'; ?>
        </div>

        <!-- ===== Правая колонка (1/3): Вкладки ===== -->
        <div class="space-y-0" x-data="{ tab: 'subtasks' }">

            <!-- Навигация вкладок -->
            <div class="bg-white rounded-t-lg shadow-sm border border-b-0">
                <nav class="flex -mb-px">
                    <button @click="tab = 'subtasks'"
                            :class="tab === 'subtasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">
                        Подзадачи
                        <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full"><?= count($children) ?></span>
                    </button>
                    <button @click="tab = 'info'"
                            :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">
                        Информация
                    </button>
                    <button @click="tab = 'history'"
                            :class="tab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">
                        История
                    </button>
                </nav>
            </div>

            <!-- ============================================================ -->
            <!-- Вкладка: Подзадачи -->
            <!-- ============================================================ -->
            <div x-show="tab === 'subtasks'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4">
                <!-- Кнопка добавления -->
                <?php if ($canEdit): ?>
                    <div class="mb-3">
                        <a href="<?= url('/tasks/create') ?>?project_id=<?= (int) $task['project_id'] ?>&parent_id=<?= (int) $task['id'] ?>"
                           class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Добавить</a>
                    </div>
                <?php endif; ?>

                <?php if (empty($children)): ?>
                    <p class="text-sm text-gray-400">Подзадач нет</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($children as $child): ?>
                            <?php
                            $childOverdue = !empty($child['deadline'])
                                && strtotime($child['deadline']) < strtotime(date('Y-m-d'))
                                && ($child['status_code'] ?? '') !== 'done';
                            $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400';
                            ?>
                            <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                <a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate">
                                    <?= e($child['title']) ?>
                                </a>
                                <?php if (!empty($child['deadline'])): ?>
                                    <span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?> flex-shrink-0">
                                        <?= date('d.m', strtotime($child['deadline'])) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= e($child['status_name'] ?? '') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Блокирующие подзадачи (если задачу нельзя закрыть) -->
                <?php if (!$canClose['can'] && !empty($canClose['blocking'])): ?>
                <div class="mt-4 bg-orange-50 rounded-lg border border-orange-200 p-3">
                    <h4 class="text-xs font-medium text-orange-800 mb-1">Блокирующие подзадачи</h4>
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

            <!-- ============================================================ -->
            <!-- Вкладка: Информация -->
            <!-- ============================================================ -->
            <div x-show="tab === 'info'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4 space-y-4">

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

                <!-- Переназначение исполнителя -->
                <?php if ($canEdit && $task['status_code'] !== 'done'): ?>
                <div class="pt-3 border-t">
                    <label class="text-xs text-gray-400 mb-2 block">Переназначить</label>
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
            </div>

            <!-- ============================================================ -->
            <!-- Вкладка: История -->
            <!-- ============================================================ -->
            <div x-show="tab === 'history'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4">
                <?php include BASE_PATH . '/views/components/activity-log.php'; ?>
            </div>

        </div>
    </div>
</div>
