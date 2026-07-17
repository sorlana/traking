<?php
/**
 * Шаблон списка проектов
 * Карточки проектов + фильтры в модальном окне
 */
$layout = 'layouts/app';

// Проверка: есть ли активные фильтры
$hasFilters = !empty($filters['status']) || !empty($filters['manager']) || !empty($filters['executor']) || !empty($filters['deadline']);

$mobileStatusOptions = ['' => 'Все статусы'];
foreach ($statuses as $s) {
    $mobileStatusOptions[$s['code']] = $s['name'];
}

$mobileManagerOptions = ['' => 'Все'];
foreach ($managers as $m) {
    $mobileManagerOptions[(string) $m['id']] = $m['name'];
}

$mobileExecutorOptions = ['' => 'Все'];
foreach ($executors as $ex) {
    $mobileExecutorOptions[(string) $ex['id']] = $ex['name'];
}

$mobileDeadlineOptions = [
    '' => 'Все',
    'overdue' => 'Просроченные',
    'week' => 'На этой неделе',
];

$createStatusOptions = [];
$createStatusValue = '';
$createStatusLabel = 'Выберите статус';
foreach ($statuses as $s) {
    $createStatusOptions[(string) $s['id']] = $s['name'];
    if (($s['code'] ?? '') === 'new') {
        $createStatusValue = (string) $s['id'];
        $createStatusLabel = $s['name'];
    }
}
if ($createStatusValue === '' && !empty($createStatusOptions)) {
    foreach ($createStatusOptions as $value => $label) {
        $createStatusValue = (string) $value;
        $createStatusLabel = $label;
        break;
    }
}
?>

<div x-data="{ showFilters: false, showCreate: false }">

    <!-- Заголовок + Создать + Фильтры -->
    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-bold text-gray-800">Проекты</h1>

        <div class="flex items-center gap-2">
            <?php if (can('create_project')): ?>
                <button type="button"
                        @click="showCreate = true; showFilters = false; $nextTick(() => $refs.createProjectTitle?.focus())"
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
    <form method="GET" action="<?= url('/projects') ?>" class="hidden lg:block mb-6">
        <div class="grid grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="ui-control">
                    <option value="">Все статусы</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= $filters['status'] === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
                <select name="manager" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= $filters['manager'] == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="executor" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($executors as $ex): ?>
                        <option value="<?= e($ex['id']) ?>" <?= $filters['executor'] == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="ui-control">
                    <option value="">Все</option>
                    <option value="overdue" <?= $filters['deadline'] === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                    <option value="week" <?= $filters['deadline'] === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="ui-btn ui-btn-dark">Применить</button>
                <a href="<?= url('/projects') ?>" class="ui-btn ui-btn-secondary">Сбросить</a>
            </div>
        </div>
    </form>

    <!-- Сетка проектов -->
    <?php if (empty($projects)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500">Проектов пока нет</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($projects as $project): ?>
                <a href="<?= url('/projects/' . (int) $project['id']) ?>"
                   class="bg-white rounded-lg shadow-sm border hover:shadow-md transition p-5 block">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <h3 class="text-base font-semibold text-gray-800 line-clamp-2"><?= e($project['title']) ?></h3>
                        <?php
                        $projectStatusColors = [
                            'new' => 'bg-gray-100 text-gray-700',
                            'active' => 'bg-blue-50 text-blue-700',
                            'on_hold' => 'bg-gray-200 text-gray-700',
                            'closed' => 'bg-gray-100 text-gray-600',
                        ];
                        $colorClass = $projectStatusColors[$project['status_code']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                        <span class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap <?= $colorClass ?>">
                            <?= e($project['status_name']) ?>
                        </span>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600">
                        <?php if ($project['deadline']): ?>
                            <?php $isOverdue = $project['deadline'] < date('Y-m-d') && $project['status_code'] !== 'closed'; ?>
                            <div class="flex items-center gap-2 <?= $isOverdue ? 'text-red-600' : '' ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span><?= date('d.m.Y', strtotime($project['deadline'])) ?></span>
                                <?php if ($isOverdue): ?>
                                    <span class="text-xs font-medium text-red-600">просрочен</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span>Задач: <?= (int) $project['task_total'] ?></span>
                            <?php if ((int) $project['task_open'] > 0): ?>
                                <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">
                                    открытых: <?= (int) $project['task_open'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($project['estimated_hours'])): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Расчёт: <?= rtrim(rtrim(number_format((float) $project['estimated_hours'], 1, '.', ''), '0'), '.') ?> ч</span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['managers'])): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="truncate"><?= e(implode(', ', array_column($project['managers'], 'name'))) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Модалка: Создание проекта -->
    <div x-show="showCreate" x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showCreate = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[86vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Новый проект</h2>
                <button type="button" @click="showCreate = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <form method="POST" action="<?= url('/projects/create') ?>" class="p-4 space-y-4" data-mobile-form-validation>
                <?= csrf_field() ?>

                <div>
                    <label for="create_project_title" class="block text-xs font-medium text-gray-500 mb-1">Название проекта <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="create_project_title" x-ref="createProjectTitle" required maxlength="255"
                           placeholder="Введите название проекта"
                           class="ui-control">
                </div>

                <div>
                    <label for="create_project_description" class="block text-xs font-medium text-gray-500 mb-1">Описание</label>
                    <textarea name="description" id="create_project_description" rows="3" placeholder="Описание проекта"
                              class="ui-control"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="create_project_deadline" class="block text-xs font-medium text-gray-500 mb-1">Срок сдачи</label>
                        <input type="date" name="deadline" id="create_project_deadline"
                               class="ui-control">
                    </div>

                    <div>
                        <label for="create_project_estimated_hours" class="block text-xs font-medium text-gray-500 mb-1">Расчётное время</label>
                        <input type="number" name="estimated_hours" id="create_project_estimated_hours" step="0.5" min="0.5"
                               placeholder="Часы"
                               class="ui-control">
                    </div>
                </div>

                <div class="mobile-filter-field" data-required-mobile-select>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Статус <span class="text-red-500">*</span></label>
                    <input type="hidden" name="status_id" value="<?= e($createStatusValue) ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($createStatusLabel) ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($createStatusOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= $createStatusValue === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

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
                <button type="button" @click="showFilters = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <form method="GET" action="<?= url('/projects') ?>" class="p-4 space-y-4">
                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                    <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileStatusOptions[$filters['status'] ?? ''] ?? 'Все статусы') ?></span>
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
                    <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
                    <input type="hidden" name="manager" value="<?= e($filters['manager'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileManagerOptions[(string)($filters['manager'] ?? '')] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileManagerOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= (string)($filters['manager'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

                <div class="mobile-filter-field">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                    <input type="hidden" name="executor" value="<?= e($filters['executor'] ?? '') ?>">
                    <details class="mobile-filter-details">
                        <summary class="mobile-filter-trigger">
                            <span class="mobile-filter-label"><?= e($mobileExecutorOptions[(string)($filters['executor'] ?? '')] ?? 'Все') ?></span>
                            <svg class="mobile-filter-arrow w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mobile-filter-menu">
                            <?php foreach ($mobileExecutorOptions as $value => $label): ?>
                                <button type="button" class="mobile-filter-option <?= (string)($filters['executor'] ?? '') === (string) $value ? 'is-selected' : '' ?>" data-value="<?= e($value) ?>" data-label="<?= e($label) ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

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

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="ui-btn ui-btn-dark">Применить</button>
                    <a href="<?= url('/projects') ?>" class="ui-btn ui-btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>
</div>
