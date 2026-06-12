<?php
/**
 * Шаблон списка проектов
 * Отображает карточки проектов в grid-сетке с фильтрами
 */
$layout = 'layouts/app';
?>

<!-- Заголовок и кнопка создания -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Проекты</h1>

    <?php if (can('create_project')): ?>
        <a href="/projects/create"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать проект
        </a>
    <?php endif; ?>
</div>

<!-- Фильтры -->
<form method="GET" action="/projects" class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Статус -->
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
            <select name="status" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Все статусы</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s['code']) ?>" <?= $filters['status'] === $s['code'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Руководитель -->
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Руководитель</label>
            <select name="manager" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Все</option>
                <?php foreach ($managers as $m): ?>
                    <option value="<?= e($m['id']) ?>" <?= $filters['manager'] == $m['id'] ? 'selected' : '' ?>>
                        <?= e($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Исполнитель -->
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Исполнитель</label>
            <select name="executor" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Все</option>
                <?php foreach ($executors as $ex): ?>
                    <option value="<?= e($ex['id']) ?>" <?= $filters['executor'] == $ex['id'] ? 'selected' : '' ?>>
                        <?= e($ex['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Срок -->
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Срок</label>
            <select name="deadline" class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Все</option>
                <option value="overdue" <?= $filters['deadline'] === 'overdue' ? 'selected' : '' ?>>Просроченные</option>
                <option value="week" <?= $filters['deadline'] === 'week' ? 'selected' : '' ?>>На этой неделе</option>
            </select>
        </div>
    </div>

    <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium transition">
            Применить
        </button>
        <a href="/projects" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition">
            Сбросить
        </a>
    </div>
</form>

<!-- Сетка проектов -->
<?php if (empty($projects)): ?>
    <div class="text-center py-12">
        <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <p class="text-gray-500">Проектов пока нет</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($projects as $project): ?>
            <a href="/projects/<?= e($project['id']) ?>"
               class="bg-white rounded-lg shadow-sm border hover:shadow-md transition p-5 block">
                <!-- Заголовок и статус -->
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h3 class="text-base font-semibold text-gray-800 line-clamp-2"><?= e($project['title']) ?></h3>
                    <?php
                    $statusColors = [
                        'new' => 'bg-blue-100 text-blue-700',
                        'active' => 'bg-green-100 text-green-700',
                        'on_hold' => 'bg-yellow-100 text-yellow-700',
                        'closed' => 'bg-gray-100 text-gray-600',
                    ];
                    $colorClass = $statusColors[$project['status_code']] ?? 'bg-gray-100 text-gray-600';
                    ?>
                    <span class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap <?= $colorClass ?>">
                        <?= e($project['status_name']) ?>
                    </span>
                </div>

                <!-- Информация -->
                <div class="space-y-2 text-sm text-gray-600">
                    <!-- Срок -->
                    <?php if ($project['deadline']): ?>
                        <?php
                        $isOverdue = $project['deadline'] < date('Y-m-d') && $project['status_code'] !== 'closed';
                        ?>
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

                    <!-- Задачи -->
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

                    <!-- Руководители -->
                    <?php if (!empty($project['managers'])): ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="truncate">
                                <?= e(implode(', ', array_column($project['managers'], 'name'))) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
