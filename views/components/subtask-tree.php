<?php
/**
 * Рекурсивный компонент дерева доработок
 *
 * Принимает: $nodes (массив задач с вложенными children), $statusColors, $statusDots, $depth
 * Рекурсивно отрисовывает все уровни вложенности.
 */

if (!function_exists('renderSubtaskTree')) {
    function renderSubtaskTree(array $nodes, array $statusColors, array $statusDots, int $depth = 0): void
    {
        foreach ($nodes as $child):
            $childOverdue = !empty($child['deadline'])
                && strtotime($child['deadline']) < strtotime(date('Y-m-d'))
                && ($child['status_code'] ?? '') !== 'done'
                && ($child['status_code'] ?? '') !== 'closed';
            $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400';
            $indent = $depth * 16; // px отступ для вложенности
            $hasChildren = !empty($child['children']);
        ?>
            <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>" style="margin-left: <?= $indent ?>px;">
                <?php if ($hasChildren): ?>
                    <span class="w-2 h-2 flex-shrink-0 text-gray-400">↳</span>
                <?php endif; ?>
                <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                <a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate"><?= e($child['title']) ?></a>
                <?php if (!empty($child['deadline'])): ?>
                    <span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?> flex-shrink-0"><?= date('d.m', strtotime($child['deadline'])) ?></span>
                <?php endif; ?>
                <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>"><?= e($child['status_name'] ?? '') ?></span>
            </div>
            <?php if ($hasChildren): ?>
                <?php renderSubtaskTree($child['children'], $statusColors, $statusDots, $depth + 1); ?>
            <?php endif; ?>
        <?php endforeach;
    }
}
?>
