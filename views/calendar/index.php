<?php $layout = 'layouts/app'; ?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Календарь времени</h1>
        </div>
        <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2">
            <a href="<?= url('/calendar?month=' . $previousMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Предыдущий месяц">←</a>
            <span class="min-w-[145px] text-center text-sm font-semibold text-gray-700"><?= e($monthTitle) ?></span>
            <a href="<?= url('/calendar?month=' . $nextMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Следующий месяц">→</a>
            <?php if ($currentMonth !== date('Y-m')): ?>
                <a href="<?= url('/calendar') ?>" class="ui-btn ui-btn-light">Сегодня</a>
            <?php endif; ?>
        </div>
    </div>

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
