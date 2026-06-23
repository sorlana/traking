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
];

// Проверка: есть ли активные фильтры
$hasFilters = !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['assigned_to']) || !empty($filters['deadline']) || !empty($filters['project_id']);
?>

<div class="space-y-4" x-data="{ showFilters: false }">
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
                <a href="<?= url('/tasks/create') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    Создать
                </a>
            <?php endif; ?>
            <button @click="showFilters = true"
                    class="lg:hidden px-3 py-1.5 bg-white border rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition <?= $hasFilters ? 'border-blue-500 text-blue-700' : '' ?>">
                Фильтры<?= $hasFilters ? ' ●' : '' ?>
            </button>
        </div>
    </div>

    <!-- Десктопные фильтры (lg+) -->
    <form method="GET" action="<?= url('/tasks') ?>" class="hidden lg:block bg-white rounded-lg shadow-sm border p-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Все</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= ($filters['status'] ?? '') === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                <select name="priority" class="w-full rounded-md border-gray-300 text-sm">
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
                <select name="assigned_to" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Все</option>
                    <?php foreach ($executors as $exec): ?>
                        <option value="<?= (int) $exec['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $exec['id'] ? 'selected' : '' ?>><?= e($exec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Все</option>
                    <option value="today" <?= ($filters['deadline'] ?? '') === 'today' ? 'selected' : '' ?>>Сегодня</option>
                    <option value="week" <?= ($filters['deadline'] ?? '') === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                    <option value="overdue" <?= ($filters['deadline'] ?? '') === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                </select>
            </div>
            <?php if (!($project ?? null) && !empty($projects)): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                <select name="project_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Все проекты</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ($filters['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">Фильтр</button>
                <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">Сбросить</a>
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

    <!-- Модалка: Фильтры -->
    <div x-show="showFilters" x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showFilters = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[82vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Фильтры</h2>
                <button @click="showFilters = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <form method="GET" action="<?= url('/tasks') ?>" class="p-4 space-y-4">
                <?php
                $statusOptions = ['' => 'Все'];
                foreach ($statuses as $s) {
                    $statusOptions[$s['code']] = $s['name'];
                }
                $priorityOptions = [
                    '' => 'Все',
                    'low' => 'Низкий',
                    'medium' => 'Средний',
                    'high' => 'Высокий',
                    'urgent' => 'Срочный',
                ];
                $deadlineOptions = [
                    '' => 'Все',
                    'today' => 'Сегодня',
                    'week' => 'На этой неделе',
                    'overdue' => 'Просроченные',
                ];
                $executorOptions = ['' => 'Все'];
                foreach ($executors as $exec) {
                    $executorOptions[(string) $exec['id']] = $exec['name'];
                }
                $projectOptions = ['' => 'Все проекты'];
                foreach ($projects ?? [] as $p) {
                    $projectOptions[(string) $p['id']] = $p['title'];
                }
                $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
                ?>
                <div class="relative" x-data="{ open: false, label: <?= json_encode($statusOptions[$filters['status'] ?? ''] ?? 'Все', $jsonFlags) ?> }">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                    <input x-ref="input" type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                    <button type="button" @click="open = !open" class="w-full min-h-0 flex items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <button type="button" @click="label = <?= json_encode($label, $jsonFlags) ?>; $refs.input.value = '<?= e($value) ?>'; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 <?= ($filters['status'] ?? '') === (string) $value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="relative" x-data="{ open: false, label: <?= json_encode($priorityOptions[$filters['priority'] ?? ''] ?? 'Все', $jsonFlags) ?> }">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Приоритет</label>
                    <input x-ref="input" type="hidden" name="priority" value="<?= e($filters['priority'] ?? '') ?>">
                    <button type="button" @click="open = !open" class="w-full min-h-0 flex items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <?php foreach ($priorityOptions as $value => $label): ?>
                            <button type="button" @click="label = <?= json_encode($label, $jsonFlags) ?>; $refs.input.value = '<?= e($value) ?>'; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 <?= ($filters['priority'] ?? '') === (string) $value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($roleId <= 2): ?>
                <div class="relative" x-data="{ open: false, label: <?= json_encode($executorOptions[(string) ($filters['assigned_to'] ?? '')] ?? 'Все', $jsonFlags) ?> }">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                    <input x-ref="input" type="hidden" name="assigned_to" value="<?= e($filters['assigned_to'] ?? '') ?>">
                    <button type="button" @click="open = !open" class="w-full min-h-0 flex items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <?php foreach ($executorOptions as $value => $label): ?>
                            <button type="button" @click="label = <?= json_encode($label, $jsonFlags) ?>; $refs.input.value = '<?= e($value) ?>'; open = false" class="w-full truncate px-3 py-2 text-left text-sm hover:bg-blue-50 <?= (string) ($filters['assigned_to'] ?? '') === (string) $value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="relative" x-data="{ open: false, label: <?= json_encode($deadlineOptions[$filters['deadline'] ?? ''] ?? 'Все', $jsonFlags) ?> }">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                    <input x-ref="input" type="hidden" name="deadline" value="<?= e($filters['deadline'] ?? '') ?>">
                    <button type="button" @click="open = !open" class="w-full min-h-0 flex items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <?php foreach ($deadlineOptions as $value => $label): ?>
                            <button type="button" @click="label = <?= json_encode($label, $jsonFlags) ?>; $refs.input.value = '<?= e($value) ?>'; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 <?= ($filters['deadline'] ?? '') === (string) $value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!($project ?? null) && !empty($projects)): ?>
                <div class="relative" x-data="{ open: false, label: <?= json_encode($projectOptions[(string) ($filters['project_id'] ?? '')] ?? 'Все проекты', $jsonFlags) ?> }">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Проект</label>
                    <input x-ref="input" type="hidden" name="project_id" value="<?= e($filters['project_id'] ?? '') ?>">
                    <button type="button" @click="open = !open" class="w-full min-h-0 flex items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        <?php foreach ($projectOptions as $value => $label): ?>
                            <button type="button" @click="label = <?= json_encode($label, $jsonFlags) ?>; $refs.input.value = '<?= e($value) ?>'; open = false" class="w-full truncate px-3 py-2 text-left text-sm hover:bg-blue-50 <?= (string) ($filters['project_id'] ?? '') === (string) $value ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($project ?? null): ?>
                    <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <?php endif; ?>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">Применить</button>
                    <a href="<?= url('/tasks') ?><?= ($project ?? null) ? '?project_id=' . (int) $project['id'] : '' ?>"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">Сбросить</a>
                </div>
            </form>
        </div>
    </div>
</div>
