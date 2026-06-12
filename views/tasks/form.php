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

        <form method="POST" action="<?= $isEdit ? url('/tasks/' . (int) $task['id'] . '/edit') : url('/tasks/create') ?>">
            <?= csrf_field() ?>

            <!-- Проект (select если не указан, hidden если указан) -->
            <?php if ($project ?? null): ?>
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Проект</label>
                    <p class="text-sm text-gray-600 bg-gray-50 rounded-md px-3 py-2"><?= e($project['title']) ?></p>
                </div>
            <?php elseif (!empty($projects)): ?>
                <div class="mb-4">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Проект <span class="text-red-500">*</span>
                    </label>
                    <select name="project_id" id="project_id" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                <?php if ($parentTask ?? null): ?>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Родительская задача</label>
                        <p class="text-sm text-gray-600 bg-gray-50 rounded-md px-3 py-2">
                            <a href="<?= url('/tasks/' . (int) $parentTask['id']) ?>" class="text-blue-600 hover:text-blue-800">
                                <?= e($parentTask['title']) ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Название -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Название <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="<?= e($fieldTitle) ?>" required
                       maxlength="255" placeholder="Название задачи"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Описание -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                <textarea name="description" id="description" rows="4" placeholder="Описание задачи (необязательно)"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?= e($fieldDescription) ?></textarea>
            </div>

            <!-- Приоритет + Статус (в строку) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <!-- Приоритет -->
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                    <select name="priority" id="priority"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="low" <?= $fieldPriority === 'low' ? 'selected' : '' ?>>Низкий</option>
                        <option value="medium" <?= $fieldPriority === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="high" <?= $fieldPriority === 'high' ? 'selected' : '' ?>>Высокий</option>
                        <option value="urgent" <?= $fieldPriority === 'urgent' ? 'selected' : '' ?>>Срочный</option>
                    </select>
                </div>

                <!-- Статус -->
                <div>
                    <label for="status_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Статус <span class="text-red-500">*</span>
                    </label>
                    <select name="status_id" id="status_id" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Выберите статус</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $fieldStatusId === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Срок + Исполнитель (в строку) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <!-- Срок -->
                <div>
                    <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1">Срок выполнения</label>
                    <input type="date" name="deadline" id="deadline" value="<?= e($fieldDeadline) ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Исполнитель -->
                <div>
                    <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Исполнитель</label>
                    <select name="assigned_to" id="assigned_to"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Не назначен</option>
                        <?php foreach ($projectUsers as $pu): ?>
                            <option value="<?= (int) $pu['id'] ?>" <?= (int) $fieldAssignedTo === (int) $pu['id'] ? 'selected' : '' ?>>
                                <?= e($pu['name']) ?> (<?= $pu['project_role'] === 'manager' ? 'руководитель' : 'исполнитель' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Кнопки -->
            <div class="flex items-center gap-3 pt-4 border-t">
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <?= $isEdit ? 'Сохранить изменения' : 'Создать задачу' ?>
                </button>
                <a href="<?= $isEdit ? url('/tasks/' . (int) $task['id']) : url('/tasks') ?>"
                   class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>
