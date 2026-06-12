<?php
/**
 * Дашборд руководителя — views/dashboard/manager.php
 *
 * Виджеты: мои проекты, задачи на проверке, просроченные задачи, новые комментарии.
 */
$layout = 'layouts/app';
?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Дашборд</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Мои проекты -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Мои проекты</h3>
                <a href="/projects" class="text-xs text-blue-600 hover:text-blue-800">Все проекты →</a>
            </div>

            <?php if (empty($myProjects)): ?>
                <p class="text-sm text-gray-400">Нет проектов</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($myProjects as $project): ?>
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50">
                            <div class="min-w-0">
                                <a href="/projects/<?= (int) $project['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                    <?= e($project['title']) ?>
                                </a>
                                <p class="text-xs text-gray-500">
                                    <?= e($project['status_name'] ?? '') ?> • <?= (int) $project['task_count'] ?> задач
                                </p>
                            </div>
                            <?php if (!empty($project['deadline'])): ?>
                                <span class="text-xs text-gray-400 flex-shrink-0">
                                    до <?= date('d.m', strtotime($project['deadline'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Задачи на проверке -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-4">
                На проверке
                <?php if (!empty($reviewTasks)): ?>
                    <span class="inline-block px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-xs ml-1"><?= count($reviewTasks) ?></span>
                <?php endif; ?>
            </h3>

            <?php if (empty($reviewTasks)): ?>
                <p class="text-sm text-gray-400">Нет задач на проверке</p>
            <?php else: ?>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($reviewTasks as $task): ?>
                        <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50">
                            <div class="flex-1 min-w-0">
                                <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                    <?= e($task['title']) ?>
                                </a>
                                <p class="text-xs text-gray-500">
                                    <?= e($task['project_title'] ?? '') ?> • <?= e($task['assigned_name'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Просроченные задачи -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-4">
                Просроченные
                <?php if (!empty($overdueTasks)): ?>
                    <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-xs ml-1"><?= count($overdueTasks) ?></span>
                <?php endif; ?>
            </h3>

            <?php if (empty($overdueTasks)): ?>
                <p class="text-sm text-gray-400">Просроченных задач нет 🎉</p>
            <?php else: ?>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($overdueTasks as $task): ?>
                        <div class="flex items-center gap-3 p-2 bg-red-50 rounded">
                            <div class="flex-1 min-w-0">
                                <a href="/tasks/<?= (int) $task['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium truncate block">
                                    <?= e($task['title']) ?>
                                </a>
                                <p class="text-xs text-gray-500">
                                    <?= e($task['project_title'] ?? '') ?> • <?= e($task['assigned_name'] ?? 'Не назначен') ?>
                                </p>
                            </div>
                            <span class="text-xs text-red-600 font-medium flex-shrink-0">
                                <?= date('d.m', strtotime($task['deadline'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Новые комментарии -->
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Новые комментарии</h3>

            <?php if (empty($recentComments)): ?>
                <p class="text-sm text-gray-400">Нет новых комментариев</p>
            <?php else: ?>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <?php foreach ($recentComments as $comment): ?>
                        <div class="p-2 rounded hover:bg-gray-50">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-medium text-gray-700"><?= e($comment['user_name'] ?? '') ?></span>
                                <span class="text-xs text-gray-400"><?= date('d.m H:i', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <a href="/tasks/<?= (int) $comment['task_id'] ?>" class="text-sm text-blue-600 hover:text-blue-800">
                                <?= e($comment['task_title'] ?? '') ?>
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                                <?= e(mb_substr($comment['comment_text'] ?? '', 0, 100)) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
