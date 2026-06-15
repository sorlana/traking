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
                <?php if ($canEditTime): ?>
                <!-- Таймер учёта времени -->
                <div class="flex items-center gap-1.5" x-data="taskTimer(<?= (int) $task['id'] ?>)">
                    <!-- Кнопка запуска таймера -->
                    <button x-show="!running && !paused" @click="start()" class="p-1 text-gray-400 hover:text-blue-600 transition" title="Запустить таймер">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <!-- Отсчёт времени -->
                    <span x-show="running || paused" x-cloak class="text-xs font-mono text-gray-700 tabular-nums" x-text="display"></span>
                    <!-- Пауза -->
                    <button x-show="running" x-cloak @click="pause()" class="p-1 text-yellow-500 hover:text-yellow-600 transition" title="Пауза">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <!-- Продолжить -->
                    <button x-show="paused" x-cloak @click="resume()" class="p-1 text-green-500 hover:text-green-600 transition" title="Продолжить">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <!-- Стоп -->
                    <button x-show="running || paused" x-cloak @click="stop()" class="p-1 text-red-500 hover:text-red-600 transition" title="Стоп — сохранить время">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    </button>
                </div>
                <?php endif; ?>
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
    /* Страница задачи: фиксированная высота для чата */
    @media (min-width: 1024px) {
        body { height: 100vh; overflow: hidden; }
        main { overflow: hidden; height: calc(100vh - 5rem); }
    }
    @media (max-width: 1023px) {
        body { height: 100vh; overflow: hidden; }
        main { overflow: hidden; height: calc(100vh - 2.5rem - 1rem); padding-bottom: 3.5rem !important; padding-left: 0 !important; padding-right: 0 !important; }
    }
</style>

<div class="lg:hidden flex flex-col h-full" x-data="{ tab: 'chat', reassignOpen: false }">

    <!-- Заголовок задачи -->
    <div class="flex items-center justify-between mb-2 px-4">
        <h1 class="text-lg font-bold text-gray-800 truncate"><?= e($task['title']) ?></h1>
        <a href="<?= url('/tasks') ?>" class="flex-shrink-0 text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
            Все задачи
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <!-- Строка статусов и действий -->
    <div class="flex items-center gap-2 mb-2 flex-wrap px-4">
        <?php if ($isRevision): ?>
            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs font-medium">Доработки</span>
        <?php else: ?>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>"><?= e($task['status_name'] ?? '') ?></span>
        <?php endif; ?>
        <?php if ($canEditTime): ?>
        <!-- Таймер -->
        <div class="flex items-center gap-1" x-data="taskTimer(<?= (int) $task['id'] ?>)">
            <button x-show="!running && !paused" @click="start()" class="p-0.5 text-gray-400 hover:text-blue-600" title="Таймер">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <span x-show="running || paused" x-cloak class="text-xs font-mono text-gray-700" x-text="display"></span>
            <button x-show="running" x-cloak @click="pause()" class="p-0.5 text-yellow-500" title="Пауза">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <button x-show="paused" x-cloak @click="resume()" class="p-0.5 text-green-500" title="Продолжить">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <button x-show="running || paused" x-cloak @click="stop()" class="p-0.5 text-red-500" title="Стоп">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
            </button>
        </div>
        <?php endif; ?>
        <div class="flex-1"></div>
        <?php if (!$isClosed && $isExecutor && $canChangeStatus && !$isDone): ?>
            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $doneStatusId ?>"><button type="submit" class="px-3 py-1 bg-white border rounded-md text-sm text-gray-700 hover:bg-gray-50 shadow-sm">Готово</button></form>
        <?php elseif ($canEdit): ?>
            <!-- Кнопка Действия для руководителя -->
            <div class="relative" x-data="{ actionsOpen: false }">
                <button @click="actionsOpen = !actionsOpen" class="px-3 py-1 bg-white border rounded-md text-sm text-gray-700 hover:bg-gray-50 shadow-sm">Действия</button>
                <div x-show="actionsOpen" @click.outside="actionsOpen = false" x-cloak x-transition
                     class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border py-1 min-w-[180px] z-50" style="display:none">
                    <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Редактировать</a>
                    <?php if (!$isClosed && !$isRevision && $task['status_code'] !== 'closed'): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>"><?= csrf_field() ?><input type="hidden" name="status_id" value="<?= $revisionStatusId ?>"><button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Доработать</button></form>
                    <?php endif; ?>
                    <?php if (!$isClosed): ?>
                        <div class="border-t my-1"></div>
                        <div x-data="{ reassignOpen2: false }">
                            <button @click="reassignOpen2 = !reassignOpen2" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Переназначить</button>
                            <div x-show="reassignOpen2" class="px-2 pb-1">
                                <?php foreach ($projectUsers as $pu): ?>
                                    <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>"><?= csrf_field() ?><input type="hidden" name="assigned_to" value="<?= (int) $pu['id'] ?>"><button type="submit" class="w-full text-left px-3 py-1.5 text-sm rounded hover:bg-gray-100 <?= (int)($task['assigned_to'] ?? 0) === (int)$pu['id'] ? 'text-blue-600 font-medium' : 'text-gray-600' ?>"><?= e($pu['name']) ?></button></form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($task['status_code'] === 'done'): ?>
                        <div class="border-t my-1"></div>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>" onsubmit="return confirm('Закрыть задачу?')"><?= csrf_field() ?><button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" <?= !$canClose['can'] ? 'disabled' : '' ?>>Закрыть</button></form>
                    <?php endif; ?>
                    <?php if (\Helpers\Auth::isAdmin() || (int) $task['created_by'] === \Helpers\Auth::id()): ?>
                        <div class="border-t my-1"></div>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/delete') ?>" onsubmit="return confirm('Удалить задачу?')"><?= csrf_field() ?><button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Удалить</button></form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Вкладки -->
    <div class="border-b border-gray-200 mb-2 px-4">
        <nav class="flex gap-4 -mb-px overflow-x-auto">
            <button @click="tab = 'chat'"
                    :class="tab === 'chat' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">Чат</button>
            <?php if (count($children) > 0): ?>
            <button @click="tab = 'subtasks'"
                    :class="tab === 'subtasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">Доработки <?= count($children) ?></button>
            <?php endif; ?>
            <button @click="tab = 'info'"
                    :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">Информация</button>
            <button @click="tab = 'history'"
                    :class="tab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">История</button>
        </nav>
    </div>

    <!-- Содержимое вкладок -->

    <!-- Доработки -->
    <div x-show="tab === 'subtasks'" x-cloak class="flex-1 min-h-0 overflow-y-auto px-4" x-data="{ showAddForm: false }">
        <?php if ($canEdit): ?>
        <div class="mb-3">
            <button x-show="!showAddForm" @click="showAddForm = true; $nextTick(() => $refs.mobileSubtaskInput.focus())" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">+ Добавить</button>
            <form x-show="showAddForm" x-transition method="POST" action="<?= url('/tasks/create') ?>" class="flex gap-2" style="display: none;">
                <?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int) $task['project_id'] ?>"><input type="hidden" name="parent_id" value="<?= (int) $task['id'] ?>"><input type="hidden" name="assigned_to" value="<?= (int) ($task['assigned_to'] ?? 0) ?>"><input type="hidden" name="priority" value="medium">
                <input type="text" name="title" x-ref="mobileSubtaskInput" required placeholder="Название..." class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2">
                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700">Создать</button>
                <button type="button" @click="showAddForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </form>
        </div>
        <?php endif; ?>
        <?php if (empty($children)): ?><p class="text-sm text-gray-400">Доработок нет</p>
        <?php else: ?><div class="space-y-2"><?php foreach ($children as $child): ?><?php $childOverdue = !empty($child['deadline']) && strtotime($child['deadline']) < strtotime(date('Y-m-d')) && ($child['status_code'] ?? '') !== 'done'; $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400'; ?>
            <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>"><span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span><a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate"><?= e($child['title']) ?></a><?php if (!empty($child['deadline'])): ?><span class="text-xs <?= $childOverdue ? 'text-red-600' : 'text-gray-400' ?> flex-shrink-0"><?= date('d.m', strtotime($child['deadline'])) ?></span><?php endif; ?><span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>"><?= e($child['status_name'] ?? '') ?></span></div>
        <?php endforeach; ?></div><?php endif; ?>
    </div>

    <!-- Чат -->
    <div x-show="tab === 'chat'" class="flex-1 min-h-0 flex flex-col">
        <?php include BASE_PATH . '/views/components/task-chat.php'; ?>
    </div>
    <?php include BASE_PATH . '/views/components/image-editor.php'; ?>

    <!-- Информация -->
    <div x-show="tab === 'info'" x-cloak class="flex-1 min-h-0 overflow-y-auto space-y-4 py-2 px-4">
        <?php if (!empty($task['description'])): ?><div><label class="text-xs text-gray-400">Описание</label><p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap"><?= e($task['description']) ?></p></div><?php endif; ?>
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
                    <span class="js-time-display text-sm text-gray-800" <?= $time_spent !== null ? '' : 'style="display:none"' ?>><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></span>
                    <input type="number" step="0.5" min="0.5" max="999.5" name="time_spent" value="<?= $time_spent !== null ? e($time_spent) : '' ?>" placeholder="0.5" class="js-time-input w-20 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2 <?= $time_spent !== null ? 'hidden' : '' ?>">
                    <span class="js-time-unit text-xs text-gray-400 <?= $time_spent !== null ? 'hidden' : '' ?>">ч</span>
                    <button type="button" class="js-save-time p-1.5 text-blue-600 hover:text-blue-800 transition <?= $time_spent !== null ? 'hidden' : '' ?>" title="Сохранить"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button>
                    <button type="button" class="js-edit-time p-1.5 text-gray-400 hover:text-blue-600 transition <?= $time_spent === null ? 'hidden' : '' ?>" title="Редактировать"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-800 mt-1"><?= $time_spent !== null ? e($time_spent) . ' ч' : '—' ?></p>
            <?php endif; ?>
            <?php if (count($children) > 0): ?>
                <div class="mt-2"><label class="text-xs text-gray-400">Суммарное время</label><p class="text-sm text-gray-800 mt-0.5"><?= $total_time > 0 ? e($total_time) . ' ч' : '—' ?></p></div>
            <?php endif; ?>
        </div>
        <!-- Действия -->
        <div class="border-t pt-4 flex flex-wrap gap-2">
            <?php if ($canEdit): ?><a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Редактировать</a><?php endif; ?>
            <?php if ($canEdit && !$isClosed): ?>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Переназначить</button>
                    <div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 bottom-full mb-1 bg-white rounded-lg shadow-lg border py-1 min-w-[200px] z-50" style="display: none;">
                        <?php foreach ($projectUsers as $pu): ?>
                            <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>"><?= csrf_field() ?><input type="hidden" name="assigned_to" value="<?= (int) $pu['id'] ?>"><button type="submit" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 <?= (int)($task['assigned_to'] ?? 0) === (int)$pu['id'] ? 'text-blue-600 font-medium' : 'text-gray-700' ?>"><?= e($pu['name']) ?></button></form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($canEdit && $task['status_code'] === 'done' && !$isExecutor): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>" onsubmit="return confirm('Закрыть задачу?')" class="inline"><?= csrf_field() ?><button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200" <?= !$canClose['can'] ? 'disabled' : '' ?>>Закрыть</button></form>
            <?php endif; ?>
        </div>
    </div>

    <!-- История -->
    <div x-show="tab === 'history'" x-cloak class="flex-1 min-h-0 overflow-y-auto py-2 px-4">
        <?php include BASE_PATH . '/views/components/activity-log.php'; ?>
    </div>
</div>
