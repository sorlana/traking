<?php $layout = 'layouts/app'; ?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Календарь времени</h1>
            <p class="text-sm text-gray-500 mt-1">Дневные затраты текущего пользователя</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('/calendar?month=' . $previousMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Предыдущий месяц">←</a>
            <span class="min-w-[150px] text-center text-sm font-medium text-gray-700"><?= e($monthTitle) ?></span>
            <a href="<?= url('/calendar?month=' . $nextMonth) ?>" class="ui-btn ui-btn-secondary" aria-label="Следующий месяц">→</a>
            <?php if ($currentMonth !== date('Y-m')): ?>
                <a href="<?= url('/calendar') ?>" class="ui-btn ui-btn-light">Сегодня</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($rows)): ?>
            <div class="py-16 px-4 text-center">
                <p class="text-gray-500">В этом месяце пока нет дневных записей времени.</p>
                <p class="text-sm text-gray-400 mt-2">Добавляйте часы кнопкой «+» во вкладке «Информация» задачи.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="border-collapse text-xs" style="min-width:<?= 260 + count($days) * 54 ?>px">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="sticky left-0 z-20 bg-gray-50 w-[260px] min-w-[260px] px-3 py-2 text-left text-gray-600 border-r">Задача</th>
                            <?php foreach ($days as $day): ?>
                                <th class="w-[54px] min-w-[54px] px-1 py-2 text-center border-r <?= $day['today'] ? 'bg-blue-100 text-blue-700' : ($day['weekend'] ? 'bg-gray-100 text-gray-400' : 'text-gray-500') ?>">
                                    <div><?= e($day['weekday']) ?></div>
                                    <div class="font-semibold mt-0.5"><?= e($day['day']) ?></div>
                                </th>
                            <?php endforeach; ?>
                            <th class="w-[70px] min-w-[70px] px-2 py-2 text-center text-gray-600">Итого</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr class="border-b last:border-b-0 hover:bg-gray-50/50">
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 border-r">
                                    <a href="<?= url('/tasks/' . $row['task_id']) ?>" class="font-medium text-gray-700 hover:text-blue-600 block truncate" title="<?= e($row['task_title']) ?>">
                                        <?= $row['is_subtask'] ? '↳ ' : '' ?><?= e($row['task_title']) ?>
                                    </a>
                                    <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                        <?= e($row['project_title']) ?> · <?= $row['time_type'] === 'manager' ? 'Руководитель' : 'Исполнитель' ?>
                                    </div>
                                </td>
                                <?php foreach ($days as $day): ?>
                                    <?php
                                    $hours = (float) ($row['days'][$day['date']] ?? 0);
                                    $intensity = $hours >= 6 ? 'bg-blue-600 text-white' : ($hours >= 4 ? 'bg-blue-500 text-white' : ($hours >= 2 ? 'bg-blue-300 text-blue-900' : ($hours > 0 ? 'bg-blue-100 text-blue-700' : '')));
                                    if ($row['time_type'] === 'manager' && $hours > 0) {
                                        $intensity = $hours >= 4 ? 'bg-purple-500 text-white' : ($hours >= 2 ? 'bg-purple-300 text-purple-900' : 'bg-purple-100 text-purple-700');
                                    }
                                    ?>
                                    <td class="h-11 p-1 border-r text-center <?= $day['weekend'] && $hours === 0.0 ? 'bg-gray-50' : '' ?>">
                                        <?php if ($hours > 0): ?>
                                            <div class="h-full min-h-[34px] rounded flex items-center justify-center font-semibold <?= $intensity ?>" title="<?= e($day['date']) ?>: <?= e($hours) ?> ч">
                                                <?= e(rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.')) ?>ч
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-2 py-2 text-center font-semibold text-gray-700"><?= e(rtrim(rtrim(number_format($row['total'], 2, '.', ''), '0'), '.')) ?>ч</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t font-semibold text-gray-600">
                            <td class="sticky left-0 z-10 bg-gray-50 px-3 py-2 border-r">Всего за день</td>
                            <?php foreach ($days as $day): ?>
                                <?php $total = (float) ($dayTotals[$day['date']] ?? 0); ?>
                                <td class="px-1 py-2 text-center border-r"><?= $total > 0 ? e(rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.')) : '—' ?></td>
                            <?php endforeach; ?>
                            <td class="px-2 py-2 text-center text-blue-700"><?= e(rtrim(rtrim(number_format($grandTotal, 2, '.', ''), '0'), '.')) ?>ч</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
        <span class="font-medium">Легенда:</span>
        <span class="flex items-center gap-1"><i class="w-3 h-3 rounded bg-blue-300"></i> время исполнителя</span>
        <span class="flex items-center gap-1"><i class="w-3 h-3 rounded bg-purple-300"></i> время руководителя</span>
    </div>
</div>
