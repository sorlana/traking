<?php
/**
 * Визуальное дерево задач — views/tasks/tree.php
 *
 * Рекурсивный компонент отрисовки дерева задач проекта.
 * Для каждого узла: название (ссылка), исполнитель, срок и бейдж статуса.
 */
$layout = 'layouts/app';

$statusColors = [
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'revision' => 'bg-orange-100 text-orange-800',
    'done' => 'bg-green-100 text-green-800',
];

/**
 * Рекурсивная функция отрисовки узла дерева
 */
function renderTreeNode(array $node, array $statusColors, int $depth = 0): string
{
    $isOverdue = !empty($node['deadline'])
        && strtotime($node['deadline']) < strtotime(date('Y-m-d'))
        && ($node['status_code'] ?? '') !== 'done';

    $indent = $depth * 24; // px отступ

    $html = '<div class="tree-node" style="margin-left: ' . $indent . 'px;">';
    $html .= '<div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 group ' . ($isOverdue ? 'bg-red-50' : '') . '">';

    // Название (ссылка)
    $html .= '<a href="' . url('/tasks/' . (int) $node['id']) . '" class="text-sm text-gray-800 hover:text-blue-600 font-medium flex-1 truncate">';
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
            $html .= renderTreeNode($child, $statusColors, $depth + 1);
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
                <a href="<?= url('/projects/' . (int) $project['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800">← К проекту</a>
            <?php endif; ?>
        </div>

        <div class="flex gap-2">
            <a href="<?= url('/tasks') ?><?= !empty($project) ? '?project_id=' . (int) $project['id'] : '' ?>"
               class="ui-btn ui-btn-secondary">
                Список
            </a>
        </div>
    </div>

    <!-- Дерево -->
    <div class="bg-white rounded-lg shadow-sm border p-5">
        <?php if (empty($tree)): ?>
            <p class="text-sm text-gray-400 text-center py-4">Задач не найдено</p>
        <?php else: ?>
            <div class="space-y-0.5">
                <?php foreach ($tree as $rootNode): ?>
                    <?= renderTreeNode($rootNode, $statusColors, 0) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
