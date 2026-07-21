<?php
/**
 * Шаблон списка проектов
 * Таблица проектов, фильтры и массовые действия
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

$currentUser = \Helpers\Auth::user();
$currentUserId = (int) ($currentUser['id'] ?? 0);
$deletableProjectIds = [];
$editableProjectIds = [];
foreach ($projects as $listedProject) {
    $listedProjectId = (int) $listedProject['id'];
    if (can('edit_project', $listedProjectId)) {
        $editableProjectIds[$listedProjectId] = true;
    }
    if (\Helpers\Auth::isAdmin() || (int) $listedProject['created_by'] === $currentUserId) {
        $deletableProjectIds[$listedProjectId] = true;
    }
}
$hasProjectActions = !empty($editableProjectIds) || !empty($deletableProjectIds);
$projectFilterQuery = http_build_query(array_filter(
    $filters,
    static fn(mixed $value): bool => $value !== '' && $value !== null
));
$projectsReturnUrl = '/projects' . ($projectFilterQuery !== '' ? '?' . $projectFilterQuery : '');

$projectStatusColors = [
    'new' => 'bg-blue-100 text-blue-700',
    'active' => 'bg-green-100 text-green-700',
    'on_hold' => 'bg-yellow-100 text-yellow-700',
    'closed' => 'bg-gray-100 text-gray-600',
];
?>

<div class="space-y-4 lg:flex lg:h-[calc(100vh-6rem)] lg:min-h-0 lg:flex-col lg:gap-4 lg:space-y-0 lg:overflow-hidden"
     x-data="{
         showFilters: false,
         showCreate: false,
         showEdit: false,
         editProject: {},
         selectedProjects: [],
         deletableProjectIds: <?= json_encode(array_map('intval', array_keys($deletableProjectIds))) ?>,
         toggleAllProjects(checked) {
             this.selectedProjects = checked ? [...this.deletableProjectIds] : [];
         },
         openEdit(project) {
             this.editProject = { ...project };
             this.showCreate = false;
             this.showFilters = false;
             this.showEdit = true;
             this.$nextTick(() => this.$refs.editProjectTitle?.focus());
         }
     }"
     @keydown.escape.window="showFilters = false; showCreate = false; showEdit = false">

    <!-- Заголовок + Создать + Фильтры -->
    <div class="flex items-center justify-between gap-4 lg:flex-shrink-0">
        <h1 class="text-xl font-bold text-gray-800">Проекты</h1>

        <div class="flex items-center gap-2">
            <?php if (can('create_project')): ?>
                <button type="button"
                        @click="showCreate = true; showEdit = false; showFilters = false; $nextTick(() => $refs.createProjectTitle?.focus())"
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

    <!-- Десктопные фильтры и массовые действия -->
    <div class="hidden items-end gap-4 rounded-lg border bg-white p-4 shadow-sm lg:flex lg:flex-shrink-0">
        <form method="GET" action="<?= url('/projects') ?>" class="flex min-w-0 flex-1 flex-wrap items-end gap-3">
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= $filters['status'] === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
                <select name="manager" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= $filters['manager'] == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="executor" class="ui-control">
                    <option value="">Все</option>
                    <?php foreach ($executors as $ex): ?>
                        <option value="<?= e($ex['id']) ?>" <?= $filters['executor'] == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                <select name="deadline" class="ui-control">
                    <option value="">Все</option>
                    <option value="overdue" <?= $filters['deadline'] === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                    <option value="week" <?= $filters['deadline'] === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                </select>
            </div>
            <div class="flex items-end gap-1">
                <button type="submit" class="ui-btn ui-btn-primary flex h-11 w-11 items-center justify-center px-0"
                        aria-label="Применить фильтры" title="Применить фильтры">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L14 13.667V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.333L3.2 4.6A1 1 0 013 4z"/>
                    </svg>
                </button>
                <a href="<?= url('/projects') ?>"
                   class="ui-btn ui-btn-secondary flex h-11 w-11 items-center justify-center px-0"
                   aria-label="Сбросить фильтры" title="Сбросить фильтры">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
        </form>

        <?php if (!empty($deletableProjectIds) && !empty($projects)): ?>
            <form method="POST" action="<?= url('/projects/delete-selected') ?>"
                  data-confirm-delete="Удалить выбранные проекты и все задачи внутри? Это действие нельзя отменить."
                  class="flex flex-shrink-0 items-center gap-2 border-l pl-4">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($projectsReturnUrl) ?>">
                <template x-for="projectId in selectedProjects" :key="'desktop-project-' + projectId">
                    <input type="hidden" name="project_ids[]" :value="projectId">
                </template>
                <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-xs text-gray-600">
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600"
                           :checked="deletableProjectIds.length > 0 && selectedProjects.length === deletableProjectIds.length"
                           @change="toggleAllProjects($event.target.checked)">
                    Выбрать все
                </label>
                <button type="submit" :disabled="selectedProjects.length === 0" class="ui-btn ui-btn-subtle whitespace-nowrap">
                    Удалить выбранные <span class="ui-btn-count" x-text="selectedProjects.length">0</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Таблица проектов -->
    <?php if (empty($projects)): ?>
        <div class="rounded-lg border bg-white p-8 text-center">
            <p class="text-gray-500">Проекты не найдены</p>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-lg border bg-white lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
            <?php if (!empty($deletableProjectIds)): ?>
                <form method="POST" action="<?= url('/projects/delete-selected') ?>"
                      data-confirm-delete="Удалить выбранные проекты и все задачи внутри? Это действие нельзя отменить."
                      class="flex h-[53px] items-center justify-between gap-3 border-b bg-white px-4 lg:hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e($projectsReturnUrl) ?>">
                    <template x-for="projectId in selectedProjects" :key="'mobile-project-' + projectId">
                        <input type="hidden" name="project_ids[]" :value="projectId">
                    </template>
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600"
                               :checked="deletableProjectIds.length > 0 && selectedProjects.length === deletableProjectIds.length"
                               @change="toggleAllProjects($event.target.checked)">
                        Выбрать все
                    </label>
                    <button type="submit" :disabled="selectedProjects.length === 0" class="ui-btn ui-btn-subtle">
                        Удалить выбранные <span class="ui-btn-count" x-text="selectedProjects.length">0</span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="task-table-scroll overflow-x-auto lg:min-h-0 lg:flex-1 lg:overflow-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-gray-50 lg:sticky lg:top-0 lg:z-10">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Название</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Статус</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Задачи</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Руководитель</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Срок</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden xl:table-cell">Расчёт</th>
                            <?php if ($hasProjectActions): ?>
                                <th class="w-20 px-2 py-3"><span class="sr-only">Действия</span></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 border-b border-gray-200">
                        <?php foreach ($projects as $project): ?>
                            <?php
                            $projectId = (int) $project['id'];
                            $canEditProject = isset($editableProjectIds[$projectId]);
                            $canDeleteProject = isset($deletableProjectIds[$projectId]);
                            $isOverdue = !empty($project['deadline'])
                                && $project['deadline'] < date('Y-m-d')
                                && ($project['status_code'] ?? '') !== 'closed';
                            $isPersonalProject = (int) ($project['creator_role_id'] ?? 0) === \Helpers\Auth::ROLE_EXECUTOR
                                && (int) $project['created_by'] === $currentUserId;
                            $rowBackground = $isPersonalProject
                                ? 'bg-blue-50 hover:bg-blue-100'
                                : 'bg-white hover:bg-gray-100';
                            $statusClass = $projectStatusColors[$project['status_code'] ?? '']
                                ?? 'bg-gray-100 text-gray-600';
                            $managerNames = !empty($project['managers'])
                                ? implode(', ', array_column($project['managers'], 'name'))
                                : '—';
                            ?>
                            <tr class="transition-colors <?= $rowBackground ?>">
                                <td class="px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <?php if ($canDeleteProject): ?>
                                            <input type="checkbox" value="<?= $projectId ?>" x-model.number="selectedProjects"
                                                   class="h-4 w-4 flex-shrink-0 rounded border-gray-300 text-blue-600"
                                                   aria-label="Выбрать проект <?= e($project['title']) ?>">
                                        <?php elseif (!empty($deletableProjectIds)): ?>
                                            <span class="h-4 w-4 flex-shrink-0" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <a href="<?= url('/projects/' . $projectId) ?>"
                                               class="font-medium text-blue-600 hover:text-blue-800">
                                                <?= e($project['title']) ?>
                                            </a>
                                            <?php if ($isOverdue): ?>
                                                <span class="ml-1 text-xs font-medium text-red-600">Просрочено</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium <?= $statusClass ?>">
                                        <?= e($project['status_name'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">
                                    <?= (int) $project['task_total'] ?>
                                    <?php if ((int) $project['task_open'] > 0): ?>
                                        <span class="ml-1 text-xs text-gray-500">открытых: <?= (int) $project['task_open'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= e($managerNames) ?></td>
                                <td class="px-4 py-3 hidden lg:table-cell <?= $isOverdue ? 'font-medium text-red-600' : 'text-gray-600' ?>">
                                    <?= $project['deadline'] ? date('d.m.Y', strtotime($project['deadline'])) : '—' ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600 hidden xl:table-cell">
                                    <?= !empty($project['estimated_hours'])
                                        ? e(rtrim(rtrim(number_format((float) $project['estimated_hours'], 1, '.', ''), '0'), '.') . ' ч')
                                        : '—' ?>
                                </td>
                                <?php if ($hasProjectActions): ?>
                                    <td class="px-2 py-1 text-right">
                                        <?php if ($canEditProject || $canDeleteProject): ?>
                                            <div class="flex items-center justify-end -space-x-3">
                                                <?php if ($canEditProject): ?>
                                                    <button type="button"
                                                       @click='openEdit(<?= json_encode([
                                                           'id' => $projectId,
                                                           'title' => $project['title'],
                                                           'description' => $project['description'] ?? '',
                                                           'deadline' => $project['deadline'] ?? '',
                                                           'estimated_hours' => $project['estimated_hours'] ?? '',
                                                           'status_id' => (int) $project['status_id'],
                                                       ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'
                                                       class="a11y-icon-button text-gray-400 hover:text-black"
                                                       aria-label="Редактировать проект <?= e($project['title']) ?>" title="Редактировать">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteProject): ?>
                                                    <form method="POST" action="<?= url('/projects/' . $projectId . '/delete') ?>"
                                                          data-confirm-delete="<?= e('Удалить проект «' . $project['title'] . '» и все задачи внутри? Это действие нельзя отменить.') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="redirect_to" value="<?= e($projectsReturnUrl) ?>">
                                                        <button type="submit" class="a11y-icon-button text-gray-400 hover:text-black"
                                                                aria-label="Удалить проект <?= e($project['title']) ?>" title="Удалить">
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
    <?php endif; ?>

    <!-- Модалка: Создание проекта -->
    <div x-show="showCreate" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="create-project-title"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showCreate = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[86vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 id="create-project-title" class="text-lg font-bold text-gray-800">Новый проект</h2>
                <button type="button" @click="showCreate = false" class="a11y-icon-button text-gray-400 hover:text-gray-600 text-xl" aria-label="Закрыть окно создания проекта">&times;</button>
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
                    <button type="submit" class="ui-btn ui-btn-primary">Создать</button>
                    <button type="button" @click="showCreate = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка: Редактирование проекта -->
    <div x-show="showEdit" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="edit-project-title"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
         @click.self="showEdit = false" style="display: none;">
        <div class="max-h-[86vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl" @click.stop>
            <div class="flex items-center justify-between border-b p-4">
                <h2 id="edit-project-title" class="text-lg font-bold text-gray-800">Редактирование проекта</h2>
                <button type="button" @click="showEdit = false"
                        class="a11y-icon-button text-xl text-gray-400 hover:text-black"
                        aria-label="Закрыть окно редактирования проекта">&times;</button>
            </div>
            <form method="POST" :action="'<?= url('/projects') ?>/' + editProject.id + '/edit'"
                  class="space-y-4 p-4">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= e($projectsReturnUrl) ?>">

                <div>
                    <label for="edit_project_title_input" class="mb-1 block text-xs font-medium text-gray-500">
                        Название проекта <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_project_title_input" name="title" x-ref="editProjectTitle"
                           x-model="editProject.title" required maxlength="255" class="ui-control">
                </div>

                <div>
                    <label for="edit_project_description" class="mb-1 block text-xs font-medium text-gray-500">Описание</label>
                    <textarea id="edit_project_description" name="description" x-model="editProject.description"
                              rows="3" placeholder="Описание проекта" class="ui-control"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="edit_project_deadline" class="mb-1 block text-xs font-medium text-gray-500">Срок сдачи</label>
                        <input type="date" id="edit_project_deadline" name="deadline"
                               x-model="editProject.deadline" class="ui-control">
                    </div>
                    <div>
                        <label for="edit_project_estimated_hours" class="mb-1 block text-xs font-medium text-gray-500">Расчётное время</label>
                        <input type="number" id="edit_project_estimated_hours" name="estimated_hours"
                               x-model="editProject.estimated_hours" step="0.5" min="0.5" placeholder="Часы" class="ui-control">
                    </div>
                </div>

                <div>
                    <label for="edit_project_status" class="mb-1 block text-xs font-medium text-gray-500">
                        Статус <span class="text-red-500">*</span>
                    </label>
                    <select id="edit_project_status" name="status_id" x-model="editProject.status_id" required class="ui-control">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= (int) $status['id'] ?>"><?= e($status['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-2 border-t pt-4">
                    <button type="submit" class="ui-btn ui-btn-primary">Сохранить</button>
                    <button type="button" @click="showEdit = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка: Фильтры -->
    <div x-show="showFilters" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="project-filters-title"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
         @click.self="showFilters = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[82vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 id="project-filters-title" class="text-lg font-bold text-gray-800">Фильтры</h2>
                <button type="button" @click="showFilters = false" class="a11y-icon-button text-gray-400 hover:text-gray-600 text-xl" aria-label="Закрыть фильтры">&times;</button>
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
                    <button type="submit" class="ui-btn ui-btn-primary">Применить</button>
                    <a href="<?= url('/projects') ?>" class="ui-btn ui-btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>
</div>
