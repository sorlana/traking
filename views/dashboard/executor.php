<?php
/**
 * Дашборд исполнителя — views/dashboard/executor.php
 *
 * Виджеты: мои задачи (по статусам), новые назначения, задачи на проверке.
 */
$layout = 'layouts/app';

// Группировка задач по статусам
$tasksByStatus = [];
foreach ($myTasks as $task) {
    $code = $task['status_code'] ?? 'new';
    $tasksByStatus[$code][] = $task;
}

$statusOrder = ['in_progress', 'new', 'review', 'done'];
$statusLabels = [
    'in_progress' => ['label' => 'В работе', 'color' => 'border-yellow-400', 'bg' => 'bg-yellow-50'],
    'new' => ['label' => 'Новые', 'color' => 'border-blue-400', 'bg' => 'bg-blue-50'],
    'review' => ['label' => 'На проверке', 'color' => 'border-purple-400', 'bg' => 'bg-purple-50'],
    'done' => ['label' => 'Выполнено', 'color' => 'border-green-400', 'bg' => 'bg-green-50'],
];
?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Мои задачи</h1>

    <!-- Счётчики -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($statusOrder as $code): ?>
            <?php
            $meta = $statusLabels[$code] ?? ['label' => $code, 'color' => 'border-gray-300', 'bg' => 'bg-gray-50'];
            $count = count($tasksByStatus[$code] ?? []);
            ?>
            <div class="bg-white rounded-lg shadow-sm border-l-4 <?= $meta['color'] ?> p-4">
                <p class="text-xs text-gray-500"><?= $meta['label'] ?></p>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= $count ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Задачи в работе + новые -->
        <div class="space-y-6">
            <?php foreach (['in_progress', 'new'] as $code): ?>
                <?php if (!empty($tasksByStatus[$code])): ?>
                    <?php $meta = $statusLabels[$code]; ?>
                    <div class="bg-white rounded-lg shadow-sm border p-5">
                        <h3 class="text-sm font-medium text-gray-500 mb-3">
                            <?= $meta['label'] ?>
                            <span class="inline-block px-1.5 py-0.5 <?= $meta['bg'] ?> text-gray-700 rounded text-xs ml-1">
                                <?= count($tasksByStatus[$code]) ?>
                            </span>
                        </h3>
                        <div class="space-y-2">
                            <?php foreach ($tasksByStatus[$code] as $task): ?>
                                <?php
                                $isOverdue = !empty($task['deadline']) && strtotime($task['deadline']) < strtotime(date('Y-m-d'));
                                ?>
                                <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 <?= $isOverdue ? 'bg-red-50' : '' ?>">
                                    <div class="flex-1 min-w-0">
                                        <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                            <?= e($task['title']) ?>
                                        </a>
                                        <p class="text-xs text-gray-500"><?= e($task['project_title'] ?? '') ?></p>
                                    </div>
                                    <?php if (!empty($task['deadline'])): ?>
                                        <span class="text-xs flex-shrink-0 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?>">
                                            <?= date('d.m', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Правая колонка -->
        <div class="space-y-6">
            <!-- На проверке -->
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">
                    На проверке
                    <?php if (!empty($reviewTasks)): ?>
                        <span class="inline-block px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-xs ml-1">
                            <?= count($reviewTasks) ?>
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($reviewTasks)): ?>
                    <p class="text-sm text-gray-400">Нет задач на проверке</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($reviewTasks as $task): ?>
                            <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                                <div class="flex-1 min-w-0">
                                    <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                        <?= e($task['title']) ?>
                                    </a>
                                    <p class="text-xs text-gray-500"><?= e($task['project_title'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Новые назначения -->
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">
                    Новые назначения (7 дней)
                    <?php if (!empty($newAssigned)): ?>
                        <span class="inline-block px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs ml-1">
                            <?= count($newAssigned) ?>
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($newAssigned)): ?>
                    <p class="text-sm text-gray-400">Нет новых назначений</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($newAssigned as $task): ?>
                            <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 bg-blue-50">
                                <div class="flex-1 min-w-0">
                                    <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                        <?= e($task['title']) ?>
                                    </a>
                                    <p class="text-xs text-gray-500">
                                        <?= e($task['project_title'] ?? '') ?>
                                        <?php if (!empty($task['deadline'])): ?>
                                            • до <?= date('d.m', strtotime($task['deadline'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 flex-shrink-0">
                                    <?= date('d.m', strtotime($task['created_at'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
