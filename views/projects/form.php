<?php
/**
 * Форма создания/редактирования проекта
 * При редактировании: заполненные поля видны, незаполненные — под «Дополнительно»
 */
$layout = 'layouts/app';
$isEdit = $project !== null;
$action = $isEdit ? url('/projects/' . (int) $project['id'] . '/edit') : url('/projects/create');

$fieldTitle = $old['title'] ?? ($project['title'] ?? '');
$fieldDescription = $old['description'] ?? ($project['description'] ?? '');
$fieldDeadline = $old['deadline'] ?? ($project['deadline'] ?? '');
$fieldStatusId = $old['status_id'] ?? ($project['status_id'] ?? '');
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-gray-800 mb-6">
        <?= $isEdit ? 'Редактирование проекта' : 'Создание проекта' ?>
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

    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="<?= $action ?>" x-data="{ showExtra: false }">
            <?= csrf_field() ?>

            <!-- === Основные поля (всегда видны) === -->

            <!-- Название (всегда) -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Название проекта <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="<?= e($fieldTitle) ?>" required
                       placeholder="Введите название проекта"
                       class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Описание (показываем если заполнено) -->
            <?php if (!empty($fieldDescription)): ?>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"><?= e($fieldDescription) ?></textarea>
            </div>
            <?php endif; ?>

            <!-- Срок (показываем если установлен) -->
            <?php if (!empty($fieldDeadline)): ?>
            <div class="mb-4">
                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1">Срок сдачи</label>
                <input type="date" id="deadline" name="deadline" value="<?= e($fieldDeadline) ?>"
                       class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <?php endif; ?>

            <!-- Статус (показываем если выбран) -->
            <?php if (!empty($fieldStatusId)): ?>
            <div class="mb-4">
                <label for="status_id" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                <select id="status_id" name="status_id"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s['id']) ?>" <?= (string)$fieldStatusId === (string)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
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

            <!-- === Дополнительные поля === -->
            <div x-show="showExtra" x-transition class="space-y-4 mb-6" style="display: none;">

                <?php if (empty($fieldDescription)): ?>
                <div>
                    <label for="description_extra" class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                    <textarea name="description" id="description_extra" rows="3" placeholder="Описание проекта"
                              class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <?php endif; ?>

                <?php if (empty($fieldDeadline)): ?>
                <div>
                    <label for="deadline_extra" class="block text-sm font-medium text-gray-700 mb-1">Срок сдачи</label>
                    <input type="date" name="deadline" id="deadline_extra"
                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <?php endif; ?>

                <?php if (empty($fieldStatusId)): ?>
                <div>
                    <label for="status_id_extra" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                    <select name="status_id" id="status_id_extra"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Выберите статус...</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s['id']) ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- Кнопки -->
            <div class="flex items-center gap-3 pt-4 border-t">
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <?= $isEdit ? 'Сохранить изменения' : 'Создать проект' ?>
                </button>
                <a href="<?= $isEdit ? url('/projects/' . (int) $project['id']) : url('/projects') ?>"
                   class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>
