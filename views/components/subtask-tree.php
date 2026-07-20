<?php
/**
 * Рекурсивный компонент дерева доработок
 *
 * Принимает: $nodes (массив задач с вложенными children), $statusColors, $statusDots, $depth
 * Рекурсивно отрисовывает все уровни вложенности.
 */

if (!function_exists('renderSubtaskTree')) {
    function renderSubtaskTree(
        array $nodes,
        array $statusColors,
        array $statusDots,
        int $depth = 0,
        bool $allowDelete = false,
        int $rootTaskId = 0,
        string $bulkFormId = ''
    ): void
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
            <div x-data="{ editing: false }" class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>" style="margin-left: <?= $indent ?>px;">
                <?php if ($allowDelete): ?>
                    <input type="checkbox" name="task_ids[]" value="<?= (int) $child['id'] ?>"
                           <?= $bulkFormId !== '' ? 'form="' . e($bulkFormId) . '"' : '' ?>
                           class="subtask-select h-4 w-4 flex-shrink-0 rounded border-gray-300 text-blue-600"
                           @change="selected = $el.closest('[data-subtask-panel]').querySelectorAll('.subtask-select:checked').length"
                           aria-label="Выбрать доработку <?= e($child['title']) ?>">
                <?php endif; ?>
                <?php if ($hasChildren): ?>
                    <span class="w-2 h-2 flex-shrink-0 text-gray-400">↳</span>
                <?php endif; ?>
                <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                <a x-show="!editing" href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1 truncate"><?= e($child['title']) ?></a>
                <?php if ($allowDelete): ?>
                    <form x-show="editing" x-cloak method="POST"
                          action="<?= url('/tasks/' . $rootTaskId . '/subtasks/' . (int) $child['id'] . '/edit') ?>"
                          class="flex min-w-0 flex-1 items-center gap-1">
                        <?= csrf_field() ?>
                        <input type="text" name="title" value="<?= e($child['title']) ?>" maxlength="255" required
                               @keydown.escape.prevent="editing = false"
                               class="min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                               aria-label="Новое название доработки">
                        <button type="submit" class="a11y-icon-button text-blue-600 hover:text-blue-800" aria-label="Сохранить название" title="Сохранить">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button type="button" @click="editing = false" class="a11y-icon-button text-gray-400 hover:text-gray-700" aria-label="Отменить редактирование" title="Отмена">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($child['deadline'])): ?>
                    <span x-show="!editing" class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?> flex-shrink-0"><?= date('d.m', strtotime($child['deadline'])) ?></span>
                <?php endif; ?>
                <span x-show="!editing" class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>"><?= e($child['status_name'] ?? '') ?></span>
                <?php if ($allowDelete): ?>
                    <button x-show="!editing" type="button" @click="editing = true; $nextTick(() => $root.querySelector('input[name=title]')?.select())"
                            class="a11y-icon-button flex-shrink-0 text-gray-400 hover:text-blue-600"
                            aria-label="Редактировать доработку <?= e($child['title']) ?>" title="Редактировать">
                        <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <form x-show="!editing" method="POST" action="<?= url('/tasks/' . $rootTaskId . '/subtasks/' . (int) $child['id'] . '/delete') ?>" class="flex-shrink-0">
                        <?= csrf_field() ?>
                        <button type="submit"
                                onclick="return confirm(<?= e(json_encode('Удалить доработку «' . $child['title'] . '» и все вложенные элементы?', JSON_UNESCAPED_UNICODE)) ?>)"
                                class="a11y-icon-button text-gray-400 hover:text-red-600"
                                aria-label="Удалить доработку <?= e($child['title']) ?>" title="Удалить">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($hasChildren): ?>
                <?php renderSubtaskTree($child['children'], $statusColors, $statusDots, $depth + 1, $allowDelete, $rootTaskId, $bulkFormId); ?>
            <?php endif; ?>
        <?php endforeach;
    }
}
?>
