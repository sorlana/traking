<?php
$layout = 'layouts/app';
$manualOld = is_array($manualOld ?? null) ? $manualOld : [];
$manualIsOpen = !empty($manualOld);
// Возможность быстрого внесения времени есть только у руководителей и исполнителей
$canQuickEntry = ($visibleTimeType ?? null) !== null;
?>

<div class="space-y-4"
     x-data="calendarQuickEntry({ enabled: <?= $canQuickEntry ? 'true' : 'false' ?>, manualOpen: <?= $manualIsOpen ? 'true' : 'false' ?> })"
     @keydown.escape.window="closeModal()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Календарь</h1>
        </div>
        <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2">
            <?php if (!empty($recoverableTasks)): ?>
                <button type="button" class="ui-btn ui-btn-primary" @click="manualOpen = !manualOpen" :aria-expanded="manualOpen.toString()" aria-controls="manual-time-entry">
                    Перенести время
                </button>
            <?php endif; ?>
            <a href="<?= url('/calendar?month=' . $previousMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Предыдущий месяц">←</a>
            <span class="min-w-[145px] text-center text-sm font-semibold text-gray-700"><?= e($monthTitle) ?></span>
            <a href="<?= url('/calendar?month=' . $nextMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Следующий месяц">→</a>
            <?php if ($currentMonth !== date('Y-m')): ?>
                <a href="<?= url('/calendar') ?>" class="ui-btn ui-btn-light">Сегодня</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($recoverableTasks)): ?>
        <form id="manual-time-entry" x-show="manualOpen" x-cloak method="POST" action="<?= url('/calendar/manual-entry') ?>"
              class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <?= csrf_field() ?>
            <div class="mb-3 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Перенести ранее учтённое время</h2>
                    <p class="mt-1 text-xs text-gray-500">Запись появится в календаре, общая сумма задачи не изменится.</p>
                </div>
                <button type="button" @click="manualOpen = false" class="p-1 text-gray-500 transition hover:text-black" aria-label="Закрыть">×</button>
            </div>
            <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_120px_165px_auto] md:items-end">
                <div>
                    <label for="manual-task-id" class="mb-1 block text-xs font-medium text-gray-600">Задача</label>
                    <select id="manual-task-id" name="task_id" required class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Выберите задачу</option>
                        <?php foreach ($recoverableTasks as $recoverableTask): ?>
                            <?php
                            $taskId = (int) $recoverableTask['id'];
                            $available = rtrim(rtrim(number_format((float) $recoverableTask['available_hours'], 2, '.', ''), '0'), '.');
                            ?>
                            <option value="<?= $taskId ?>" <?= (int) ($manualOld['task_id'] ?? 0) === $taskId ? 'selected' : '' ?>>
                                <?= e($recoverableTask['project_title'] . ' · ' . $recoverableTask['title'] . ' — доступно ' . $available . ' ч') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="manual-hours" class="mb-1 block text-xs font-medium text-gray-600">Часы</label>
                    <input id="manual-hours" name="hours" type="number" required min="0.5" max="999.5" step="0.5"
                           value="<?= e((string) ($manualOld['hours'] ?? '')) ?>" placeholder="0.5"
                           class="w-full rounded-md border-gray-300 text-sm placeholder:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="manual-entry-date" class="mb-1 block text-xs font-medium text-gray-600">Дата</label>
                    <input id="manual-entry-date" name="entry_date" type="date" required max="<?= date('Y-m-d') ?>"
                           value="<?= e((string) ($manualOld['entry_date'] ?? date('Y-m-d'))) ?>"
                           class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="ui-btn ui-btn-primary justify-center">Перенести</button>
            </div>
        </form>
    <?php elseif (($visibleTimeType ?? null) !== null): ?>
        <p class="text-xs text-gray-500">Всё ранее учтённое время уже распределено по календарю.</p>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
            <?php foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $index => $weekday): ?>
                <div class="py-2 text-center text-xs font-semibold <?= $index >= 5 ? 'text-gray-400' : 'text-gray-600' ?>">
                    <?= $weekday ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-7 bg-gray-200 gap-px">
            <?php for ($i = 0; $i < $leadingBlankDays; $i++): ?>
                <div class="min-h-[64px] sm:min-h-[90px] bg-gray-50"></div>
            <?php endfor; ?>

            <?php foreach ($days as $day): ?>
                <?php
                $dayEntries = $entriesByDate[$day['date']] ?? [];
                $dayTotal = (float) ($dayTotals[$day['date']] ?? 0);
                $isFuture = $day['date'] > date('Y-m-d');
                ?>
                <div class="relative min-w-0 min-h-[64px] sm:min-h-[90px] p-1 sm:p-1.5 <?= $day['today'] ? 'bg-blue-50 ring-2 ring-inset ring-blue-400' : ($day['weekend'] ? 'bg-gray-50' : 'bg-white') ?> <?= ($canQuickEntry && !$isFuture) ? 'cursor-context-menu' : '' ?>"
                    <?php if ($canQuickEntry && !$isFuture): ?>
                    @contextmenu.prevent="openModal('<?= e($day['date']) ?>')"
                    <?php endif; ?>>
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold <?= $day['today'] ? 'bg-blue-600 text-white' : ($day['weekend'] ? 'text-gray-400' : 'text-gray-700') ?>">
                            <?= e($day['day']) ?>
                        </span>
                        <?php if ($dayTotal > 0): ?>
                            <span class="text-[10px] sm:text-xs font-semibold text-blue-700 whitespace-nowrap">
                                <?= e(rtrim(rtrim(number_format($dayTotal, 2, '.', ''), '0'), '.')) ?>ч
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-1">
                        <?php foreach ($dayEntries as $entry): ?>
                            <?php $entryClass = $entry['time_type'] === 'manager' ? 'bg-purple-100 text-purple-800 hover:bg-purple-200' : 'bg-blue-100 text-blue-800 hover:bg-blue-200'; ?>
                            <a href="<?= url('/tasks/' . $entry['task_id']) ?>"
                               class="block min-w-0 rounded px-1 py-1 text-[10px] sm:text-xs leading-tight <?= $entryClass ?>"
                               title="<?= e($entry['project_title'] . ' · ' . $entry['task_title'] . ' · ' . $entry['hours'] . ' ч') ?>">
                                <span class="font-semibold"><?= e(rtrim(rtrim(number_format($entry['hours'], 2, '.', ''), '0'), '.')) ?>ч</span>
                                <span class="hidden sm:inline"> · <?= $entry['is_subtask'] ? '↳ ' : '' ?><?= e($entry['task_title']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $trailingBlankDays; $i++): ?>
                <div class="min-h-[64px] sm:min-h-[90px] bg-gray-50"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
        <div class="flex flex-wrap items-center gap-4">
            <span class="font-medium">Легенда:</span>
            <span class="flex items-center gap-1"><i class="w-3 h-3 rounded <?= $visibleTimeType === 'manager' ? 'bg-purple-300' : 'bg-blue-300' ?>"></i> Ваше время</span>
        </div>
        <span class="font-semibold text-gray-700">За месяц: <?= e(rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.')) ?>ч</span>
    </div>

    <?php if ($canQuickEntry): ?>
    <p class="text-xs text-gray-400">Подсказка: кликните правой кнопкой мыши по дню календаря, чтобы записать время.</p>

    <!-- Модальное окно быстрой записи времени (контекстное меню ячейки) -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-950/45 backdrop-blur-[1px]" @click="closeModal()" aria-hidden="true"></div>
        <section class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="quick-entry-title">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 id="quick-entry-title" class="text-base font-semibold text-gray-900">Записать время</h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Дата: <span class="font-medium text-gray-700" x-text="formatDate(selectedDate)"></span>
                    </p>
                </div>
                <button type="button" @click="closeModal()" class="p-1 text-gray-500 transition hover:text-black" aria-label="Закрыть">×</button>
            </div>

            <!-- Уже добавленные за эту дату в текущем сеансе записи -->
            <template x-if="savedEntries.length">
                <div class="mb-4 space-y-1.5">
                    <p class="text-xs font-medium text-gray-600">Записано в этот день:</p>
                    <template x-for="(item, index) in savedEntries" :key="index">
                        <div class="flex items-center justify-between rounded-md bg-green-50 px-2.5 py-1.5 text-xs text-green-800">
                            <span x-text="item.label"></span>
                            <span class="font-semibold" x-text="item.hours + ' ч'"></span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Загрузка списка задач -->
            <template x-if="loading">
                <p class="py-4 text-center text-sm text-gray-500">Загрузка списка задач…</p>
            </template>

            <!-- Нет доступных задач -->
            <template x-if="!loading && projects.length === 0">
                <p class="py-4 text-center text-sm text-gray-500">Нет задач, доступных для внесения времени.</p>
            </template>

            <!-- Форма записи -->
            <div x-show="!loading && projects.length > 0" class="space-y-3">
                <p x-show="error" x-cloak class="rounded-md bg-red-50 px-3 py-2 text-xs text-red-700" x-text="error"></p>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Проект</label>
                    <select x-model="projectId" @change="taskId = ''"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Выберите проект</option>
                        <template x-for="project in projects" :key="project.id">
                            <option :value="project.id" x-text="project.title"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Задача</label>
                    <select x-model="taskId" :disabled="!projectId"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                        <option value="">Выберите задачу</option>
                        <template x-for="task in currentTasks()" :key="task.id">
                            <option :value="task.id" x-text="(task.is_subtask ? '↳ ' : '') + task.title"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Часы</label>
                    <input x-model="hours" type="number" min="0.5" max="999.5" step="0.5" placeholder="0.5"
                           class="w-full rounded-md border-gray-300 text-sm placeholder:text-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="closeModal()" class="ui-btn ui-btn-secondary justify-center">Готово</button>
                    <button type="button" @click="submitEntry()" :disabled="saving"
                            class="ui-btn ui-btn-primary justify-center"
                            x-text="saving ? 'Сохранение…' : 'Записать и добавить ещё'"></button>
                </div>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>

<script>
/**
 * Alpine-компонент контекстного меню календаря.
 * Позволяет по правому клику на день выбрать проект, задачу и записать время.
 * После сохранения форма остаётся открытой, чтобы добавить несколько задач за один день.
 */
function calendarQuickEntry(config) {
    return {
        enabled: config.enabled,
        manualOpen: config.manualOpen,   // управляет формой «Перенести время»
        modalOpen: false,
        selectedDate: '',
        // Кэш проектов и задач (загружается один раз)
        loaded: false,
        loading: false,
        projects: [],
        // Поля формы
        projectId: '',
        taskId: '',
        hours: '',
        // Состояние отправки
        saving: false,
        error: '',
        // Записи, добавленные за выбранную дату в текущем сеансе
        savedEntries: [],

        /** Открыть модалку для конкретной даты (Y-m-d). */
        openModal(date) {
            if (!this.enabled) return;
            this.selectedDate = date;
            this.savedEntries = [];
            this.projectId = '';
            this.taskId = '';
            this.hours = '';
            this.error = '';
            this.modalOpen = true;
            this.loadOptions();
        },

        closeModal() {
            if (!this.modalOpen) return;
            this.modalOpen = false;
            // Если что-то записали — перезагружаем страницу, чтобы обновить календарь
            if (this.savedEntries.length > 0) {
                window.location.reload();
            }
        },

        /** Однократная загрузка списка проектов и задач. */
        loadOptions() {
            if (this.loaded) return;
            this.loading = true;
            fetch(BASE_URL + '/calendar/entry-options', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    this.projects = data.projects || [];
                    this.loaded = true;
                })
                .catch(() => { this.error = 'Не удалось загрузить список задач'; })
                .finally(() => { this.loading = false; });
        },

        /** Задачи выбранного проекта. */
        currentTasks() {
            const project = this.projects.find(p => String(p.id) === String(this.projectId));
            return project ? project.tasks : [];
        },

        /** Форматирование даты Y-m-d → DD.MM.YYYY. */
        formatDate(date) {
            if (!date) return '';
            const parts = date.split('-');
            return parts.length === 3 ? parts[2] + '.' + parts[1] + '.' + parts[0] : date;
        },

        /** Отправить одну запись времени. */
        submitEntry() {
            this.error = '';
            if (!this.taskId || !this.hours) {
                this.error = 'Выберите задачу и укажите часы';
                return;
            }
            this.saving = true;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(BASE_URL + '/calendar/quick-entry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({
                    task_id: this.taskId,
                    hours: this.hours,
                    entry_date: this.selectedDate,
                    _token: csrf
                })
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || data.error) {
                        this.error = data.error || 'Не удалось записать время';
                        return;
                    }
                    // Запоминаем запись для показа в списке
                    const project = this.projects.find(p => String(p.id) === String(this.projectId));
                    const task = this.currentTasks().find(t => String(t.id) === String(this.taskId));
                    this.savedEntries.push({
                        label: (project ? project.title + ' · ' : '') + (task ? task.title : ''),
                        hours: data.added
                    });
                    // Сбрасываем поля для следующей записи
                    this.taskId = '';
                    this.hours = '';
                })
                .catch(() => { this.error = 'Ошибка сети. Попробуйте ещё раз'; })
                .finally(() => { this.saving = false; });
        }
    };
}
</script>
