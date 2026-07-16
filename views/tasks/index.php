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
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high' => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
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
?>

<div class="space-y-4" x-data="{ showFilters: false, showCreate: false }">
    <!-- Заголовок + Создать + Фильтры (мобильная кнопка) -->
    <div class="flex items-center justify-between gap-4">
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
                        @click="showCreate = true; showFilters = false; $nextTick(() => $refs.createTaskTitle?.focus())"
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

    <!-- Десктопные фильтры (lg+) -->
    <form method="GET" action="<?= url('/tasks') ?>" class="hidden lg:block bg-white rounded-lg shadow-sm border p-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= ($filters['status'] ?? '') === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
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
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="assigned_to" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($executors as $exec): ?>
                        <option value="<?= (int) $exec['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $exec['id'] ? 'selected' : '' ?>><?= e($exec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="ui-control">
                    <option value="">Все</option>
                    <option value="today" <?= ($filters['deadline'] ?? '') === 'today' ? 'selected' : '' ?>>Сегодня</option>
                    <option value="week" <?= ($filters['deadline'] ?? '') === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                    <option value="overdue" <?= ($filters['deadline'] ?? '') === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                </select>
            </div>
            <?php if (!($project ?? null) && !empty($projects)): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                <select name="project_id" class="ui-control">
                    <option value="">Все проекты</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($filters['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="flex items-end gap-2">
                <button type="submit" class="ui-btn ui-btn-dark">Фильтр</button>
                <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>" class="ui-btn ui-btn-secondary">Сбросить</a>
            </div>
        </div>
        <?php if ($project ?? null): ?><input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>"><?php endif; ?>
    </form>

    <!-- Таблица задач -->
    <?php if (empty($tasks)): ?>
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
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

    <!-- Модалка: Создание задачи -->
    <div x-show="showCreate" x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showCreate = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[86vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Новая задача</h2>
                <button type="button" @click="showCreate = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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

                <div>
                    <label for="create_task_description" class="block text-xs font-medium text-gray-500 mb-1">Описание</label>
                    <textarea name="description" id="create_task_description" rows="3" placeholder="Короткое описание"
                              class="ui-control"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="mobile-filter-field">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                        <input type="hidden" name="priority" value="<?= e($createPriorityValue) ?>">
                        <details class="mobile-filter-details">
                            <summary class="mobile-filter-trigger">
                                <span class="mobile-filter-label"><?= e($createPriorityOptions[$createPriorityValue] ?? 'Средний') ?></span>
                                <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mobile-filter-menu">
                                <?php foreach ($createPriorityOptions as $value => $label): ?>
                                    <button type="button" class="mobile-filter-option <?= $createPriorityValue === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>

                    <div>
                        <label for="create_task_deadline" class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                        <input type="date" name="deadline" id="create_task_deadline"
                               class="ui-control">
                    </div>
                </div>

                <?php if ($roleId <= 2): ?>
                    <div class="mobile-filter-field">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                        <input type="hidden" name="assigned_to" value="">
                        <details class="mobile-filter-details">
                            <summary class="mobile-filter-trigger">
                                <span class="mobile-filter-label"><?= e($createExecutorOptions['']) ?></span>
                                <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mobile-filter-menu">
                                <?php foreach ($createExecutorOptions as $value => $label): ?>
                                    <button type="button" class="mobile-filter-option <?= (string) $value === '' ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <div class="flex gap-2 pt-2 border-t">
                    <button type="submit" class="ui-btn ui-btn-dark">Создать</button>
                    <button type="button" @click="showCreate = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка: Фильтры -->
    <div x-show="showFilters" x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showFilters = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[82vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Фильтры</h2>
                <button @click="showFilters = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
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
