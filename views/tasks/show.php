<?php
/**
 * Карточка задачи — views/tasks/show.php
 *
 * Новая структура:
 * 1. Панель кнопок сверху чата (название задачи, вкладки в модалках, действия)
 * 2. Чат на всю оставшуюся высоту страницы
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

<div class="flex flex-col h-full" x-data="{ modal: null, reassignOpen: false }">

    <!-- ===== Верхняя панель: название + кнопки модалок + действия ===== -->
    <div class="bg-white rounded-lg shadow-sm border p-3 mb-3">

        <!-- Первая строка: breadcrumb + ссылка -->
        <div class="flex items-center justify-between mb-2">
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="hover:text-blue-500">
                    <?= e($task['project_title'] ?? 'Проект') ?>
                </a>
                <?php if ($parent ?? null): ?>
                    <span>›</span>
                    <a href="<?= url('/tasks/' . (int) $parent['id']) ?>" class="hover:text-blue-500">
                        <?= e($parent['title']) ?>
                    </a>
                <?php endif; ?>
            </nav>
            <a href="<?= url('/tasks') ?>" class="text-xs text-blue-600 hover:text-blue-800">Все задачи</a>
        </div>

        <!-- Вторая строка: заголовок + статус + просрочка -->
        <div class="flex items-center gap-3 mb-2">
            <h1 class="text-lg font-bold text-gray-800 truncate"><?= e($task['title']) ?></h1>
            <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                <?= e($task['status_name'] ?? '') ?>
            </span>
            <?php if ($isOverdue): ?>
                <span class="flex-shrink-0 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">⚠ Просрочена</span>
            <?php endif; ?>
        </div>

        <!-- Третья строка: кнопки модалок + кнопки действий -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Кнопки открытия модальных окон -->
            <button @click="modal = 'subtasks'"
                    class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md text-sm hover:bg-blue-100 transition">
                Доработки <span class="text-xs opacity-70"><?= count($children) ?></span>
            </button>
            <button @click="modal = 'info'"
                    class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md text-sm hover:bg-blue-100 transition">
                Информация
            </button>
            <button @click="modal = 'history'"
                    class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md text-sm hover:bg-blue-100 transition">
                История
            </button>

            <!-- Разделитель -->
            <div class="w-px h-5 bg-gray-200 mx-1"></div>

            <!-- Кнопки действий -->
            <?php if ($canEdit): ?>
                <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>"
                   class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                    Редактировать
                </a>
            <?php endif; ?>

            <?php if (!$isClosed): ?>
                <?php if ($isExecutor && $canChangeStatus && !$isDone): ?>
                    <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status_id" value="<?= $doneStatusId ?>">
                        <button type="submit"
                                class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                            Готово
                        </button>
                    </form>
                <?php elseif (!$isExecutor && $canEdit && !$isRevision && $task['status_code'] !== 'closed'): ?>
                    <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status_id" value="<?= $revisionStatusId ?>">
                        <button type="submit"
                                class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                            Доработать
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($canEdit && !$isClosed): ?>
                <div class="relative">
                    <button @click="reassignOpen = !reassignOpen"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                        Переназначить
                    </button>
                    <div x-show="reassignOpen" @click.outside="reassignOpen = false" x-transition
                         class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border py-1 min-w-[200px] z-50"
                         style="display: none;">
                        <?php foreach ($projectUsers as $pu): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="assigned_to" value="<?= (int) $pu['id'] ?>">
                                <button type="submit"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 <?= (int)($task['assigned_to'] ?? 0) === (int)$pu['id'] ? 'text-blue-600 font-medium' : 'text-gray-700' ?>">
                                    <?= e($pu['name']) ?>
                                    <span class="text-xs text-gray-400"><?= $pu['project_role'] === 'manager' ? 'рук.' : 'исп.' ?></span>
                                    <?php if ((int)($task['assigned_to'] ?? 0) === (int)$pu['id']): ?>
                                        <span class="text-xs text-blue-500 ml-auto">✓</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($canEdit && $task['status_code'] === 'done' && !$isExecutor): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>"
                      onsubmit="return confirm('Закрыть задачу и перевести в архив?')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition"
                            <?= !$canClose['can'] ? 'disabled title="Есть незавершённые доработки"' : '' ?>>
                        Закрыть задачу
                    </button>
                </form>
            <?php endif; ?>

            <?php if (\Helpers\Auth::isAdmin() || (int) $task['created_by'] === \Helpers\Auth::id()): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/delete') ?>"
                      onsubmit="return confirm('Удалить задачу «<?= e($task['title']) ?>» и все доработки? Это действие нельзя отменить!')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                        Удалить
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Чат задачи (на всю оставшуюся высоту) ===== -->
    <div class="flex-1 min-h-0 flex flex-col">
        <?php include BASE_PATH . '/views/components/task-chat.php'; ?>
    </div>

    <!-- Редактор изображений (полноэкранный оверлей) -->
    <?php include BASE_PATH . '/views/components/image-editor.php'; ?>

    <!-- ===== Модальное окно: Доработки ===== -->
    <div x-show="modal === 'subtasks'" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="modal = null" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Доработки</h2>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto flex-1" x-data="{ showAddForm: false }">
                <!-- Кнопка добавления -->
                <?php if ($canEdit): ?>
                    <div class="mb-3">
                        <button x-show="!showAddForm" @click="showAddForm = true; $nextTick(() => $refs.subtaskInput.focus())"
                                class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">+ Добавить</button>
                        <form x-show="showAddForm" x-transition
                              method="POST" action="<?= url('/tasks/create') ?>"
                              class="flex gap-2" style="display: none;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int) $task['project_id'] ?>">
                            <input type="hidden" name="parent_id" value="<?= (int) $task['id'] ?>">
                            <input type="hidden" name="assigned_to" value="<?= (int) ($task['assigned_to'] ?? 0) ?>">
                            <input type="hidden" name="priority" value="medium">
                            <input type="text" name="title" x-ref="subtaskInput" required
                                   placeholder="Название доработки..."
                                   class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2">
                            <button type="submit"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition">Создать</button>
                            <button type="button" @click="showAddForm = false"
                                    class="px-2 py-1.5 text-gray-400 hover:text-gray-600 text-sm">✕</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (empty($children)): ?>
                    <p class="text-sm text-gray-400">Доработок нет</p>
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
            </div>
        </div>
    </div>

    <!-- ===== Модальное окно: Информация ===== -->
    <div x-show="modal === 'info'" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="modal = null" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Информация</h2>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <?php if (!empty($task['description'])): ?>
                <div>
                    <label class="text-xs text-gray-400">Описание</label>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap"><?= e($task['description']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (($task['priority'] ?? 'medium') !== 'medium'): ?>
                <div>
                    <label class="text-xs text-gray-400">Приоритет</label>
                    <div class="mt-1">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>"><?= $prio['label'] ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($task['assigned_name'])): ?>
                <div>
                    <label class="text-xs text-gray-400">Исполнитель</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['assigned_name']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($task['creator_name'])): ?>
                <div>
                    <label class="text-xs text-gray-400">Автор</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['creator_name']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($task['deadline'])): ?>
                <div>
                    <label class="text-xs text-gray-400">Срок</label>
                    <p class="text-sm mt-1 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-800' ?>">
                        <?= date('d.m.Y', strtotime($task['deadline'])) ?>
                    </p>
                </div>
                <?php endif; ?>

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
        </div>
    </div>

    <!-- ===== Модальное окно: История ===== -->
    <div x-show="modal === 'history'" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="modal = null" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">История</h2>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto flex-1">
                <?php include BASE_PATH . '/views/components/activity-log.php'; ?>
            </div>
        </div>
    </div>

</div>
