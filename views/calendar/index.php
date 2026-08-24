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

    <?php if ($canQuickEntry): ?>
    <!-- Панель массовых действий: появляется при выборе записей -->
    <div x-show="selectedEntries.length > 0" x-cloak
         class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2"
         style="display:none">
        <span class="text-sm text-blue-800">
            Выбрано записей: <span class="font-semibold" x-text="selectedEntries.length"></span>
        </span>
        <div class="flex items-center gap-2">
            <button type="button" @click="selectedEntries = []" class="ui-btn ui-btn-secondary">Снять выбор</button>
            <button type="button" @click="openBulkDelete()"
                    class="ui-btn justify-center border-red-600 bg-red-600 text-white hover:border-red-700 hover:bg-red-700 focus-visible:ring-red-500">
                Удалить выбранные
            </button>
        </div>
    </div>
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
                            <?php
                            // Цвет записи определяется проектом; если проект вдруг не в карте — нейтральный серый
                            $entryClass = $projectColors[$entry['project_id']]['bg']
                                ?? 'bg-gray-100 text-gray-800 hover:bg-gray-200';
                            $entryHours = rtrim(rtrim(number_format($entry['hours'], 2, '.', ''), '0'), '.');
                            // Данные записи для JS: идентификация по задаче+типу+дате
                            $entryPayload = json_encode([
                                'task_id' => $entry['task_id'],
                                'time_type' => $entry['time_type'],
                                'entry_date' => $day['date'],
                                'hours' => (float) $entry['hours'],
                                'task_title' => $entry['task_title'],
                                'project_title' => $entry['project_title'],
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                            // Уникальный ключ записи для массового выбора
                            $entryKey = $entry['task_id'] . '|' . $entry['time_type'] . '|' . $day['date'];
                            ?>
                            <?php if ($canQuickEntry): ?>
                                <div class="group/entry rounded px-1 py-1 text-[10px] sm:text-xs leading-tight <?= $entryClass ?>">
                                    <!-- Верхний ряд: чекбокс слева, иконки действий справа -->
                                    <div class="flex items-center justify-between gap-1">
                                        <input type="checkbox" value="<?= e($entryKey) ?>" x-model="selectedEntries"
                                               @click.stop
                                               class="h-3 w-3 flex-shrink-0 rounded border-gray-400 text-blue-600 focus:ring-blue-500"
                                               aria-label="Выбрать запись <?= e($entry['task_title']) ?>">
                                        <div class="flex flex-shrink-0 items-center gap-0.5">
                                            <button type="button"
                                                    @click.stop='openEditEntry(<?= $entryPayload ?>)'
                                                    class="flex h-4 w-4 items-center justify-center rounded hover:bg-black/10"
                                                    aria-label="Редактировать запись" title="Редактировать">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button type="button"
                                                    @click.stop='openDeleteEntry(<?= $entryPayload ?>)'
                                                    class="flex h-4 w-4 items-center justify-center rounded hover:bg-black/10"
                                                    aria-label="Удалить запись" title="Удалить">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Содержимое записи (ссылка на задачу) -->
                                    <a href="<?= url('/tasks/' . $entry['task_id']) ?>"
                                       class="mt-0.5 block min-w-0"
                                       title="<?= e($entry['project_title'] . ' · ' . $entry['task_title'] . ' · ' . $entry['hours'] . ' ч') ?>">
                                        <span class="font-semibold"><?= e($entryHours) ?>ч</span>
                                        <span class="hidden sm:inline"> · <?= $entry['is_subtask'] ? '↳ ' : '' ?><?= e($entry['task_title']) ?></span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="<?= url('/tasks/' . $entry['task_id']) ?>"
                                   class="block min-w-0 rounded px-1 py-1 text-[10px] sm:text-xs leading-tight <?= $entryClass ?>"
                                   title="<?= e($entry['project_title'] . ' · ' . $entry['task_title'] . ' · ' . $entry['hours'] . ' ч') ?>">
                                    <span class="font-semibold"><?= e($entryHours) ?>ч</span>
                                    <span class="hidden sm:inline"> · <?= $entry['is_subtask'] ? '↳ ' : '' ?><?= e($entry['task_title']) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $trailingBlankDays; $i++): ?>
                <div class="min-h-[64px] sm:min-h-[90px] bg-gray-50"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3 text-xs text-gray-500">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-medium">Проекты:</span>
            <?php if (!empty($projectsMeta)): ?>
                <?php foreach ($projectsMeta as $project): ?>
                    <?php $dotClass = $projectColors[$project['id']]['dot'] ?? 'bg-gray-400'; ?>
                    <span class="flex items-center gap-1">
                        <i class="w-3 h-3 rounded <?= $dotClass ?>"></i>
                        <?= e($project['title']) ?>
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-gray-400">нет записей за месяц</span>
            <?php endif; ?>
        </div>
        <span class="font-semibold text-gray-700 whitespace-nowrap">За месяц: <?= e(rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.')) ?>ч</span>
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

    <!-- Модальное окно редактирования записи времени -->
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-950/45 backdrop-blur-[1px]" @click="editOpen = false" aria-hidden="true"></div>
        <section class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="edit-entry-title">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 id="edit-entry-title" class="text-base font-semibold text-gray-900">Изменить время</h2>
                    <p class="mt-1 text-xs text-gray-500" x-text="editEntry.project_title + ' · ' + editEntry.task_title"></p>
                    <p class="text-xs text-gray-400" x-text="'Дата: ' + formatDate(editEntry.entry_date)"></p>
                </div>
                <button type="button" @click="editOpen = false" class="p-1 text-gray-500 transition hover:text-black" aria-label="Закрыть">×</button>
            </div>

            <p x-show="editError" x-cloak class="mb-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700" x-text="editError"></p>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Часы</label>
                <input x-model="editHours" type="number" min="0.5" max="999.5" step="0.5"
                       class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="editOpen = false" class="ui-btn ui-btn-secondary justify-center">Отмена</button>
                <button type="button" @click="submitEditEntry()" :disabled="editSaving"
                        class="ui-btn ui-btn-primary justify-center"
                        x-text="editSaving ? 'Сохранение…' : 'Сохранить'"></button>
            </div>
        </section>
    </div>

    <!-- Модальное окно подтверждения удаления записи -->
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-950/45 backdrop-blur-[1px]" @click="deleteOpen = false" aria-hidden="true"></div>
        <section class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl sm:p-6"
                 role="alertdialog" aria-modal="true" aria-labelledby="delete-entry-title">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="delete-entry-title" class="text-lg font-semibold text-gray-900">Удалить запись?</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Запись «<span class="font-medium" x-text="deleteEntry.task_title"></span>»
                        (<span x-text="formatHours(deleteEntry.hours)"></span> ч)
                        за <span x-text="formatDate(deleteEntry.entry_date)"></span> будет удалена.
                        Общее время задачи уменьшится на это значение.
                    </p>
                    <p x-show="deleteError" x-cloak class="mt-2 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700" x-text="deleteError"></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="deleteOpen = false" class="ui-btn ui-btn-secondary justify-center">Нет</button>
                <button type="button" @click="confirmDeleteEntry()" :disabled="deleteSaving"
                        class="ui-btn justify-center border-red-600 bg-red-600 text-white hover:border-red-700 hover:bg-red-700 focus-visible:ring-red-500"
                        x-text="deleteSaving ? 'Удаление…' : 'Да, удалить'"></button>
            </div>
        </section>
    </div>

    <!-- Модальное окно подтверждения массового удаления -->
    <div x-show="bulkDeleteOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-gray-950/45 backdrop-blur-[1px]" @click="bulkDeleteOpen = false" aria-hidden="true"></div>
        <section class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl sm:p-6"
                 role="alertdialog" aria-modal="true" aria-labelledby="bulk-delete-title">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="bulk-delete-title" class="text-lg font-semibold text-gray-900">Удалить выбранные записи?</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Будет удалено записей: <span class="font-semibold" x-text="selectedEntries.length"></span>.
                        Общее время соответствующих задач уменьшится. Действие нельзя отменить.
                    </p>
                    <p x-show="bulkError" x-cloak class="mt-2 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700" x-text="bulkError"></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="bulkDeleteOpen = false" class="ui-btn ui-btn-secondary justify-center">Нет</button>
                <button type="button" @click="confirmBulkDelete()" :disabled="bulkSaving"
                        class="ui-btn justify-center border-red-600 bg-red-600 text-white hover:border-red-700 hover:bg-red-700 focus-visible:ring-red-500"
                        x-text="bulkSaving ? 'Удаление…' : 'Да, удалить все'"></button>
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

        // --- Состояние редактирования записи ---
        editOpen: false,
        editEntry: {},
        editHours: '',
        editSaving: false,
        editError: '',

        // --- Состояние удаления записи ---
        deleteOpen: false,
        deleteEntry: {},
        deleteSaving: false,
        deleteError: '',

        // --- Массовый выбор и удаление ---
        selectedEntries: [],   // массив ключей вида "task_id|time_type|entry_date"
        bulkDeleteOpen: false,
        bulkSaving: false,
        bulkError: '',

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
            // Escape закрывает любое открытое окно
            if (this.bulkDeleteOpen) { this.bulkDeleteOpen = false; return; }
            if (this.editOpen) { this.editOpen = false; return; }
            if (this.deleteOpen) { this.deleteOpen = false; return; }
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
        },

        /** Отформатировать число часов без лишних нулей. */
        formatHours(value) {
            const num = parseFloat(value);
            if (isNaN(num)) return value;
            return String(parseFloat(num.toFixed(2)));
        },

        /** Открыть модалку редактирования конкретной записи. */
        openEditEntry(entry) {
            this.editEntry = entry;
            this.editHours = this.formatHours(entry.hours);
            this.editError = '';
            this.editOpen = true;
        },

        /** Сохранить изменённые часы записи. */
        submitEditEntry() {
            this.editError = '';
            if (!this.editHours) {
                this.editError = 'Укажите количество часов';
                return;
            }
            this.editSaving = true;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(BASE_URL + '/calendar/entry/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({
                    task_id: this.editEntry.task_id,
                    time_type: this.editEntry.time_type,
                    entry_date: this.editEntry.entry_date,
                    hours: this.editHours,
                    _token: csrf
                })
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || data.error) {
                        this.editError = data.error || 'Не удалось изменить запись';
                        return;
                    }
                    // Успех — обновляем календарь
                    window.location.reload();
                })
                .catch(() => { this.editError = 'Ошибка сети. Попробуйте ещё раз'; })
                .finally(() => { this.editSaving = false; });
        },

        /** Открыть модалку подтверждения удаления записи. */
        openDeleteEntry(entry) {
            this.deleteEntry = entry;
            this.deleteError = '';
            this.deleteOpen = true;
        },

        /** Подтвердить удаление записи. */
        confirmDeleteEntry() {
            this.deleteError = '';
            this.deleteSaving = true;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(BASE_URL + '/calendar/entry/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({
                    task_id: this.deleteEntry.task_id,
                    time_type: this.deleteEntry.time_type,
                    entry_date: this.deleteEntry.entry_date,
                    _token: csrf
                })
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || data.error) {
                        this.deleteError = data.error || 'Не удалось удалить запись';
                        return;
                    }
                    // Успех — обновляем календарь
                    window.location.reload();
                })
                .catch(() => { this.deleteError = 'Ошибка сети. Попробуйте ещё раз'; })
                .finally(() => { this.deleteSaving = false; });
        },

        /** Открыть модалку подтверждения массового удаления. */
        openBulkDelete() {
            if (this.selectedEntries.length === 0) return;
            this.bulkError = '';
            this.bulkDeleteOpen = true;
        },

        /** Удалить все выбранные записи последовательными запросами. */
        confirmBulkDelete() {
            if (this.selectedEntries.length === 0) return;
            this.bulkError = '';
            this.bulkSaving = true;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            // Каждый ключ имеет формат "task_id|time_type|entry_date"
            const requests = this.selectedEntries.map(key => {
                const [taskId, timeType, entryDate] = key.split('|');
                return fetch(BASE_URL + '/calendar/entry/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({
                        task_id: taskId,
                        time_type: timeType,
                        entry_date: entryDate,
                        _token: csrf
                    })
                }).then(r => r.json().then(data => ({ ok: r.ok, data })));
            });

            Promise.all(requests)
                .then(results => {
                    const failed = results.filter(r => !r.ok || r.data.error);
                    if (failed.length > 0) {
                        this.bulkError = 'Не удалось удалить часть записей (' + failed.length + '). Обновите страницу.';
                        // Всё равно перезагружаем, чтобы показать актуальное состояние
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }
                    window.location.reload();
                })
                .catch(() => { this.bulkError = 'Ошибка сети. Попробуйте ещё раз'; })
                .finally(() => { this.bulkSaving = false; });
        }
    };
}
</script>
