<?php
/**
 * Шаблон формы создания/редактирования проекта
 * Используется для обоих действий: create и edit
 */
$layout = 'layouts/app';
$isEdit = $project !== null;
$action = $isEdit ? '/projects/' . e($project['id']) . '/edit' : '/projects/create';
?>

<!-- Заголовок -->
<div class="mb-6">
    <a href="<?= $isEdit ? '/projects/' . e($project['id']) : '/projects' ?>"
       class="text-sm text-blue-600 hover:text-blue-700 mb-2 inline-block">&larr; Назад</a>
    <h1 class="text-2xl font-bold text-gray-800">
        <?= $isEdit ? 'Редактирование проекта' : 'Создание проекта' ?>
    </h1>
</div>

<!-- Форма -->
<div class="bg-white rounded-lg shadow-sm border p-6 max-w-2xl">
    <form method="POST" action="<?= $action ?>">
        <?= csrf_field() ?>

        <!-- Название -->
        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Название проекта <span class="text-red-500">*</span>
            </label>
            <input type="text" id="title" name="title"
                   value="<?= e($old['title'] ?? $project['title'] ?? '') ?>"
                   required
                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['title']) ? 'border-red-500' : '' ?>"
                   placeholder="Введите название проекта">
            <?php if (!empty($errors['title'])): ?>
                <?php foreach ($errors['title'] as $error): ?>
                    <p class="text-red-600 text-xs mt-1"><?= e($error) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Описание -->
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                Описание
            </label>
            <textarea id="description" name="description" rows="4"
                      class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Описание проекта (необязательно)"><?= e($old['description'] ?? $project['description'] ?? '') ?></textarea>
        </div>

        <!-- Срок сдачи -->
        <div class="mb-4">
            <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1">
                Срок сдачи
            </label>
            <input type="date" id="deadline" name="deadline"
                   value="<?= e($old['deadline'] ?? $project['deadline'] ?? '') ?>"
                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['deadline']) ? 'border-red-500' : '' ?>">
            <?php if (!empty($errors['deadline'])): ?>
                <?php foreach ($errors['deadline'] as $error): ?>
                    <p class="text-red-600 text-xs mt-1"><?= e($error) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Статус -->
        <div class="mb-6">
            <label for="status_id" class="block text-sm font-medium text-gray-700 mb-1">
                Статус <span class="text-red-500">*</span>
            </label>
            <select id="status_id" name="status_id" required
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 <?= !empty($errors['status_id']) ? 'border-red-500' : '' ?>">
                <option value="">Выберите статус...</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s['id']) ?>"
                        <?= (string)($old['status_id'] ?? $project['status_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['status_id'])): ?>
                <?php foreach ($errors['status_id'] as $error): ?>
                    <p class="text-red-600 text-xs mt-1"><?= e($error) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Кнопки -->
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md text-sm font-medium transition">
                <?= $isEdit ? 'Сохранить изменения' : 'Создать проект' ?>
            </button>
            <a href="<?= $isEdit ? '/projects/' . e($project['id']) : '/projects' ?>"
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2.5 rounded-md text-sm font-medium transition">
                Отмена
            </a>
        </div>
    </form>
</div>
