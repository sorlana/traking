<?php
/**
 * Шаблон списка проектов
 * Карточки проектов + фильтры в модальном окне
 */
$layout = 'layouts/app';

// Проверка: есть ли активные фильтры
$hasFilters = !empty($filters['status']) || !empty($filters['manager']) || !empty($filters['executor']) || !empty($filters['deadline']);
?>

<div x-data="{ showFilters: false }">

    <!-- Заголовок + Создать + Фильтры -->
    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-bold text-gray-800">Проекты</h1>

        <div class="flex items-center gap-2">
            <?php if (can('create_project')): ?>
                <a href="<?= url('/projects/create') ?>"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    Создать
                </a>
            <?php endif; ?>
            <button @click="showFilters = true"
                    class="lg:hidden px-4 py-2 bg-white border rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition <?= $hasFilters ? 'border-blue-500 text-blue-700' : '' ?>">
                Фильтры<?= $hasFilters ? ' ●' : '' ?>
            </button>
        </div>
    </div>

    <!-- Десктопные фильтры (lg+) -->
    <form method="GET" action="<?= url('/projects') ?>" class="hidden lg:block bg-white rounded-lg shadow-sm border p-4 mb-6">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select name="status" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Все статусы</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['code']) ?>" <?= $filters['status'] === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
                <select name="manager" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Все</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= $filters['manager'] == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                <select name="executor" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Все</option>
                    <?php foreach ($executors as $ex): ?>
                        <option value="<?= e($ex['id']) ?>" <?= $filters['executor'] == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">Применить</button>
                <a href="<?= url('/projects') ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">Сбросить</a>
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
                            'new' => 'bg-blue-100 text-blue-700',
                            'active' => 'bg-green-100 text-green-700',
                            'on_hold' => 'bg-yellow-100 text-yellow-700',
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
                                <span class="text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded">
                                    открытых: <?= (int) $project['task_open'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

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

    <!-- Модалка: Фильтры -->
    <div x-show="showFilters" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="showFilters = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">Фильтры</h2>
                <button @click="showFilters = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <form method="GET" action="<?= url('/projects') ?>" class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                    <select name="status" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="">Все статусы</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s['code']) ?>" <?= $filters['status'] === $s['code'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
                    <select name="manager" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="">Все</option>
                        <?php foreach ($managers as $m): ?>
                            <option value="<?= e($m['id']) ?>" <?= $filters['manager'] == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
                    <select name="executor" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="">Все</option>
                        <?php foreach ($executors as $ex): ?>
                            <option value="<?= e($ex['id']) ?>" <?= $filters['executor'] == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
                    <select name="deadline" class="w-full border-gray-300 rounded-md text-sm">
                        <option value="">Все</option>
                        <option value="overdue" <?= $filters['deadline'] === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                        <option value="week" <?= $filters['deadline'] === 'week' ? 'selected' : '' ?>>На этой неделе</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">Применить</button>
                    <a href="<?= url('/projects') ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300 transition">Сбросить</a>
                </div>
            </form>
        </div>
    </div>
</div>
