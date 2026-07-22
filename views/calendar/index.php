<?php
$layout = 'layouts/app';
$manualOld = is_array($manualOld ?? null) ? $manualOld : [];
$manualIsOpen = !empty($manualOld);
?>

<div class="space-y-4" x-data="{ manualOpen: <?= $manualIsOpen ? 'true' : 'false' ?> }">
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
                ?>
                <div class="min-w-0 min-h-[64px] sm:min-h-[90px] p-1 sm:p-1.5 <?= $day['today'] ? 'bg-blue-50 ring-2 ring-inset ring-blue-400' : ($day['weekend'] ? 'bg-gray-50' : 'bg-white') ?>">
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
</div>
