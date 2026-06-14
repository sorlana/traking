<?php
/**
 * Карточка задачи — views/tasks/show.php
 *
 * Адаптивная структура:
 * - Десктоп (lg+): Шапка + Двухколоночная сетка (Чат | Боковая панель)
 * - Мобильные/планшеты (<lg): Кнопки модалок + Чат на всю высоту
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

$priorityLabels = [
    'low'    => ['label' => 'Низкий',  'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high'   => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
];

$statusColors = [
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'revision'    => 'bg-orange-100 text-orange-800',
    'done'        => 'bg-green-100 text-green-800',
    'closed'      => 'bg-gray-100 text-gray-800',
];

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

<!-- ============================================================ -->
<!-- ДЕСКТОПНАЯ ВЕРСИЯ (lg+) -->
<!-- ============================================================ -->
<div class="hidden lg:flex flex-col h-full gap-4">

    <!-- Шапка -->
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="flex items-center justify-between mb-2">
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="hover:text-blue-500"><?= e($task['project_title'] ?? 'Проект') ?></a>
                <?php if ($parent ?? null): ?>
                    <span>›</span>
                    <a href="<?= url('/tasks/' . (int) $parent['id']) ?>" class="hover:text-blue-500"><?= e($parent['title']) ?></a>
                <?php endif; ?>
            </nav>
            <a href="<?= url('/tasks') ?>" class="text-xs text-blue-600 hover:text-blue-800">Все задачи</a>
        </div>
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <h1 class="text-xl font-bold text-gray-800 truncate"><?= e($task['title']) ?></h1>
                <?php if ($isOverdue): ?>
                    <span class="flex-shrink-0 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">⚠ Просрочена</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 flex-wrap">
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>"><?= e($task['status_name'] ?? '') ?></span>
                <div class="flex items-center gap-2" x-data="{ reassignOpen: false }">
                    <?php if ($canEdit): ?>
                        <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Редактировать</a>
                    <?php endif; ?>
                    <?php if (!$isClosed): ?>
                        <?php if ($isExecutor && $canChangeStatus && !$isDone): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                                <?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $doneStatusId ?>">
                                <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Готово</button>
                            </form>
                        <?php elseif (!$isExecutor && $canEdit && !$isRevision && $task['status_code'] !== 'closed'): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline">
                                <?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $revisionStatusId ?>">
                                <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Доработать</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($canEdit && !$isClosed): ?>
                        <div class="relative">
                            <button @click="reassignOpen = !reassignOpen" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Переназначить</button>
                            <div x-show="reassignOpen" @click.outside="reassignOpen = false" x-transition class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border py-1 min-w-[200px] z-50" style="display: none;">
                                <?php foreach ($projectUsers as $pu): ?>
                                    <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>">
                                        <?= csrf_field() ?><input type="hidden" name="assigned_to" value="<?= (int) $pu['id'] ?>">
                                        <button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 <?= (int)($task['assigned_to'] ?? 0) === (int)$pu['id'] ? 'text-blue-600 font-medium' : 'text-gray-700' ?>">
                                            <?= e($pu['name']) ?> <span class="text-xs text-gray-400"><?= $pu['project_role'] === 'manager' ? 'рук.' : 'исп.' ?></span>
                                            <?php if ((int)($task['assigned_to'] ?? 0) === (int)$pu['id']): ?><span class="text-xs text-blue-500 ml-auto">✓</span><?php endif; ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($canEdit && $task['status_code'] === 'done' && !$isExecutor): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>" onsubmit="return confirm('Закрыть задачу?')" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition" <?= !$canClose['can'] ? 'disabled title="Есть незавершённые доработки"' : '' ?>>Закрыть задачу</button>
                        </form>
                    <?php endif; ?>
                    <?php if (\Helpers\Auth::isAdmin() || (int) $task['created_by'] === \Helpers\Auth::id()): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/delete') ?>" onsubmit="return confirm('Удалить задачу и все доработки?')" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Удалить</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Двухколоночная сетка -->
    <div class="grid grid-cols-3 gap-4 flex-1 min-h-0">
        <div class="col-span-2 flex flex-col min-h-0">
            <?php if (!empty($task['description'])): ?>
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Описание</h3>
                <div class="text-gray-700 text-sm whitespace-pre-wrap"><?= e($task['description']) ?></div>
            </div>
            <?php endif; ?>
            <?php include BASE_PATH . '/views/components/task-chat.php'; ?>
            <?php include BASE_PATH . '/views/components/image-editor.php'; ?>
        </div>

        <!-- Боковая панель -->
        <div class="flex flex-col min-h-0" x-data="{ tab: 'subtasks' }">
            <div class="bg-white rounded-t-lg shadow-sm border border-b-0">
                <nav class="flex -mb-px">
                    <button @click="tab = 'subtasks'" :class="tab === 'subtasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">Доработки <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full"><?= count($children) ?></span></button>
                    <button @click="tab = 'info'" :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">Информация</button>
                    <button @click="tab = 'history'" :class="tab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 whitespace-nowrap py-3 px-2 border-b-2 font-medium text-xs text-center transition">История</button>
                </nav>
            </div>
            <!-- Доработки -->
            <div x-show="tab === 'subtasks'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4 flex-1 min-h-0 overflow-y-auto" x-data="{ showAddForm: false }">
                <?php if ($canEdit): ?>
                <div class="mb-3">
                    <button x-show="!showAddForm" @click="showAddForm = true; $nextTick(() => $refs.subtaskInput.focus())" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">+ Добавить</button>
                    <form x-show="showAddForm" x-transition method="POST" action="<?= url('/tasks/create') ?>" class="flex gap-2" style="display: none;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="project_id" value="<?= (int) $task['project_id'] ?>">
                        <input type="hidden" name="parent_id" value="<?= (int) $task['id'] ?>">
                        <input type="hidden" name="assigned_to" value="<?= (int) ($task['assigned_to'] ?? 0) ?>">
                        <input type="hidden" name="priority" value="medium">
                        <input type="text" name="title" x-ref="subtaskInput" required placeholder="Название доработки..." class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2">
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition">Создать</button>
                        <button type="button" @click="showAddForm = false" class="px-2 py-1.5 text-gray-400 hover:text-gray-600 text-sm">✕</button>
                    </form>
                </div>
                <?php endif; ?>
                <?php if (empty($children)): ?>
                    <p class="text-sm text-gray-400">Доработок нет</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($children as $child): ?>
                            <?php $childOverdue = !empty($child['deadline']) && strtotime($child['deadline']) < strtotime(date('Y-m-d')) && ($child['status_code'] ?? '') !== 'done'; $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400'; ?>
                            <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                <a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate"><?= e($child['title']) ?></a>
                                <?php if (!empty($child['deadline'])): ?><span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?> flex-shrink-0"><?= date('d.m', strtotime($child['deadline'])) ?></span><?php endif; ?>
                                <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>"><?= e($child['status_name'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Информация -->
            <div x-show="tab === 'info'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4 space-y-4 flex-1 min-h-0 overflow-y-auto">
                <?php if (($task['priority'] ?? 'medium') !== 'medium'): ?><div><label class="text-xs text-gray-400">Приоритет</label><div class="mt-1"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>"><?= $prio['label'] ?></span></div></div><?php endif; ?>
                <?php if (!empty($task['assigned_name'])): ?><div><label class="text-xs text-gray-400">Исполнитель</label><p class="text-sm text-gray-800 mt-1"><?= e($task['assigned_name']) ?></p></div><?php endif; ?>
                <?php if (!empty($task['creator_name'])): ?><div><label class="text-xs text-gray-400">Автор</label><p class="text-sm text-gray-800 mt-1"><?= e($task['creator_name']) ?></p></div><?php endif; ?>
                <?php if (!empty($task['deadline'])): ?><div><label class="text-xs text-gray-400">Срок</label><p class="text-sm mt-1 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-800' ?>"><?= date('d.m.Y', strtotime($task['deadline'])) ?></p></div><?php endif; ?>
                <div><label class="text-xs text-gray-400">Создана</label><p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></p></div>
                <?php if ($task['closed_at']): ?><div><label class="text-xs text-gray-400">Закрыта</label><p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['closed_at'])) ?></p></div><?php endif; ?>

                <!-- Затраченное время -->
                <div class="border-t pt-4">
                    <label class="text-xs text-gray-400">Затраченное время</label>
                    <?php if ($canEditTime): ?>
                        <div class="mt-1 flex items-center gap-2 js-time-container" data-task-id="<?= (int) $task['id'] ?>">
                            <!-- Режим просмотра -->
                            <span class="js-time-display text-sm text-gray-800 <?= $time_spent === null ? '' : '' ?>" <?= $time_spent !== null ? '' : 'style="display:none"' ?>><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></span>
                            <!-- Режим редактирования (скрыт если уже есть значение) -->
                            <input type="number" step="0.5" min="0.5" max="999.5"
                                   name="time_spent"
                                   value="<?= $time_spent !== null ? e($time_spent) : '' ?>"
                                   placeholder="0.5"
                                   class="js-time-input w-20 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2 <?= $time_spent !== null ? 'hidden' : '' ?>">
                            <span class="js-time-unit text-xs text-gray-400 <?= $time_spent !== null ? 'hidden' : '' ?>">ч</span>
                            <!-- Кнопка Сохранить (дискета) — видна в режиме редактирования -->
                            <button type="button" class="js-save-time p-1.5 text-blue-600 hover:text-blue-800 transition <?= $time_spent !== null ? 'hidden' : '' ?>" title="Сохранить">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <!-- Кнопка Редактировать (карандаш) — видна в режиме просмотра -->
                            <button type="button" class="js-edit-time p-1.5 text-gray-400 hover:text-blue-600 transition <?= $time_spent === null ? 'hidden' : '' ?>" title="Редактировать">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-800 mt-1"><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></p>
                    <?php endif; ?>
                    <?php if (count($children) > 0): ?>
                        <div class="mt-2">
                            <label class="text-xs text-gray-400">Суммарное время</label>
                            <p class="text-sm text-gray-800 mt-0.5"><?= $total_time > 0 ? e($total_time) . ' ч' : '—' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- История -->
            <div x-show="tab === 'history'" x-transition class="bg-white rounded-b-lg shadow-sm border border-t-0 p-4 flex-1 min-h-0 overflow-y-auto">
                <?php include BASE_PATH . '/views/components/activity-log.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- МОБИЛЬНАЯ/ПЛАНШЕТНАЯ ВЕРСИЯ (<lg) -->
<!-- ============================================================ -->
<style>
    /* Страница задачи: фиксированная высота, без прокрутки body */
    body { height: 100vh; overflow: hidden; }
    main { overflow: hidden; height: calc(100vh - 4rem - 2rem); }
</style>

<div class="lg:hidden flex flex-col h-full" x-data="{ modal: null, tab: 'subtasks', reassignOpen: false }">

    <!-- Кнопки над чатом -->
    <div class="flex items-center gap-2 mb-3">
        <button @click="modal = 'task'" class="px-3 py-1.5 bg-white border rounded-md text-sm font-medium text-gray-800 hover:bg-gray-50 shadow-sm transition">Задача</button>
        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>"><?= e($task['status_name'] ?? '') ?></span>
        <?php if ($isOverdue): ?><span class="flex-shrink-0 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">⚠</span><?php endif; ?>
        <div class="flex-1"></div>
        <button @click="modal = 'details'" class="px-3 py-1.5 bg-white border rounded-md text-sm text-gray-700 hover:bg-gray-50 shadow-sm transition">Детали</button>
    </div>

    <!-- Чат -->
    <div class="flex-1 min-h-0 flex flex-col">
        <?php include BASE_PATH . '/views/components/task-chat.php'; ?>
    </div>
    <?php include BASE_PATH . '/views/components/image-editor.php'; ?>

    <!-- Модалка: Задача -->
    <div x-show="modal === 'task'" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="modal = null" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800 truncate"><?= e($task['title']) ?></h2>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4">
                <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
                    <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="hover:text-blue-500"><?= e($task['project_title'] ?? 'Проект') ?></a>
                    <?php if ($parent ?? null): ?><span>›</span><a href="<?= url('/tasks/' . (int) $parent['id']) ?>" class="hover:text-blue-500"><?= e($parent['title']) ?></a><?php endif; ?>
                    <span class="ml-auto"><a href="<?= url('/tasks') ?>" class="text-blue-600 hover:text-blue-800">Все задачи</a></span>
                </nav>
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>"><?= e($task['status_name'] ?? '') ?></span>
                    <?php if (($task['priority'] ?? 'medium') !== 'medium'): ?><span class="px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>"><?= $prio['label'] ?></span><?php endif; ?>
                    <?php if ($isOverdue): ?><span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">⚠ Просрочена</span><?php endif; ?>
                </div>
                <?php if (!empty($task['description'])): ?><div class="mb-4"><p class="text-sm text-gray-700 whitespace-pre-wrap"><?= e($task['description']) ?></p></div><?php endif; ?>
                <div class="flex items-center gap-2 flex-wrap border-t pt-4">
                    <?php if ($canEdit): ?><a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Редактировать</a><?php endif; ?>
                    <?php if (!$isClosed): ?>
                        <?php if ($isExecutor && $canChangeStatus && !$isDone): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $doneStatusId ?>"><button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Готово</button></form>
                        <?php elseif (!$isExecutor && $canEdit && !$isRevision && $task['status_code'] !== 'closed'): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $revisionStatusId ?>"><button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Доработать</button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($canEdit && !$isClosed): ?>
                        <div class="relative">
                            <button @click="reassignOpen = !reassignOpen" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Переназначить</button>
                            <div x-show="reassignOpen" @click.outside="reassignOpen = false" x-transition class="absolute left-0 bottom-full mb-1 bg-white rounded-lg shadow-lg border py-1 min-w-[200px] z-50" style="display: none;">
                                <?php foreach ($projectUsers as $pu): ?>
                                    <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>"><?= csrf_field() ?><input type="hidden" name="assigned_to" value="<?= (int) $pu['id'] ?>"><button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 <?= (int)($task['assigned_to'] ?? 0) === (int)$pu['id'] ? 'text-blue-600 font-medium' : 'text-gray-700' ?>"><?= e($pu['name']) ?> <span class="text-xs text-gray-400"><?= $pu['project_role'] === 'manager' ? 'рук.' : 'исп.' ?></span><?php if ((int)($task['assigned_to'] ?? 0) === (int)$pu['id']): ?><span class="text-xs text-blue-500 ml-auto">✓</span><?php endif; ?></button></form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($canEdit && $task['status_code'] === 'done' && !$isExecutor): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>" onsubmit="return confirm('Закрыть задачу?')" class="inline"><?= csrf_field() ?><button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition" <?= !$canClose['can'] ? 'disabled' : '' ?>>Закрыть</button></form>
                    <?php endif; ?>
                    <?php if (\Helpers\Auth::isAdmin() || (int) $task['created_by'] === \Helpers\Auth::id()): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/delete') ?>" onsubmit="return confirm('Удалить задачу?')" class="inline"><?= csrf_field() ?><button type="submit" class="px-3 py-1.5 bg-red-50 text-red-700 rounded-md text-sm hover:bg-red-100 transition">Удалить</button></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка: Детали -->
    <div x-show="modal === 'details'" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="modal = null" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col" @click.stop>
            <div class="flex items-center justify-between border-b px-4 pt-3">
                <nav class="flex gap-4">
                    <button @click="tab = 'subtasks'" :class="tab === 'subtasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 text-sm font-medium transition">Доработки <?= count($children) ?></button>
                    <button @click="tab = 'info'" :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 text-sm font-medium transition">Информация</button>
                    <button @click="tab = 'history'" :class="tab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'" class="pb-2 border-b-2 text-sm font-medium transition">История</button>
                </nav>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-600 text-xl pb-2">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto flex-1">
                <!-- Доработки -->
                <div x-show="tab === 'subtasks'" x-data="{ showAddForm: false }">
                    <?php if ($canEdit): ?>
                    <div class="mb-3">
                        <button x-show="!showAddForm" @click="showAddForm = true; $nextTick(() => $refs.subtaskInput2.focus())" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">+ Добавить</button>
                        <form x-show="showAddForm" x-transition method="POST" action="<?= url('/tasks/create') ?>" class="flex gap-2" style="display: none;">
                            <?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int) $task['project_id'] ?>"><input type="hidden" name="parent_id" value="<?= (int) $task['id'] ?>"><input type="hidden" name="assigned_to" value="<?= (int) ($task['assigned_to'] ?? 0) ?>"><input type="hidden" name="priority" value="medium">
                            <input type="text" name="title" x-ref="subtaskInput2" required placeholder="Название доработки..." class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2">
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition">Создать</button>
                            <button type="button" @click="showAddForm = false" class="px-2 py-1.5 text-gray-400 hover:text-gray-600 text-sm">✕</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($children)): ?><p class="text-sm text-gray-400">Доработок нет</p>
                    <?php else: ?><div class="space-y-2"><?php foreach ($children as $child): ?><?php $childOverdue = !empty($child['deadline']) && strtotime($child['deadline']) < strtotime(date('Y-m-d')) && ($child['status_code'] ?? '') !== 'done'; $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400'; ?>
                        <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>"><span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span><a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate"><?= e($child['title']) ?></a><?php if (!empty($child['deadline'])): ?><span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?> flex-shrink-0"><?= date('d.m', strtotime($child['deadline'])) ?></span><?php endif; ?><span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>"><?= e($child['status_name'] ?? '') ?></span></div>
                    <?php endforeach; ?></div><?php endif; ?>
                </div>
                <!-- Информация -->
                <div x-show="tab === 'info'" class="space-y-4">
                    <?php if (!empty($task['assigned_name'])): ?><div><label class="text-xs text-gray-400">Исполнитель</label><p class="text-sm text-gray-800 mt-1"><?= e($task['assigned_name']) ?></p></div><?php endif; ?>
                    <?php if (!empty($task['creator_name'])): ?><div><label class="text-xs text-gray-400">Автор</label><p class="text-sm text-gray-800 mt-1"><?= e($task['creator_name']) ?></p></div><?php endif; ?>
                    <?php if (($task['priority'] ?? 'medium') !== 'medium'): ?><div><label class="text-xs text-gray-400">Приоритет</label><div class="mt-1"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>"><?= $prio['label'] ?></span></div></div><?php endif; ?>
                    <?php if (!empty($task['deadline'])): ?><div><label class="text-xs text-gray-400">Срок</label><p class="text-sm mt-1 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-800' ?>"><?= date('d.m.Y', strtotime($task['deadline'])) ?></p></div><?php endif; ?>
                    <div><label class="text-xs text-gray-400">Создана</label><p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></p></div>
                    <?php if ($task['closed_at']): ?><div><label class="text-xs text-gray-400">Закрыта</label><p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['closed_at'])) ?></p></div><?php endif; ?>

                    <!-- Затраченное время -->
                    <div class="border-t pt-4">
                        <label class="text-xs text-gray-400">Затраченное время</label>
                        <?php if ($canEditTime): ?>
                            <div class="mt-1 flex items-center gap-2 js-time-container" data-task-id="<?= (int) $task['id'] ?>">
                                <!-- Режим просмотра -->
                                <span class="js-time-display text-sm text-gray-800" <?= $time_spent !== null ? '' : 'style="display:none"' ?>><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></span>
                                <!-- Режим редактирования -->
                                <input type="number" step="0.5" min="0.5" max="999.5"
                                       name="time_spent"
                                       value="<?= $time_spent !== null ? e($time_spent) : '' ?>"
                                       placeholder="0.5"
                                       class="js-time-input w-20 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2 <?= $time_spent !== null ? 'hidden' : '' ?>">
                                <span class="js-time-unit text-xs text-gray-400 <?= $time_spent !== null ? 'hidden' : '' ?>">ч</span>
                                <!-- Кнопка Сохранить (галочка) -->
                                <button type="button" class="js-save-time p-1.5 text-blue-600 hover:text-blue-800 transition <?= $time_spent !== null ? 'hidden' : '' ?>" title="Сохранить">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <!-- Кнопка Редактировать (карандаш) -->
                                <button type="button" class="js-edit-time p-1.5 text-gray-400 hover:text-blue-600 transition <?= $time_spent === null ? 'hidden' : '' ?>" title="Редактировать">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-800 mt-1"><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></p>
                        <?php endif; ?>
                        <?php if (count($children) > 0): ?>
                            <div class="mt-2">
                                <label class="text-xs text-gray-400">Суммарное время</label>
                                <p class="text-sm text-gray-800 mt-0.5"><?= $total_time > 0 ? e($total_time) . ' ч' : '—' ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- История -->
                <div x-show="tab === 'history'"><?php include BASE_PATH . '/views/components/activity-log.php'; ?></div>
            </div>
        </div>
    </div>
</div>
