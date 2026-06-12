<?php
/**
 * Визуальное дерево задач — views/tasks/tree.php
 *
 * Рекурсивный компонент отрисовки дерева задач проекта.
 * Для каждого узла: статус (точка цветная), название (ссылка), исполнитель, срок.
 */
$layout = 'layouts/app';

// Цвета точек статусов
$statusDots = [
    'new' => 'bg-blue-500',
    'in_progress' => 'bg-yellow-500',
    'review' => 'bg-purple-500',
    'done' => 'bg-green-500',
    'closed' => 'bg-gray-400',
    'cancelled' => 'bg-red-400',
];

$statusColors = [
    'new' => 'bg-blue-100 text-blue-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'review' => 'bg-purple-100 text-purple-800',
    'done' => 'bg-green-100 text-green-800',
    'closed' => 'bg-gray-100 text-gray-800',
    'cancelled' => 'bg-red-100 text-red-800',
];

/**
 * Рекурсивная функция отрисовки узла дерева
 */
function renderTreeNode(array $node, array $statusDots, array $statusColors, int $depth = 0): string
{
    $dotColor = $statusDots[$node['status_code'] ?? ''] ?? 'bg-gray-400';
    $isOverdue = !empty($node['deadline'])
        && strtotime($node['deadline']) < strtotime(date('Y-m-d'))
        && !in_array($node['status_code'] ?? '', ['closed', 'cancelled', 'done']);

    $indent = $depth * 24; // px отступ

    $html = '<div class="tree-node" style="margin-left: ' . $indent . 'px;">';
    $html .= '<div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 group ' . ($isOverdue ? 'bg-red-50' : '') . '">';

    // Точка статуса
    $html .= '<span class="w-2.5 h-2.5 rounded-full flex-shrink-0 ' . $dotColor . '"></span>';

    // Название (ссылка)
    $html .= '<a href="/tasks/' . (int) $node['id'] . '" class="text-sm text-gray-800 hover:text-blue-600 font-medium flex-1 truncate">';
    $html .= htmlspecialchars($node['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $html .= '</a>';

    // Исполнитель
    if (!empty($node['assigned_name'])) {
        $html .= '<span class="text-xs text-gray-400 hidden sm:inline">' . htmlspecialchars($node['assigned_name'], ENT_QUOTES, 'UTF-8') . '</span>';
    }

    // Срок
    if (!empty($node['deadline'])) {
        $deadlineClass = $isOverdue ? 'text-red-600 font-medium' : 'text-gray-400';
        $html .= '<span class="text-xs ' . $deadlineClass . ' hidden sm:inline">' . date('d.m', strtotime($node['deadline'])) . '</span>';
    }

    // Статус badge
    $sClass = $statusColors[$node['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600';
    $html .= '<span class="text-xs px-1.5 py-0.5 rounded ' . $sClass . ' hidden md:inline">';
    $html .= htmlspecialchars($node['status_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $html .= '</span>';

    $html .= '</div>'; // Закрываем flex

    // Рекурсивно рендерим дочерние
    if (!empty($node['children'])) {
        foreach ($node['children'] as $child) {
            $html .= renderTreeNode($child, $statusDots, $statusColors, $depth + 1);
        }
    }

    $html .= '</div>'; // Закрываем tree-node

    return $html;
}
?>

<div class="space-y-6">
    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Дерево задач
                <?php if (!empty($project)): ?>
                    — <?= e($project['title'] ?? '') ?>
                <?php endif; ?>
            </h1>
            <?php if (!empty($project)): ?>
                <a href="/projects/<?= (int) $project['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800">← К проекту</a>
            <?php endif; ?>
        </div>

        <div class="flex gap-2">
            <a href="/tasks<?= !empty($project) ? '?project_id=' . (int) $project['id'] : '' ?>"
               class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                Список
            </a>
        </div>
    </div>

    <!-- Легенда статусов -->
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="flex flex-wrap gap-4 text-xs">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Новая</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> В работе</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> На проверке</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Выполнена</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> Закрыта</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span> Отменена</span>
        </div>
    </div>

    <!-- Дерево -->
    <div class="bg-white rounded-lg shadow-sm border p-5">
        <?php if (empty($tree)): ?>
            <p class="text-sm text-gray-400 text-center py-4">Задач не найдено</p>
        <?php else: ?>
            <div class="space-y-0.5">
                <?php foreach ($tree as $rootNode): ?>
                    <?= renderTreeNode($rootNode, $statusDots, $statusColors, 0) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
