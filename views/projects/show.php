<?php
/**
 * Шаблон карточки проекта
 * Десктоп: шапка видна + вкладки
 * Мобильные: кнопка «Проект» → модалка + вкладки
 */
$layout = 'layouts/app';
?>

<div x-data="{ tab: 'info', showProject: false }">

    <!-- ДЕСКТОП: Шапка проекта (lg+) -->
    <div class="hidden lg:block bg-white rounded-lg shadow-sm border p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold text-gray-800"><?= e($project['title']) ?></h1>
            <a href="<?= url('/projects') ?>" class="text-xs text-blue-600 hover:text-blue-800">Все проекты</a>
        </div>
        <?php if (can('edit_project', (int) $project['id'])): ?>
            <div class="flex items-center gap-2">
                <a href="<?= url('/projects/' . (int) $project['id'] . '/edit') ?>" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Редактировать</a>
                <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                    <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>" onsubmit="return confirm('Удалить проект «<?= e($project['title']) ?>» и все задачи?')" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Удалить</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- МОБИЛЬНЫЕ: кнопка «Проект» (<lg) -->
    <div class="lg:hidden flex items-center gap-3 mb-4 flex-wrap">
        <button @click="showProject = true" class="px-3 py-1.5 bg-white border rounded-md text-sm font-medium text-gray-800 hover:bg-gray-50 shadow-sm transition">Проект</button>
    </div>

    <!-- Навигация вкладок (общая) -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-4 -mb-px overflow-x-auto">
            <button @click="tab = 'info'"
                    :class="tab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Информация</button>
            <button @click="tab = 'users'"
                    :class="tab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Участники <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= count($users) ?></span></button>
            <button @click="tab = 'documents'"
                    :class="tab === 'documents' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Документы <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= count($documents) ?></span></button>
            <button @click="tab = 'tasks'"
                    :class="tab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Задачи <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= $taskStats['total'] ?></span></button>
        </nav>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Информация -->
    <!-- ============================================================ -->
    <div x-show="tab === 'info'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
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
                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/status') ?>" class="flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <select name="status_id" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= e($s['id']) ?>" <?= (int)$project['status_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded transition">Изменить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
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
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Создатель</span>
                        <p class="mt-1 text-sm text-gray-800"><?= e($creator['name'] ?? '—') ?></p>
                    </div>
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
                <div>
                    <span class="text-xs font-medium text-gray-500 uppercase">Статистика задач</span>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Всего:</span>
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
                            <?php $percent = round(($taskStats['closed'] / $taskStats['total']) * 100); ?>
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Прогресс</span><span><?= $percent ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition whitespace-nowrap">Добавить</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php if (empty($users)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Участников нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($users as $u): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                            <div>
                                <span class="text-sm font-medium text-gray-800"><?= e($u['name']) ?></span>
                                <span class="text-xs text-gray-500 ml-2"><?= e($u['email']) ?></span>
                                <?php if ($u['project_role'] === 'manager'): ?>
                                    <span class="ml-2 text-xs font-medium bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Рук.</span>
                                <?php else: ?>
                                    <span class="ml-2 text-xs font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Исп.</span>
                                <?php endif; ?>
                            </div>
                            <?php if (can('edit_project', (int) $project['id']) && (int) $u['id'] !== (int) $project['created_by']): ?>
                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/remove-user') ?>"
                                      onsubmit="return confirm('Удалить участника?')" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs">Удалить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Документы -->
    <!-- ============================================================ -->
    <div x-show="tab === 'documents'" x-transition>
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-4 border-b bg-gray-50 rounded-t-lg">
                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/add-document') ?>" enctype="multipart/form-data" class="space-y-3">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" name="doc_title" placeholder="Название *" required class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                        <select name="document_type" class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="other">Другое</option>
                            <option value="kp">КП</option>
                            <option value="brief">Бриф</option>
                            <option value="tz">ТЗ</option>
                            <option value="contract">Договор</option>
                            <option value="estimate">Смета</option>
                            <option value="presentation">Презентация</option>
                            <option value="figma_link">Figma</option>
                            <option value="cloud_link">Облако</option>
                        </select>
                        <input type="text" name="external_url" placeholder="Ссылка" class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="file" name="document_file" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition w-full sm:w-auto">Загрузить</button>
                    </div>
                </form>
            </div>
            <?php if (empty($documents)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Документов нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($documents as $doc): ?>
                        <div class="p-4 hover:bg-gray-50 flex items-center justify-between gap-2">
                            <div>
                                <span class="text-sm font-medium text-gray-800"><?= e($doc['title']) ?></span>
                                <?php
                                $typeLabels = ['kp'=>'КП','brief'=>'Бриф','tz'=>'ТЗ','contract'=>'Договор','estimate'=>'Смета','presentation'=>'Презентация','figma_link'=>'Figma','cloud_link'=>'Облако','other'=>'Другое'];
                                ?>
                                <span class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded"><?= $typeLabels[$doc['document_type']] ?? $doc['document_type'] ?></span>
                                <div class="text-xs text-gray-500 mt-1"><?= e($doc['uploader_name']) ?> · <?= date('d.m.Y', strtotime($doc['created_at'])) ?></div>
                            </div>
                            <?php if ($doc['external_url']): ?>
                                <a href="<?= e($doc['external_url']) ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-700">Открыть</a>
                            <?php endif; ?>
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
                <div class="p-6 text-center text-gray-500 text-sm">Задач нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($tasks as $task): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <?php
                                    $taskDots = ['in_progress'=>'bg-yellow-400','revision'=>'bg-orange-400','done'=>'bg-green-400','closed'=>'bg-gray-400'];
                                    $dotColor = $taskDots[$task['status_code'] ?? ''] ?? 'bg-gray-400';
                                    ?>
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                    <a href="<?= url('/tasks/' . (int) $task['id']) ?>" class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate"><?= e($task['title']) ?></a>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-xs text-gray-500"><?= e($task['status_name']) ?></span>
                                    <?php if ($task['assigned_name']): ?>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"><?= e($task['assigned_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Модалка: Проект ===== -->
    <div x-show="showProject" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="showProject = false" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800 truncate"><?= e($project['title']) ?></h2>
                <button @click="showProject = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="p-4">
                <div class="flex items-center gap-2 mb-4">
                    <a href="<?= url('/projects') ?>" class="text-xs text-blue-600 hover:text-blue-800">← Все проекты</a>
                </div>
                <?php if (can('edit_project', (int) $project['id'])): ?>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="<?= url('/projects/' . (int) $project['id'] . '/edit') ?>"
                           class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">Редактировать</a>
                        <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                            <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Удалить проект «<?= e($project['title']) ?>» и все его задачи?')" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-700 rounded-md text-sm hover:bg-red-100 transition">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
