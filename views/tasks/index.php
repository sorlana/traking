<?php
/**
 * Список задач — views/tasks/index.php
 *
 * Показывает таблицу задач. Фильтры открываются в модальном окне.
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

$priorityLabels = [
    'low' => ['label' => 'Низкий', 'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-gray-100 text-gray-700'],
    'high' => ['label' => 'Высокий', 'class' => 'bg-gray-100 text-gray-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-gray-100 text-gray-700'],
];

$statusColors = [
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'revision' => 'bg-orange-100 text-orange-800',
    'done' => 'bg-green-100 text-green-800',
    'closed' => 'bg-indigo-100 text-indigo-800',
];

// Проверка: есть ли активные фильтры
$hasFilters = !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['assigned_to']) || !empty($filters['deadline']) || !empty($filters['project_id']);

$createPriorityOptions = [
    'low' => 'Низкий',
    'medium' => 'Средний',
    'high' => 'Высокий',
    'urgent' => 'Срочный',
];
$createPriorityValue = 'medium';

$createProjectOptions = [];
foreach ($projects ?? [] as $p) {
    $createProjectOptions[(string) $p['id']] = $p['title'];
}

$selectedFilterProjectId = (string) ($filters['project_id'] ?? '');
$createProjectValue = '';
$createProjectLabel = 'Выберите проект';
if ($project ?? null) {
    $createProjectValue = (string) $project['id'];
    $createProjectLabel = $project['title'];
} elseif ($selectedFilterProjectId !== '' && isset($createProjectOptions[$selectedFilterProjectId])) {
    $createProjectValue = $selectedFilterProjectId;
    $createProjectLabel = $createProjectOptions[$selectedFilterProjectId];
}

$createExecutorOptions = ['' => 'Не назначен'];
foreach ($executors ?? [] as $exec) {
    $createExecutorOptions[(string) $exec['id']] = $exec['name'];
}

// Автовыбор исполнителя: если в проекте один исполнитель, выбрать его
$createExecutorValue = '';
$createExecutorLabel = 'Не назначен';
if ($project ?? null) {
    // Проект определён — ищем исполнителей проекта
    $db = \Helpers\Database::getInstance();
    $projectExecutors = $db->fetchAll(
        "SELECT u.id, u.name FROM project_users pu JOIN users u ON pu.user_id = u.id WHERE pu.project_id = ? AND pu.project_role = 'executor'",
        [(int) $project['id']]
    );
    if (count($projectExecutors) === 1) {
        $createExecutorValue = (string) $projectExecutors[0]['id'];
        $createExecutorLabel = $projectExecutors[0]['name'];
    }
} else {
    // Проект не выбран — если в системе один исполнитель (role_id=3), выбрать его
    $db = \Helpers\Database::getInstance();
    $allExecutors = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' AND role_id = 3");
    if (count($allExecutors) === 1) {
        $createExecutorValue = (string) $allExecutors[0]['id'];
        $createExecutorLabel = $allExecutors[0]['name'];
    }
}

$editableTaskIds = [];
$deletableTaskIds = [];
foreach ($tasks ?? [] as $listedTask) {
    $listedTaskId = (int) $listedTask['id'];
    if (can('create_task', (int) $listedTask['project_id'])) {
        $editableTaskIds[$listedTaskId] = true;
    }
    if ($roleId === 1 || (int) $listedTask['created_by'] === (int) ($currentUser['id'] ?? 0)) {
        $deletableTaskIds[$listedTaskId] = true;
    }
}
$hasTaskActions = !empty($editableTaskIds) || !empty($deletableTaskIds);
$taskFilterQuery = http_build_query(array_filter(
    $filters ?? [],
    static fn(mixed $value): bool => $value !== '' && $value !== null
));
$tasksReturnUrl = '/tasks' . ($taskFilterQuery !== '' ? '?' . $taskFilterQuery : '');
?>

<div class="space-y-4 lg:flex lg:h-[calc(100vh-6rem)] lg:min-h-0 lg:flex-col lg:gap-4 lg:space-y-0 lg:overflow-hidden"
     x-data="{
         showFilters: false,
         showCreate: false,
         showCreateExtra: false,
         showEdit: false,
         showEditExtra: false,
         editTask: {},
         expandedTasks: {},
         selectedTasks: [],
         deletableTaskIds: <?= json_encode(array_map('intval', array_keys($deletableTaskIds))) ?>,
         toggleTask(id) { this.expandedTasks[id] = !this.expandedTasks[id] },
         taskVisible(ancestorIds) { return ancestorIds.every(id => this.expandedTasks[id]) },
         toggleAllTasks(checked) { this.selectedTasks = checked ? [...this.deletableTaskIds] : [] },
         openEdit(task) {
             this.editTask = { ...task };
             this.showCreate = false;
             this.showEditExtra = false;
             this.showFilters = false;
             this.showEdit = true;
             this.$nextTick(() => this.$refs.editTaskTitle?.focus());
         }
     }"
     @keydown.escape.window="showFilters = false; showCreate = false; showEdit = false">
    <!-- Заголовок + Создать + Фильтры (мобильная кнопка) -->
    <div class="flex items-center justify-between gap-4 lg:flex-shrink-0">
        <h1 class="text-xl font-bold text-gray-800">
            <?php if ($project ?? null): ?>
                Задачи «<?= e($project['title']) ?>»
            <?php else: ?>
                Задачи
            <?php endif; ?>
        </h1>

        <div class="flex items-center gap-2">
            <?php if (can('create_task', $project['id'] ?? null)): ?>
                <button type="button"
                        @click="showCreate = true; showCreateExtra = false; showEdit = false; showFilters = false; $nextTick(() => $refs.createTaskTitle?.focus())"
                        class="ui-btn ui-btn-primary">
                    Создать
                </button>
            <?php endif; ?>
            <button type="button" @click="showFilters = true; showCreate = false"
                    class="lg:hidden ui-btn ui-btn-light <?= $hasFilters ? 'border-blue-500 text-blue-700' : '' ?>">
                Фильтры<?= $hasFilters ? ' ●' : '' ?>
            </button>
        </div>
    </div>

    <!-- Десктопные фильтры и массовые действия (lg+) -->
    <div class="hidden items-end gap-4 rounded-lg border bg-white p-4 shadow-sm lg:flex lg:flex-shrink-0">
        <form method="GET" action="<?= url('/tasks') ?>" class="flex min-w-0 flex-1 flex-wrap items-end gap-3">
            <div class="w-28">
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= ($filters['status'] ?? '') === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                <select name="priority" class="ui-control">
                    <option value="">Все</option>
                    <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Низкий</option>
                    <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Высокий</option>
                    <option value="urgent" <?= ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Срочный</option>
                </select>
            </div>
            <?php if ($roleId <= 2): ?>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="assigned_to" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($executors as $exec): ?>
                        <option value="<?= (int) $exec['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $exec['id'] ? 'selected' : '' ?>><?= e($exec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="ui-control">
                    <option value="">Все</option>
                    <option value="today" <?= ($filters['deadline'] ?? '') === 'today' ? 'selected' : '' ?>>Сегодня</option>
                    <option value="week" <?= ($filters['deadline'] ?? '') === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                    <option value="overdue" <?= ($filters['deadline'] ?? '') === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                </select>
            </div>
            <?php if (!($project ?? null) && !empty($projects)): ?>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                <select name="project_id" class="ui-control">
                    <option value="">Все проекты</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($filters['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="flex items-end gap-1">
                <button type="submit" class="ui-btn ui-btn-primary flex h-11 w-11 items-center justify-center px-0"
                        aria-label="Применить фильтры" title="Применить фильтры">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L14 13.667V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.333L3.2 4.6A1 1 0 013 4z"/>
                    </svg>
                </button>
                <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
                   class="ui-btn ui-btn-secondary flex h-11 w-11 items-center justify-center px-0"
                   aria-label="Сбросить фильтры" title="Сбросить фильтры">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
            <?php if ($project ?? null): ?><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>"><?php endif; ?>
        </form>

        <?php if (!empty($deletableTaskIds) && !empty($tasks)): ?>
            <form method="POST" action="<?= url('/tasks/delete') ?>"
                  data-confirm-delete="Удалить выбранные задачи и все вложенные элементы? Это действие нельзя отменить."
                  class="flex flex-shrink-0 items-center gap-2 border-l pl-4">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($tasksReturnUrl) ?>">
                <template x-for="taskId in selectedTasks" :key="'desktop-selected-' + taskId">
                    <input type="hidden" name="task_ids[]" :value="taskId">
                </template>
                <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-xs text-gray-600">
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600"
                           :checked="deletableTaskIds.length > 0 && selectedTasks.length === deletableTaskIds.length"
                           @change="toggleAllTasks($event.target.checked)">
                    Выбрать все
                </label>
                <button type="submit" :disabled="selectedTasks.length === 0" class="ui-btn ui-btn-subtle whitespace-nowrap">
                    Удалить выбранные <span class="ui-btn-count" x-text="selectedTasks.length">0</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Таблица задач -->
    <?php if (empty($tasks)): ?>
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <p class="text-gray-500">Задачи не найдены</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
            <?php if (!empty($deletableTaskIds)): ?>
                <form method="POST" action="<?= url('/tasks/delete') ?>"
                      data-confirm-delete="Удалить выбранные задачи и все вложенные элементы? Это действие нельзя отменить."
                      class="flex h-[53px] items-center justify-between gap-3 border-b bg-white px-4 lg:hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e($tasksReturnUrl) ?>">
                    <template x-for="taskId in selectedTasks" :key="'mobile-selected-' + taskId">
                        <input type="hidden" name="task_ids[]" :value="taskId">
                    </template>
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox"
                               class="h-4 w-4 rounded border-gray-300 text-blue-600"
                               :checked="deletableTaskIds.length > 0 && selectedTasks.length === deletableTaskIds.length"
                               @change="toggleAllTasks($event.target.checked)">
                        Выбрать все
                    </label>
                    <button type="submit" :disabled="selectedTasks.length === 0"
                            class="ui-btn ui-btn-subtle">
                        Удалить выбранные <span class="ui-btn-count" x-text="selectedTasks.length">0</span>
                    </button>
                </form>
            <?php endif; ?>
            <div class="task-table-scroll overflow-x-auto lg:min-h-0 lg:flex-1 lg:overflow-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-gray-50 lg:sticky lg:top-0 lg:z-10">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Название</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Статус</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Приоритет</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Исполнитель</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Срок</th>
                            <?php if (!($project ?? null)): ?>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden xl:table-cell">Проект</th>
                            <?php endif; ?>
                            <?php if ($hasTaskActions): ?>
                                <th class="w-20 px-2 py-3"><span class="sr-only">Действия</span></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $treeDepth = (int) ($task['tree_depth'] ?? 0);
                            $treeAncestors = $task['tree_ancestor_ids'] ?? [];
                            $treeHasChildren = !empty($task['tree_has_children']);
                            $isOverdue = !empty($task['deadline'])
                                && strtotime($task['deadline']) < strtotime(date('Y-m-d'))
                                && ($task['status_code'] ?? '') !== 'done';
                            $prio = $priorityLabels[$task['priority'] ?? 'medium'] ?? $priorityLabels['medium'];
                            $statusClass = $statusColors[$task['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <?php
                            $taskId = (int) $task['id'];
                            $canEditTask = isset($editableTaskIds[$taskId]);
                            $canDeleteTask = isset($deletableTaskIds[$taskId]);
                            $rowBackground = $treeDepth > 0 ? 'bg-gray-50' : ($isOverdue ? 'bg-red-50' : '');
                            ?>
                            <tr x-show='taskVisible(<?= json_encode($treeAncestors) ?>)'
                                <?= $treeDepth > 0 ? 'x-cloak style="display:none"' : '' ?>
                                class="transition hover:bg-gray-100 <?= $rowBackground ?>">
                                <td class="px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-1" style="padding-left: <?= $treeDepth * 20 ?>px">
                                        <?php if ($treeHasChildren): ?>
                                            <button type="button"
                                                    @click.stop="toggleTask(<?= (int) $task['id'] ?>)"
                                                    :aria-expanded="Boolean(expandedTasks[<?= (int) $task['id'] ?>])"
                                                    class="-ml-1 flex h-6 w-6 flex-shrink-0 items-center justify-center p-0 text-gray-500 hover:text-black"
                                                    aria-label="Показать или скрыть вложенные задачи">
                                                <svg class="h-4 w-4 transition-transform"
                                                     :class="{ 'rotate-180': expandedTasks[<?= (int) $task['id'] ?>] }"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"/>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="h-6 w-6 flex-shrink-0" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <?php if ($canDeleteTask): ?>
                                            <input type="checkbox" value="<?= $taskId ?>" x-model.number="selectedTasks"
                                                   class="mx-1 h-4 w-4 flex-shrink-0 rounded border-gray-300 text-blue-600"
                                                   aria-label="Выбрать задачу <?= e($task['title']) ?>">
                                        <?php elseif (!empty($deletableTaskIds)): ?>
                                            <span class="mx-1 h-4 w-4 flex-shrink-0" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <a href="<?= url('/tasks/' . (int) $task['id']) ?>" class="font-medium text-blue-600 hover:text-blue-800">
                                                <?= e($task['title']) ?>
                                            </a>
                                            <?php if ($isOverdue): ?>
                                                <span class="ml-1 text-xs font-medium text-red-600">Просрочено</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                        <?= e($task['status_name'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium <?= $prio['class'] ?>">
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
                                <?php if ($hasTaskActions): ?>
                                    <td class="px-2 py-1 text-right">
                                        <?php if ($canEditTask || $canDeleteTask): ?>
                                            <div class="flex items-center justify-end -space-x-3">
                                                <?php if ($canEditTask): ?>
                                                    <button type="button"
                                                            @click='openEdit(<?= json_encode([
                                                                'id' => $taskId,
                                                                'title' => $task['title'],
                                                                'description' => $task['description'] ?? '',
                                                                'status_id' => (int) $task['status_id'],
                                                                'priority' => $task['priority'] ?? 'medium',
                                                                'deadline' => $task['deadline'] ?? '',
                                                                'assigned_to' => $task['assigned_to'] ?? '',
                                                                'project_title' => $task['project_title'] ?? ($project['title'] ?? ''),
                                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'
                                                            class="a11y-icon-button text-gray-400 hover:text-black"
                                                            aria-label="Редактировать задачу <?= e($task['title']) ?>" title="Редактировать">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteTask): ?>
                                                    <form method="POST" action="<?= url('/tasks/' . $taskId . '/delete') ?>"
                                                          data-confirm-delete="<?= e('Удалить задачу «' . $task['title'] . '» и все вложенные элементы? Это действие нельзя отменить.') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="redirect_to" value="<?= e($tasksReturnUrl) ?>">
                                                        <button type="submit" class="a11y-icon-button text-gray-400 hover:text-black"
                                                                aria-label="Удалить задачу <?= e($task['title']) ?>" title="Удалить">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-sm text-gray-400 lg:flex-shrink-0">Найдено задач: <?= count($tasks) ?></p>
    <?php endif; ?>

    <!-- Модалка: Создание задачи -->
    <div x-show="showCreate" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="create-task-title"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showCreate = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[86vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 id="create-task-title" class="text-lg font-bold text-gray-800">Новая задача</h2>
                <button type="button" @click="showCreate = false" class="a11y-icon-button text-gray-400 hover:text-gray-600 text-xl" aria-label="Закрыть окно создания задачи">&times;</button>
            </div>
            <form method="POST" action="<?= url('/tasks/create') ?>" class="p-4 space-y-4" data-mobile-form-validation>
                <?= csrf_field() ?>

                <?php if ($project ?? null): ?>
                    <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                        <div class="ui-control bg-gray-50 text-gray-700">
                            <?= e($createProjectLabel) ?>
                        </div>
                    </div>
                <?php elseif (!empty($createProjectOptions)): ?>
                    <div class="mobile-filter-field" data-required-mobile-select>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Проект <span class="text-red-500">*</span></label>
                        <input type="hidden" name="project_id" value="<?= e($createProjectValue) ?>">
                        <details class="mobile-filter-details">
                            <summary class="mobile-filter-trigger">
                                <span class="mobile-filter-label"><?= e($createProjectLabel) ?></span>
                                <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mobile-filter-menu">
                                <?php foreach ($createProjectOptions as $value => $label): ?>
                                    <button type="button" class="mobile-filter-option <?= $createProjectValue === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="create_task_title" class="block text-xs font-medium text-gray-500 mb-1">Название <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="create_task_title" x-ref="createTaskTitle" required maxlength="255"
                           placeholder="Название задачи"
                           class="ui-control">
                </div>

                <?php if ($roleId <= 2): ?>
                    <div class="mobile-filter-field">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                        <input type="hidden" name="assigned_to" value="<?= e($createExecutorValue) ?>">
                        <details class="mobile-filter-details">
                            <summary class="mobile-filter-trigger">
                                <span class="mobile-filter-label"><?= e($createExecutorLabel) ?></span>
                                <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mobile-filter-menu">
                                <?php foreach ($createExecutorOptions as $value => $label): ?>
                                    <button type="button" class="mobile-filter-option <?= (string) $value === $createExecutorValue ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <button type="button" @click="showCreateExtra = !showCreateExtra"
                        class="flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800"
                        :aria-expanded="showCreateExtra">
                    <span x-text="showCreateExtra ? 'Скрыть' : 'Дополнительно'">Дополнительно</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showCreateExtra }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="showCreateExtra" x-cloak class="space-y-4" style="display: none;">
                    <div>
                        <label for="create_task_description" class="mb-1 block text-xs font-medium text-gray-500">Описание</label>
                        <textarea name="description" id="create_task_description" rows="3" placeholder="Короткое описание"
                                  class="ui-control"></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="mobile-filter-field">
                            <label class="mb-1 block text-xs font-medium text-gray-500">Приоритет</label>
                            <input type="hidden" name="priority" value="<?= e($createPriorityValue) ?>">
                            <details class="mobile-filter-details">
                                <summary class="mobile-filter-trigger">
                                    <span class="mobile-filter-label"><?= e($createPriorityOptions[$createPriorityValue] ?? 'Средний') ?></span>
                                    <svg class="mobile-filter-arrow h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </summary>
                                <div class="mobile-filter-menu">
                                    <?php foreach ($createPriorityOptions as $value => $label): ?>
                                        <button type="button" class="mobile-filter-option <?= $createPriorityValue === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </div>

                        <div>
                            <label for="create_task_deadline" class="mb-1 block text-xs font-medium text-gray-500">Срок</label>
                            <input type="date" name="deadline" id="create_task_deadline" class="ui-control">
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2 border-t">
                    <button type="submit" class="ui-btn ui-btn-primary">Создать</button>
                    <button type="button" @click="showCreate = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка: Редактирование задачи -->
    <div x-show="showEdit" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="edit-task-title"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
         @click.self="showEdit = false" style="display: none;">
        <div class="max-h-[86vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl" @click.stop>
            <div class="flex items-center justify-between border-b p-4">
                <h2 id="edit-task-title" class="text-lg font-bold text-gray-800">Редактирование задачи</h2>
                <button type="button" @click="showEdit = false"
                        class="a11y-icon-button text-xl text-gray-400 hover:text-black"
                        aria-label="Закрыть окно редактирования задачи">&times;</button>
            </div>
            <form method="POST" :action="'<?= url('/tasks') ?>/' + editTask.id + '/edit'"
                  class="space-y-4 p-4" data-mobile-form-validation>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($tasksReturnUrl) ?>">

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Проект</label>
                    <div class="ui-control bg-gray-50 text-gray-700" x-text="editTask.project_title"></div>
                </div>

                <div>
                    <label for="edit_task_title_input" class="mb-1 block text-xs font-medium text-gray-500">
                        Название <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_task_title_input" name="title" x-ref="editTaskTitle"
                           x-model="editTask.title" required maxlength="255" class="ui-control">
                </div>

                <?php if ($roleId <= 2): ?>
                    <div>
                        <label for="edit_task_assigned" class="mb-1 block text-xs font-medium text-gray-500">Исполнитель</label>
                        <select id="edit_task_assigned" name="assigned_to" x-model="editTask.assigned_to" class="ui-control">
                            <?php foreach ($createExecutorOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="assigned_to" :value="editTask.assigned_to">
                <?php endif; ?>

                <button type="button" @click="showEditExtra = !showEditExtra"
                        class="flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800"
                        :aria-expanded="showEditExtra">
                    <span x-text="showEditExtra ? 'Скрыть' : 'Дополнительно'">Дополнительно</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showEditExtra }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="showEditExtra" x-cloak class="space-y-4" style="display: none;">
                    <div>
                        <label for="edit_task_description" class="mb-1 block text-xs font-medium text-gray-500">Описание</label>
                        <textarea id="edit_task_description" name="description" x-model="editTask.description"
                                  rows="3" placeholder="Короткое описание" class="ui-control"></textarea>
                    </div>

                    <div>
                        <label for="edit_task_status" class="mb-1 block text-xs font-medium text-gray-500">Статус</label>
                        <select id="edit_task_status" name="status_id" x-model="editTask.status_id" required class="ui-control">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= (int) $status['id'] ?>"><?= e($status['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="edit_task_priority" class="mb-1 block text-xs font-medium text-gray-500">Приоритет</label>
                            <select id="edit_task_priority" name="priority" x-model="editTask.priority" class="ui-control">
                                <?php foreach ($createPriorityOptions as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="edit_task_deadline" class="mb-1 block text-xs font-medium text-gray-500">Срок</label>
                            <input type="date" id="edit_task_deadline" name="deadline" x-model="editTask.deadline" class="ui-control">
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t pt-4">
                    <button type="submit" class="ui-btn ui-btn-primary">Сохранить</button>
                    <button type="button" @click="showEdit = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка: Фильтры -->
    <div x-show="showFilters" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="task-filters-title"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showFilters = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[82vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 id="task-filters-title" class="text-lg font-bold text-gray-800">Фильтры</h2>
                <button type="button" @click="showFilters = false" class="a11y-icon-button text-gray-400 hover:text-gray-600 text-xl" aria-label="Закрыть фильтры">&times;</button>
            </div>
            <?php
            $mobileStatusOptions = ['' => 'Все'];
            foreach ($statuses as $s) {
                $mobileStatusOptions[$s['code']] = $s['name'];
            }
            $mobilePriorityOptions = [
                '' => 'Все',
                'low' => 'Низкий',
                'medium' => 'Средний',
                'high' => 'Высокий',
                'urgent' => 'Срочный',
            ];
            $mobileDeadlineOptions = [
                '' => 'Все',
                'today' => 'Сегодня',
                'week' => 'На этой неделе',
                'overdue' => 'Просроченные',
            ];
            $mobileExecutorOptions = ['' => 'Все'];
            foreach ($executors as $exec) {
                $mobileExecutorOptions[(string) $exec['id']] = $exec['name'];
            }
            $mobileProjectOptions = ['' => 'Все проекты'];
            foreach ($projects ?? [] as $p) {
                $mobileProjectOptions[(string) $p['id']] = $p['title'];
            }
            $mobileJsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
            ?>
            <form method="GET" action="<?= url('/tasks') ?>" class="p-4 space-y-4">
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                    <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileStatusOptions[$filters['status'] ?? ''] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileStatusOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= ($filters['status'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                    <input type="hidden" name="priority" value="<?= e($filters['priority'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobilePriorityOptions[$filters['priority'] ?? ''] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobilePriorityOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= ($filters['priority'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
                <?php if ($roleId <= 2): ?>
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                    <input type="hidden" name="assigned_to" value="<?= e($filters['assigned_to'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileExecutorOptions[(string)($filters['assigned_to'] ?? '')] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileExecutorOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= (string)($filters['assigned_to'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
                <?php endif; ?>
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                    <input type="hidden" name="deadline" value="<?= e($filters['deadline'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileDeadlineOptions[$filters['deadline'] ?? ''] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileDeadlineOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= ($filters['deadline'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
                <?php if (!($project ?? null) && !empty($projects)): ?>
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                    <input type="hidden" name="project_id" value="<?= e($filters['project_id'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileProjectOptions[(string)($filters['project_id'] ?? '')] ?? 'Все проекты') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileProjectOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= (string)($filters['project_id'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
                <?php endif; ?>
                <?php if ($project ?? null): ?>
                    <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <?php endif; ?>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="ui-btn ui-btn-dark">Применить</button>
                    <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
                       class="ui-btn ui-btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>
</div>
