<?php
/**
 * Шаблон карточки проекта
 * Вкладки: Информация, Участники, Документы, Задачи
 * Используется Alpine.js для переключения вкладок
 */
$layout = 'layouts/app';
?>

<!-- Шапка проекта -->
<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <!-- Название + «Все проекты» -->
    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold text-gray-800"><?= e($project['title']) ?></h1>
        <a href="<?= url('/projects') ?>" class="text-xs text-blue-600 hover:text-blue-800">Все проекты</a>
    </div>

    <!-- Кнопки действий -->
    <?php if (can('edit_project', (int) $project['id'])): ?>
        <div class="flex items-center gap-2">
            <a href="<?= url('/projects/' . (int) $project['id'] . '/edit') ?>"
               class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                Редактировать
            </a>
            <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>"
                      onsubmit="return confirm('Удалить проект «<?= e($project['title']) ?>» и все его задачи? Это действие нельзя отменить!')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                        Удалить
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Вкладки -->
<div x-data="{ tab: 'info' }">
    <!-- Навигация вкладок -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-4 -mb-px overflow-x-auto">
            <button @click="tab = 'info'"
                    :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                Информация
            </button>
            <button @click="tab = 'users'"
                    :class="tab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                Участники <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= count($users) ?></span>
            </button>
            <button @click="tab = 'documents'"
                    :class="tab === 'documents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                Документы <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= count($documents) ?></span>
            </button>
            <button @click="tab = 'tasks'"
                    :class="tab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                Задачи <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= $taskStats['total'] ?></span>
            </button>
        </nav>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Информация -->
    <!-- ============================================================ -->
    <div x-show="tab === 'info'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Левая колонка -->
                <div class="space-y-4">
                    <!-- Статус -->
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Статус</span>
                        <div class="mt-1 flex items-center gap-3">
                            <?php
                            $statusColors = [
                                'new' => 'bg-blue-100 text-blue-700',
                                'active' => 'bg-green-100 text-green-700',
                                'on_hold' => 'bg-yellow-100 text-yellow-700',
                                'closed' => 'bg-gray-100 text-gray-600',
                            ];
                            $colorClass = $statusColors[$status['code'] ?? ''] ?? 'bg-gray-100 text-gray-600';
                            ?>
                            <span class="text-sm font-medium px-3 py-1 rounded-full <?= $colorClass ?>">
                                <?= e($status['name'] ?? 'Не указан') ?>
                            </span>

                            <?php if (can('edit_project', (int) $project['id'])): ?>
                                <!-- Форма смены статуса -->
                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/status') ?>" class="flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <select name="status_id" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= e($s['id']) ?>" <?= (int)$project['status_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                                                <?= e($s['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded transition">
                                        Изменить
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Срок -->
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Срок сдачи</span>
                        <p class="mt-1 text-sm text-gray-800">
                            <?php if ($project['deadline']): ?>
                                <?= date('d.m.Y', strtotime($project['deadline'])) ?>
                                <?php if ($project['deadline'] < date('Y-m-d') && ($status['code'] ?? '') !== 'closed'): ?>
                                    <span class="text-red-600 font-medium ml-2">просрочен</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">Не указан</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Создатель -->
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Создатель</span>
                        <p class="mt-1 text-sm text-gray-800"><?= e($creator['name'] ?? '—') ?></p>
                    </div>

                    <!-- Даты -->
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Создан</span>
                        <p class="mt-1 text-sm text-gray-800"><?= date('d.m.Y H:i', strtotime($project['created_at'])) ?></p>
                    </div>

                    <?php if ($project['closed_at']): ?>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase">Закрыт</span>
                            <p class="mt-1 text-sm text-gray-800"><?= date('d.m.Y H:i', strtotime($project['closed_at'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Правая колонка — статистика задач -->
                <div>
                    <span class="text-xs font-medium text-gray-500 uppercase">Статистика задач</span>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Всего задач:</span>
                            <span class="font-semibold"><?= $taskStats['total'] ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Открытых:</span>
                            <span class="font-semibold text-orange-600"><?= $taskStats['open'] ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Закрытых:</span>
                            <span class="font-semibold text-green-600"><?= $taskStats['closed'] ?></span>
                        </div>

                        <?php if ($taskStats['total'] > 0): ?>
                            <!-- Прогресс-бар -->
                            <div class="mt-3">
                                <?php $percent = $taskStats['total'] > 0 ? round(($taskStats['closed'] / $taskStats['total']) * 100) : 0; ?>
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Прогресс</span>
                                    <span><?= $percent ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Описание -->
            <?php if ($project['description']): ?>
                <div class="mt-6 pt-6 border-t">
                    <span class="text-xs font-medium text-gray-500 uppercase">Описание</span>
                    <div class="mt-2 text-sm text-gray-700 whitespace-pre-wrap"><?= e($project['description']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Участники -->
    <!-- ============================================================ -->
    <div x-show="tab === 'users'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border">
            <!-- Форма добавления участника -->
            <?php if (can('edit_project', (int) $project['id'])): ?>
                <div class="p-4 border-b bg-gray-50 rounded-t-lg">
                    <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/add-user') ?>" class="flex flex-col sm:flex-row gap-3">
                        <?= csrf_field() ?>
                        <select name="user_id" required class="flex-1 border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Выберите пользователя...</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= e($u['id']) ?>"><?= e($u['name']) ?> (<?= e($u['login']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select name="project_role" class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="manager">Руководитель</option>
                            <option value="executor" selected>Исполнитель</option>
                        </select>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">
                            Добавить
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Таблица участников -->
            <?php if (empty($users)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Участников пока нет</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Имя</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Роль в проекте</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Добавлен</th>
                                <?php if (can('edit_project', (int) $project['id'])): ?>
                                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase">Действия</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800"><?= e($u['name']) ?></td>
                                    <td class="px-4 py-3 text-gray-600"><?= e($u['email']) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($u['project_role'] === 'manager'): ?>
                                            <span class="text-xs font-medium bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Руководитель</span>
                                        <?php else: ?>
                                            <span class="text-xs font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Исполнитель</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500"><?= date('d.m.Y', strtotime($u['joined_at'])) ?></td>
                                    <?php if (can('edit_project', (int) $project['id'])): ?>
                                        <td class="px-4 py-3 text-right">
                                            <?php if ((int) $u['id'] !== (int) $project['created_by']): ?>
                                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/remove-user') ?>" class="inline"
                                                      onsubmit="return confirm('Удалить участника из проекта?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium">
                                                        Удалить
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400">Создатель</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Документы -->
    <!-- ============================================================ -->
    <div x-show="tab === 'documents'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border">
            <!-- Форма загрузки документа -->
            <div class="p-4 border-b bg-gray-50 rounded-t-lg">
                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/add-document') ?>" enctype="multipart/form-data"
                      class="space-y-3">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <input type="text" name="doc_title" placeholder="Название документа *" required
                               class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                        <select name="document_type" class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="other">Другое</option>
                            <option value="kp">Коммерческое предложение</option>
                            <option value="brief">Бриф</option>
                            <option value="tz">Техническое задание</option>
                            <option value="contract">Договор</option>
                            <option value="estimate">Смета</option>
                            <option value="presentation">Презентация</option>
                            <option value="figma_link">Ссылка Figma</option>
                            <option value="cloud_link">Облачная ссылка</option>
                        </select>
                        <input type="text" name="external_url" placeholder="Внешняя ссылка (необязательно)"
                               class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="file" name="document_file"
                               class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <input type="text" name="doc_comment" placeholder="Комментарий (необязательно)"
                               class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Загрузить документ
                    </button>
                </form>
            </div>

            <!-- Список документов -->
            <?php if (empty($documents)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Документов пока нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($documents as $doc): ?>
                        <div class="p-4 hover:bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <!-- Иконка типа -->
                                    <?php if ($doc['file_path']): ?>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                    <?php endif; ?>
                                    <span class="font-medium text-sm text-gray-800"><?= e($doc['title']) ?></span>
                                    <?php
                                    $typeLabels = [
                                        'kp' => 'КП', 'brief' => 'Бриф', 'tz' => 'ТЗ', 'contract' => 'Договор',
                                        'estimate' => 'Смета', 'presentation' => 'Презентация',
                                        'figma_link' => 'Figma', 'cloud_link' => 'Облако', 'other' => 'Другое',
                                    ];
                                    ?>
                                    <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">
                                        <?= $typeLabels[$doc['document_type']] ?? $doc['document_type'] ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= e($doc['uploader_name']) ?> · <?= date('d.m.Y', strtotime($doc['created_at'])) ?>
                                    <?php if ($doc['comment']): ?>
                                        · <?= e($doc['comment']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($doc['external_url']): ?>
                                    <a href="<?= e($doc['external_url']) ?>" target="_blank" rel="noopener"
                                       class="text-xs text-blue-600 hover:text-blue-700 font-medium">Открыть ссылку</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Задачи -->
    <!-- ============================================================ -->
    <div x-show="tab === 'tasks'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border">
            <?php if (empty($tasks)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Задач пока нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($tasks as $task): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <?php
                                    $taskStatusColors = [
                                        'new' => 'bg-blue-400',
                                        'in_progress' => 'bg-yellow-400',
                                        'review' => 'bg-purple-400',
                                        'done' => 'bg-green-400',
                                        'closed' => 'bg-gray-400',
                                    ];
                                    $dotColor = $taskStatusColors[$task['status_code'] ?? ''] ?? 'bg-gray-400';
                                    ?>
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                    <a href="<?= url('/tasks/' . (int) $task['id']) ?>" class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate">
                                        <?= e($task['title']) ?>
                                    </a>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-xs text-gray-500"><?= e($task['status_name']) ?></span>
                                    <?php if ($task['assigned_name']): ?>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                            <?= e($task['assigned_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($task['deadline']): ?>
                                        <?php $isOverdue = $task['deadline'] < date('Y-m-d') && !in_array($task['status_code'], ['done', 'closed']); ?>
                                        <span class="text-xs <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-500' ?>">
                                            <?= date('d.m', strtotime($task['deadline'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
