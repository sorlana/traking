<?php
/**
 * Форма создания/редактирования задачи — views/tasks/form.php
 *
 * Поля: проект, родительская задача, название, описание,
 * приоритет, срок, исполнитель, статус.
 */
$layout = 'layouts/app';

$isEdit = !empty($task);
$oldData = $old ?? [];

// Значения полей (old → task → default)
$fieldTitle = $oldData['title'] ?? ($task['title'] ?? '');
$fieldDescription = $oldData['description'] ?? ($task['description'] ?? '');
$fieldPriority = $oldData['priority'] ?? ($task['priority'] ?? 'medium');
$fieldDeadline = $oldData['deadline'] ?? ($task['deadline'] ?? '');
$fieldStatusId = $oldData['status_id'] ?? ($task['status_id'] ?? '');
$fieldAssignedTo = $oldData['assigned_to'] ?? ($task['assigned_to'] ?? '');
$fieldProjectId = $oldData['project_id'] ?? ($project['id'] ?? ($_GET['project_id'] ?? ''));
$fieldParentId = $oldData['parent_id'] ?? ($parentTask['id'] ?? ($task['parent_id'] ?? ($_GET['parent_id'] ?? '')));
require_once BASE_PATH . '/views/components/source-image-picker.php';

// Автовыбор исполнителя: если создание задачи и в проекте один исполнитель
if (!$isEdit && empty($fieldAssignedTo) && !empty($projectUsers)) {
    $executors = array_filter($projectUsers, fn($pu) => $pu['project_role'] === 'executor');
    if (count($executors) === 1) {
        $fieldAssignedTo = (int) reset($executors)['id'];
    }
}
?>

<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6 flex-wrap">
        <a href="<?= url('/tasks') ?>" class="hover:text-blue-600">Задачи</a>
        <?php if ($project ?? null): ?>
            <span>→</span>
            <a href="<?= url('/projects/' . (int) $project['id']) ?>" class="hover:text-blue-600"><?= e($project['title']) ?></a>
        <?php endif; ?>
        <?php if ($parentTask ?? null): ?>
            <span>→</span>
            <a href="<?= url('/tasks/' . (int) $parentTask['id']) ?>" class="hover:text-blue-600"><?= e($parentTask['title']) ?></a>
        <?php endif; ?>
        <span>→</span>
        <span class="text-gray-800"><?= $isEdit ? 'Редактирование' : 'Создание задачи' ?></span>
    </nav>

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h1 class="text-xl font-bold text-gray-800 mb-6">
            <?= $isEdit ? 'Редактирование задачи' : 'Создание задачи' ?>
        </h1>

        <!-- Ошибки валидации -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="text-sm text-red-700 space-y-1">
                    <?php foreach ($errors as $field => $fieldErrors): ?>
                        <?php foreach ($fieldErrors as $error): ?>
                            <li>• <?= e($error) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $isEdit ? url('/tasks/' . (int) $task['id'] . '/edit') : url('/tasks/create') ?>" x-data="{ showExtra: false }">
            <?= csrf_field() ?>

            <!-- Проект (select если не указан, hidden если указан) -->
            <?php if ($project ?? null): ?>
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
            <?php elseif (!empty($projects)): ?>
                <div class="mb-4">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Проект <span class="text-red-500">*</span>
                    </label>
                    <select name="project_id" id="project_id" required
                            class="ui-control">
                        <option value="">Выберите проект</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (int) $fieldProjectId === (int) $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Родительская задача (hidden) -->
            <?php if (!empty($fieldParentId)): ?>
                <input type="hidden" name="parent_id" value="<?= (int) $fieldParentId ?>">
            <?php endif; ?>

            <!-- === Основные поля (всегда видны) === -->

            <!-- Название (всегда) -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Название <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="<?= e($fieldTitle) ?>" required
                       maxlength="255" placeholder="Название задачи"
                       class="ui-control">
            </div>

            <?php if (!$isEdit && !empty($fieldParentId)): ?>
                <div class="mb-4">
                    <?php renderSourceImagePicker($parentChatImages ?? [], 'full-form', !empty($oldData['source_image_id']) ? (int) $oldData['source_image_id'] : null); ?>
                </div>
            <?php endif; ?>

            <!-- Описание (показываем если заполнено) -->
            <?php if (!empty($fieldDescription)): ?>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                <textarea name="description" id="description" rows="3"
                          class="ui-control"><?= e($fieldDescription) ?></textarea>
            </div>
            <?php endif; ?>

            <!-- Исполнитель (показываем если назначен) -->
            <?php if (!empty($fieldAssignedTo)): ?>
            <div class="mb-4">
                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Исполнитель</label>
                <select name="assigned_to" id="assigned_to"
                        class="ui-control">
                    <option value="">Не назначен</option>
                    <?php foreach ($projectUsers as $pu): ?>
                        <option value="<?= (int) $pu['id'] ?>" <?= (int) $fieldAssignedTo === (int) $pu['id'] ? 'selected' : '' ?>>
                            <?= e($pu['name']) ?> (<?= $pu['project_role'] === 'manager' ? 'руководитель' : 'исполнитель' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Срок (показываем если установлен) -->
            <?php if (!empty($fieldDeadline)): ?>
            <div class="mb-4">
                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1">Срок выполнения</label>
                <input type="date" name="deadline" id="deadline" value="<?= e($fieldDeadline) ?>"
                       class="ui-control">
            </div>
            <?php endif; ?>

            <!-- Приоритет (показываем если не medium) -->
            <?php if ($fieldPriority !== 'medium'): ?>
            <div class="mb-4">
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                <select name="priority" id="priority"
                        class="ui-control">
                    <option value="low" <?= $fieldPriority === 'low' ? 'selected' : '' ?>>Низкий</option>
                    <option value="medium" <?= $fieldPriority === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="high" <?= $fieldPriority === 'high' ? 'selected' : '' ?>>Высокий</option>
                    <option value="urgent" <?= $fieldPriority === 'urgent' ? 'selected' : '' ?>>Срочный</option>
                </select>
            </div>
            <?php endif; ?>

            <!-- === Ссылка «Дополнительно» === -->
            <div class="mb-4">
                <button type="button" @click="showExtra = !showExtra"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                    <span x-text="showExtra ? 'Скрыть' : 'Дополнительно'"></span>
                    <svg class="w-4 h-4 transition-transform" :class="showExtra ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <!-- === Дополнительные поля (скрытые по умолчанию) === -->
            <div x-show="showExtra" x-transition class="space-y-4 mb-6" style="display: none;">

                <!-- Описание (если ещё не показано выше) -->
                <?php if (empty($fieldDescription)): ?>
                <div>
                    <label for="description_extra" class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                    <textarea name="description" id="description_extra" rows="3" placeholder="Описание задачи"
                              class="ui-control"><?= e($fieldDescription) ?></textarea>
                </div>
                <?php endif; ?>

                <!-- Исполнитель (если не показан выше) -->
                <?php if (empty($fieldAssignedTo)): ?>
                <div>
                    <label for="assigned_to_extra" class="block text-sm font-medium text-gray-700 mb-1">Исполнитель</label>
                    <select name="assigned_to" id="assigned_to_extra"
                            class="ui-control">
                        <option value="">Не назначен</option>
                        <?php foreach ($projectUsers as $pu): ?>
                            <option value="<?= (int) $pu['id'] ?>" <?= (int) $fieldAssignedTo === (int) $pu['id'] ? 'selected' : '' ?>>
                                <?= e($pu['name']) ?> (<?= $pu['project_role'] === 'manager' ? 'руководитель' : 'исполнитель' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Срок (если не показан выше) -->
                <?php if (empty($fieldDeadline)): ?>
                <div>
                    <label for="deadline_extra" class="block text-sm font-medium text-gray-700 mb-1">Срок выполнения</label>
                    <input type="date" name="deadline" id="deadline_extra"
                           class="ui-control">
                </div>
                <?php endif; ?>

                <!-- Приоритет (если не показан выше) -->
                <?php if ($fieldPriority === 'medium'): ?>
                <div>
                    <label for="priority_extra" class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                    <select name="priority" id="priority_extra"
                            class="ui-control">
                        <option value="low">Низкий</option>
                        <option value="medium" selected>Средний</option>
                        <option value="high">Высокий</option>
                        <option value="urgent">Срочный</option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Статус -->
                <?php if (!empty($statuses)): ?>
                <div>
                    <label for="status_id" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                    <select name="status_id" id="status_id"
                            class="ui-control">
                        <option value="">Автоматически (В работе)</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $fieldStatusId === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- Кнопки -->
            <div class="flex items-center gap-3 pt-4 border-t">
                <button type="submit"
                        class="ui-btn ui-btn-primary">
                    <?= $isEdit ? 'Сохранить изменения' : 'Создать задачу' ?>
                </button>
                <a href="<?= $isEdit ? url('/tasks/' . (int) $task['id']) : url('/tasks') ?>"
                   class="ui-btn ui-btn-secondary">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>
