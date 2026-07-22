<?php
/**
 * Шаблон карточки проекта
 * Десктоп: шапка видна + вкладки
 * Мобильные: кнопка «Проект» → модалка + вкладки
 */
$layout = 'layouts/app';
$allowedTabs = ['info', 'users', 'documents', 'links', 'tasks'];
$initialTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'info';
?>

<style>
/* Страница проекта: убираем общую прокрутку, фиксированная шапка */
body { height: 100vh; overflow: hidden; }
main { overflow: hidden; padding-bottom: 0 !important; }
.project-page { display: flex; flex-direction: column; height: calc(100vh - 5rem); overflow: hidden; padding-bottom: 0.5rem; }
.project-page .project-header { flex-shrink: 0; }
.project-page .project-content { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
.project-page .project-content > [x-show] { flex: 1; min-height: 0; }
@media (max-width: 767px) {
    .project-page { height: calc(100vh - 3.5rem - 3.5rem - 1rem); }
}
</style>

<div x-data="{ tab: '<?= e($initialTab) ?>', showProject: false, showEditProject: false, showLinkGroupForm: false }"
     @keydown.escape.window="showProject = false; showEditProject = false"
     class="project-page">

  <div class="project-header">
    <!-- ДЕСКТОП: Шапка проекта (lg+) -->
    <div class="mb-4 hidden rounded-lg border bg-white p-4 shadow-sm lg:block">
        <a href="<?= url('/projects') ?>" class="mb-2 block text-xs font-medium text-blue-600 hover:text-blue-800">Все проекты</a>
        <div class="flex items-center justify-between gap-4">
            <h1 class="min-w-0 truncate text-xl font-bold text-gray-800"><?= e($project['title']) ?></h1>
            <?php if (can('edit_project', (int) $project['id'])): ?>
                <div class="flex flex-shrink-0 items-center gap-2">
                    <button type="button" @click="showEditProject = true"
                            class="ui-btn ui-btn-secondary">Редактировать</button>
                    <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                        <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>" data-confirm-delete="<?= e('Удалить проект «' . $project['title'] . '» и все его задачи? Это действие нельзя отменить.') ?>" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="ui-btn ui-btn-secondary">Удалить</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- МОБИЛЬНЫЕ: заголовок проекта + ссылка «Все проекты» (<lg) -->
    <div class="lg:hidden mb-4">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-lg font-bold text-gray-800 truncate"><?= e($project['title']) ?></h1>
            <a href="<?= url('/projects') ?>" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 flex-shrink-0">
                Все проекты
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <!-- Статус + Действия -->
        <div class="flex items-center gap-2">
            <?php
            $statusColors = $statusColors ?? [
                'new' => 'bg-blue-100 text-blue-700',
                'active' => 'bg-green-100 text-green-700',
                'on_hold' => 'bg-yellow-100 text-yellow-700',
                'closed' => 'bg-gray-100 text-gray-600',
            ];
            $mobileStatusClass = $statusColors[$status['code'] ?? ''] ?? 'bg-gray-100 text-gray-600';
            ?>
            <span class="text-xs font-medium px-2.5 py-1 rounded-full <?= $mobileStatusClass ?>"><?= e($status['name'] ?? '') ?></span>
            <div class="flex-1"></div>
            <?php if (can('edit_project', (int) $project['id'])): ?>
            <div class="relative" x-data="{ projActions: false }">
                <button @click="projActions = !projActions" class="ui-btn ui-btn-light">Действия</button>
                <div x-show="projActions" @click.outside="projActions = false" x-cloak x-transition
                     class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border py-1 min-w-[180px] z-50" style="display:none">
                    <button type="button" @click="projActions = false; showEditProject = true"
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Редактировать</button>
                    <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                        <div class="border-t my-1"></div>
                        <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>" data-confirm-delete="<?= e('Удалить проект «' . $project['title'] . '» и все его задачи? Это действие нельзя отменить.') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Удалить</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Навигация вкладок (общая) -->
    <div class="border-b border-gray-200 mb-4">
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
            <button @click="tab = 'links'"
                    :class="tab === 'links' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Ссылки <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= count($linkGroups) ?></span></button>
            <button @click="tab = 'tasks'"
                    :class="tab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">Задачи <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full ml-1"><?= $taskStats['total'] ?></span></button>
        </nav>
    </div>
  </div><!-- /project-header -->

  <div class="project-content">
    <!-- ============================================================ -->
    <!-- Вкладка: Информация -->
    <!-- ============================================================ -->
    <div x-show="tab === 'info'" x-transition class="overflow-y-auto">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Статус</span>
                        <div class="mt-1">
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
                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/status') ?>" class="flex items-center gap-2 mt-2"
                                      x-data="{ open: false, selected: '<?= (int)$project['status_id'] ?>', selectedName: '<?= e($status['name'] ?? '') ?>' }">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status_id" :value="selected">
                                    <div class="relative flex-1">
                                        <button type="button" @click="open = !open"
                                                class="ui-control flex items-center justify-between text-left">
                                            <span x-text="selectedName"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @click.outside="open = false" x-transition x-cloak
                                             class="absolute z-20 mt-1 w-full bg-white border rounded-md shadow-lg py-1" style="display:none">
                                            <?php foreach ($statuses as $s): ?>
                                                <button type="button" @click="selected = '<?= e($s['id']) ?>'; selectedName = '<?= e($s['name']) ?>'; open = false"
                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 transition"
                                                        :class="selected === '<?= e($s['id']) ?>' ? 'text-blue-600 font-medium bg-blue-50' : 'text-gray-700'">
                                                    <?= e($s['name']) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button type="submit" class="ui-btn ui-btn-primary">Изменить</button>
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
    <div x-show="tab === 'users'" x-transition class="flex flex-col min-h-0">
        <div class="bg-white rounded-lg shadow-sm border flex flex-col flex-1 min-h-0">
            <?php if (can('edit_project', (int) $project['id']) && empty($isPrivateExecutorProject)): ?>
                <div class="p-4 border-b bg-gray-50 rounded-t-lg flex-shrink-0">
                    <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/add-user') ?>" class="flex flex-col sm:flex-row gap-3">
                        <?= csrf_field() ?>
                        <select name="user_id" required class="ui-control sm:max-w-xs">
                            <option value="">Выберите пользователя...</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= e($u['id']) ?>"><?= e($u['name']) ?> (<?= e($u['login']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select name="project_role" class="ui-control w-full sm:w-44">
                            <option value="manager">Руководитель</option>
                            <option value="executor" selected>Исполнитель</option>
                        </select>
                        <button type="submit" class="ui-btn ui-btn-primary whitespace-nowrap">Добавить</button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-h-0 overflow-y-auto">
            <?php if (empty($users)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Участников нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($users as $u): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50 gap-3">
                            <div class="flex items-center gap-2 flex-wrap min-w-0">
                                <span class="text-sm font-medium text-gray-800"><?= e($u['name']) ?></span>
                                <span class="text-xs text-gray-500"><?= e($u['email']) ?></span>
                                <?php if ($u['project_role'] === 'manager'): ?>
                                    <span class="text-xs font-medium bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Руководитель</span>
                                <?php else: ?>
                                    <span class="text-xs font-medium bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Исполнитель</span>
                                <?php endif; ?>
                            </div>
                            <?php if (can('edit_project', (int) $project['id']) && empty($isPrivateExecutorProject) && (int) $u['id'] !== (int) $project['created_by']): ?>
                                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/remove-user') ?>"
                                      data-confirm-delete="<?= e('Удалить участника «' . $u['name'] . '» из проекта?') ?>" class="inline flex-shrink-0">
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
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Документы -->
    <!-- ============================================================ -->
    <div x-show="tab === 'documents'" x-transition class="flex flex-col min-h-0">
        <div class="bg-white rounded-lg shadow-sm border flex flex-col flex-1 min-h-0">
            <div class="p-4 border-b bg-gray-50 rounded-t-lg flex-shrink-0">
                <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/add-document') ?>" enctype="multipart/form-data" class="space-y-3">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" name="doc_title" placeholder="Название *" required class="ui-control">
                        <select name="document_type" class="ui-control">
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
                        <input type="text" name="external_url" placeholder="Ссылка" class="ui-control">
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="file" name="document_file" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">Загрузить</button>
                    </div>
                </form>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto">
            <?php if (empty($documents)): ?>
                <div class="p-6 text-center text-gray-500 text-sm">Документов нет</div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($documents as $doc): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-sm font-bold text-gray-800"><?= e($doc['title']) ?></span>
                                <span class="text-xs text-gray-400"><?= e($doc['uploader_name']) ?> · <?= date('d.m.Y', strtotime($doc['created_at'])) ?></span>
                                <?php if ($doc['file_path']):
                                    $docExt = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                                ?>
                                    <?php if ($docExt === 'docx'): ?>
                                        <button type="button"
                                                class="js-docx-preview text-xs text-blue-600 hover:text-blue-700 hover:underline"
                                                data-url="<?= url('/projects/documents/' . (int) $doc['id'] . '/view') ?>"
                                                data-title="<?= e($doc['title']) ?>">Просмотр ↗</button>
                                    <?php else: ?>
                                        <a href="<?= url('/projects/documents/' . (int) $doc['id'] . '/view') ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">Просмотр ↗</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($doc['external_url']): ?>
                                    <a href="<?= e($doc['external_url']) ?>" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">Ссылка ↗</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Ссылки -->
    <!-- ============================================================ -->
    <div x-show="tab === 'links'" x-transition class="flex min-h-0 flex-col">
        <div class="flex min-h-0 flex-1 flex-col rounded-lg border bg-white">
            <?php if (can('edit_project', (int) $project['id'])): ?>
                <div class="flex flex-shrink-0 items-center justify-between gap-3 border-b bg-gray-50 p-4 rounded-t-lg">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Ссылки и доступы</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Объединяйте ссылки, логины и пароли в группы.</p>
                    </div>
                    <button type="button" @click="showLinkGroupForm = !showLinkGroupForm" class="ui-btn ui-btn-primary whitespace-nowrap">Создать группу</button>
                </div>
                <form x-show="showLinkGroupForm" x-cloak method="POST" enctype="multipart/form-data"
                      action="<?= url('/projects/' . (int) $project['id'] . '/link-groups') ?>"
                      class="grid flex-shrink-0 gap-3 border-b p-4 sm:grid-cols-[minmax(180px,1fr)_minmax(220px,1.5fr)_minmax(180px,auto)_auto] sm:items-end">
                    <?= csrf_field() ?>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Название группы</label>
                        <input type="text" name="group_title" required maxlength="255" class="ui-control" placeholder="Например, Сайт">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Подпись</label>
                        <input type="text" name="group_caption" maxlength="500" class="ui-control" placeholder="Краткое пояснение">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Иконка</label>
                        <input type="file" name="group_icon" accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-xs file:text-gray-700 hover:file:bg-gray-200">
                    </div>
                    <button type="submit" class="ui-btn ui-btn-primary justify-center">Создать</button>
                </form>
            <?php endif; ?>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <?php if (empty($linkGroups)): ?>
                    <div class="py-10 text-center text-sm text-gray-500">Групп пока нет</div>
                <?php else: ?>
                    <div class="grid gap-4 xl:grid-cols-2">
                        <?php foreach ($linkGroups as $linkGroup): ?>
                            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <div class="flex items-center gap-3 border-b bg-gray-50 p-4">
                                    <?php if (!empty($linkGroup['icon_path'])): ?>
                                        <img src="<?= url('/project-link-groups/' . (int) $linkGroup['id'] . '/icon') ?>"
                                             alt="" class="h-10 w-10 flex-shrink-0 rounded-md border border-gray-200 object-cover">
                                    <?php else: ?>
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500" aria-hidden="true">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 13a5 5 0 007.07.07l2-2a5 5 0 00-7.07-7.07l-1.15 1.15m3.15 5.85a5 5 0 01-7.07-.07l-2 2A5 5 0 0012 20l1.15-1.15"/></svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-semibold text-gray-800"><?= e($linkGroup['title']) ?></h3>
                                        <?php if (!empty($linkGroup['caption'])): ?>
                                            <p class="mt-0.5 line-clamp-2 text-xs text-gray-500"><?= e($linkGroup['caption']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    <?php if (empty($linkGroup['items'])): ?>
                                        <p class="px-4 py-5 text-center text-xs text-gray-400">Записей нет</p>
                                    <?php else: ?>
                                        <?php foreach ($linkGroup['items'] as $linkItem): ?>
                                            <?php
                                            $itemType = $linkItem['item_type'];
                                            $typeLabel = ['link' => 'Ссылка', 'login' => 'Логин', 'password' => 'Пароль'][$itemType] ?? '';
                                            ?>
                                            <div class="flex items-center gap-3 px-4 py-3">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="truncate text-xs font-medium text-gray-700"><?= e($linkItem['label']) ?></span>
                                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500"><?= e($typeLabel) ?></span>
                                                    </div>
                                                    <?php if ($itemType === 'password'): ?>
                                                        <p class="mt-1 truncate text-sm tracking-widest text-gray-600" aria-label="Пароль скрыт">••••••••••••</p>
                                                    <?php elseif ($itemType === 'link'): ?>
                                                        <a href="<?= e($linkItem['value_text']) ?>" target="_blank" rel="noopener noreferrer"
                                                           class="mt-1 block truncate text-sm text-blue-600 hover:underline"><?= e($linkItem['value_text']) ?></a>
                                                    <?php else: ?>
                                                        <p class="mt-1 truncate text-sm text-gray-700"><?= e($linkItem['value_text']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button"
                                                        class="js-copy-project-link flex h-8 w-8 flex-shrink-0 items-center justify-center text-gray-500 transition hover:text-black"
                                                        data-value-url="<?= url('/project-link-items/' . (int) $linkItem['id'] . '/value') ?>"
                                                        title="Копировать" aria-label="Копировать <?= e(mb_strtolower($typeLabel)) ?>">
                                                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 8V6a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2h-2m-8-8H4a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-2m-8-8h6a2 2 0 012 2v6"/></svg>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (can('edit_project', (int) $project['id'])): ?>
                                    <form method="POST" action="<?= url('/project-link-groups/' . (int) $linkGroup['id'] . '/items') ?>"
                                          class="grid gap-2 border-t bg-gray-50 p-3 sm:grid-cols-[110px_minmax(120px,0.8fr)_minmax(160px,1.2fr)_auto] sm:items-center"
                                          x-data="{ itemType: 'link' }">
                                        <?= csrf_field() ?>
                                        <select name="item_type" x-model="itemType" class="ui-control text-sm">
                                            <option value="link">Ссылка</option>
                                            <option value="login">Логин</option>
                                            <option value="password">Пароль</option>
                                        </select>
                                        <input type="text" name="item_label" required maxlength="255" class="ui-control text-sm" placeholder="Название">
                                        <input :type="itemType === 'password' ? 'password' : 'text'" name="item_value" required maxlength="2048"
                                               class="ui-control text-sm" :placeholder="itemType === 'link' ? 'https://...' : 'Значение'" autocomplete="off">
                                        <button type="submit" class="ui-btn ui-btn-primary justify-center">Добавить</button>
                                    </form>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Вкладка: Задачи -->
    <!-- ============================================================ -->
    <div x-show="tab === 'tasks'" x-transition x-data="projectTasks(<?= (int) $project['id'] ?>)" class="flex flex-col min-h-0">
        <div class="bg-white rounded-lg shadow-sm border flex flex-col flex-1 min-h-0">
            <!-- Форма быстрого создания задачи (фиксированная) -->
            <?php if (can('create_task', (int) $project['id'])): ?>
            <div class="p-4 border-b bg-gray-50 rounded-t-lg flex-shrink-0">
                <form @submit.prevent="addTask()" class="flex flex-col sm:flex-row gap-2">
                    <input type="text" x-model="newTaskTitle" placeholder="Название задачи..." required
                           class="ui-control flex-1 min-w-0" @keydown.enter="addTask()">
                    <select x-model="newTaskAssigned" class="ui-control w-full sm:w-44">
                        <option value="">Не назначен</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?php
                                // Автовыбор единственного исполнителя
                                $projectExecutors = array_filter($users, fn($pu) => $pu['project_role'] === 'executor');
                                if (count($projectExecutors) === 1 && $u['project_role'] === 'executor') echo 'selected';
                            ?>><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="ui-btn ui-btn-primary whitespace-nowrap" :disabled="adding">
                        <span x-show="!adding">Добавить</span>
                        <span x-show="adding" x-cloak>...</span>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Список задач с drag-and-drop -->
            <?php if (empty($tasks)): ?>
                <div x-show="taskList.length === 0" class="p-6 text-center text-gray-500 text-sm">Задач нет</div>
            <?php endif; ?>
            <div class="divide-y overflow-y-auto flex-1 min-h-0" id="taskSortable">
                <?php
                $taskDots = ['in_progress'=>'bg-yellow-400','revision'=>'bg-orange-400','done'=>'bg-green-400','closed'=>'bg-indigo-400'];
                ?>
                <template x-for="task in taskList" :key="task.id">
                    <div class="p-3 hover:bg-gray-50 task-sortable-item" :data-id="task.id">
                        <!-- Режим просмотра -->
                        <div x-show="editingId !== task.id" class="flex items-center gap-3">
                            <span class="drag-handle flex-shrink-0 text-gray-300 hover:text-gray-500 cursor-grab">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/></svg>
                            </span>
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  :class="{
                                      'bg-yellow-400': task.status_code === 'in_progress',
                                      'bg-orange-400': task.status_code === 'revision',
                                      'bg-green-400': task.status_code === 'done',
                                      'bg-indigo-400': task.status_code === 'closed',
                                      'bg-gray-400': !task.status_code
                                  }"></span>
                            <a :href="BASE_URL + '/tasks/' + task.id" class="text-sm font-medium text-gray-800 hover:text-blue-600 flex-1 min-w-0" x-text="task.title"></a>
                            <span class="text-xs text-gray-500 flex-shrink-0" x-text="task.status_name"></span>
                            <span x-show="task.assigned_name" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded flex-shrink-0" x-text="task.assigned_name"></span>
                            <?php if (can('create_task', (int) $project['id'])): ?>
                            <button @click.prevent="startEdit(task)" class="flex-shrink-0 p-1 text-gray-300 hover:text-blue-600 transition" title="Редактировать">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>
                        <!-- Режим редактирования -->
                        <div x-show="editingId === task.id" x-cloak class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="text" x-model="editTitle" @keydown.enter="saveEdit(task)" @keydown.escape="cancelEdit()"
                                   x-ref="editInput"
                                   class="ui-control flex-1 min-w-0 text-sm py-1.5">
                            <select x-model="editAssigned" class="ui-control w-full sm:w-40 text-sm py-1.5">
                                <option value="">Не назначен</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="flex gap-1">
                                <button @click="saveEdit(task)" class="p-1.5 text-green-600 hover:text-green-700" title="Сохранить">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button @click="cancelEdit()" class="p-1.5 text-gray-400 hover:text-gray-600" title="Отмена">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <button @click="deleteTask(task)" class="p-1.5 text-red-400 hover:text-red-600" title="Удалить">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

  </div><!-- /project-content -->

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
                        <button type="button" @click="showProject = false; showEditProject = true"
                                class="ui-btn ui-btn-secondary">Редактировать</button>
                        <?php if (\Helpers\Auth::isAdmin() || (int) $project['created_by'] === \Helpers\Auth::id()): ?>
                            <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/delete') ?>"
                                  data-confirm-delete="<?= e('Удалить проект «' . $project['title'] . '» и все его задачи? Это действие нельзя отменить.') ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="ui-btn bg-red-50 text-red-700 border-red-100 hover:bg-red-100">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== Модалка: Редактирование проекта ===== -->
    <div x-show="showEditProject" x-cloak x-transition.opacity
         role="dialog" aria-modal="true" aria-labelledby="edit-project-title"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
         @click.self="showEditProject = false" style="display: none;">
        <div class="max-h-[86vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl" @click.stop>
            <div class="flex items-center justify-between border-b p-4">
                <h2 id="edit-project-title" class="text-lg font-bold text-gray-800">Редактирование проекта</h2>
                <button type="button" @click="showEditProject = false"
                        class="a11y-icon-button text-xl text-gray-400 hover:text-black"
                        aria-label="Закрыть окно редактирования проекта">&times;</button>
            </div>
            <form method="POST" action="<?= url('/projects/' . (int) $project['id'] . '/edit') ?>" class="space-y-4 p-4">
                <?= csrf_field() ?>

                <div>
                    <label for="edit_project_title_input" class="mb-1 block text-xs font-medium text-gray-500">
                        Название проекта <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_project_title_input" name="title" value="<?= e($project['title']) ?>"
                           required maxlength="255" class="ui-control">
                </div>

                <div>
                    <label for="edit_project_description" class="mb-1 block text-xs font-medium text-gray-500">Описание</label>
                    <textarea id="edit_project_description" name="description" rows="3"
                              placeholder="Описание проекта" class="ui-control"><?= e($project['description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="edit_project_deadline" class="mb-1 block text-xs font-medium text-gray-500">Срок сдачи</label>
                        <input type="date" id="edit_project_deadline" name="deadline"
                               value="<?= e($project['deadline'] ?? '') ?>" class="ui-control">
                    </div>
                    <div>
                        <label for="edit_project_estimated_hours" class="mb-1 block text-xs font-medium text-gray-500">Расчётное время</label>
                        <input type="number" id="edit_project_estimated_hours" name="estimated_hours"
                               value="<?= e($project['estimated_hours'] ?? '') ?>" step="0.5" min="0.5" placeholder="Часы" class="ui-control">
                    </div>
                </div>

                <div>
                    <label for="edit_project_status" class="mb-1 block text-xs font-medium text-gray-500">
                        Статус <span class="text-red-500">*</span>
                    </label>
                    <select id="edit_project_status" name="status_id" required class="ui-control">
                        <?php foreach ($statuses as $projectStatus): ?>
                            <option value="<?= (int) $projectStatus['id'] ?>"
                                <?= (int) $projectStatus['id'] === (int) $project['status_id'] ? 'selected' : '' ?>>
                                <?= e($projectStatus['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-2 border-t pt-4">
                    <button type="submit" class="ui-btn ui-btn-primary">Сохранить</button>
                    <button type="button" @click="showEditProject = false" class="ui-btn ui-btn-secondary">Отмена</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- SortableJS для drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('projectTasks', (projectId) => ({
        taskList: <?= json_encode($tasks ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        newTaskTitle: '',
        newTaskAssigned: '<?php
            $projectExecutors = array_filter($users, fn($pu) => $pu['project_role'] === 'executor');
            echo count($projectExecutors) === 1 ? (int) reset($projectExecutors)['id'] : '';
        ?>',
        adding: false,
        sortable: null,
        editingId: null,
        editTitle: '',
        editAssigned: '',

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('taskSortable');
                if (el) {
                    this.sortable = Sortable.create(el, {
                        handle: '.drag-handle',
                        animation: 150,
                        ghostClass: 'bg-blue-50',
                        onEnd: () => this.saveOrder()
                    });
                }
            });
        },

        startEdit(task) {
            this.editingId = task.id;
            this.editTitle = task.title;
            this.editAssigned = task.assigned_to ? String(task.assigned_to) : '';
            this.$nextTick(() => {
                const input = this.$el.querySelector('input[x-model="editTitle"]');
                if (input) input.focus();
            });
        },

        cancelEdit() {
            this.editingId = null;
            this.editTitle = '';
            this.editAssigned = '';
        },

        async saveEdit(task) {
            if (!this.editTitle.trim()) return;

            const form = new FormData();
            form.append('title', this.editTitle.trim());
            form.append('assigned_to', this.editAssigned);

            try {
                const res = await fetch(BASE_URL + '/ajax/projects/' + projectId + '/edit-task/' + task.id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: form
                });
                const data = await res.json();
                if (data.success && data.task) {
                    const idx = this.taskList.findIndex(t => t.id === task.id);
                    if (idx !== -1) this.taskList[idx] = data.task;
                }
            } catch (e) {}
            this.editingId = null;
        },

        async deleteTask(task) {
            if (!(await window.confirmDeletion('Удалить задачу «' + task.title + '»? Это действие нельзя отменить.'))) return;

            try {
                const res = await fetch(BASE_URL + '/ajax/projects/' + projectId + '/delete-task/' + task.id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.taskList = this.taskList.filter(t => t.id !== task.id);
                }
            } catch (e) {}
            this.editingId = null;
        },

        async addTask() {
            if (!this.newTaskTitle.trim() || this.adding) return;
            this.adding = true;

            try {
                const form = new FormData();
                form.append('title', this.newTaskTitle.trim());
                form.append('assigned_to', this.newTaskAssigned);

                const res = await fetch(BASE_URL + '/ajax/projects/' + projectId + '/quick-task', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
                    body: form
                });
                const data = await res.json();

                if (data.success && data.task) {
                    this.taskList.push(data.task);
                    this.newTaskTitle = '';
                }
            } catch (e) {}
            this.adding = false;
        },

        async saveOrder() {
            const items = document.querySelectorAll('.task-sortable-item');
            const order = Array.from(items).map(el => el.dataset.id);

            // Обновляем локальный массив
            const map = {};
            this.taskList.forEach(t => map[t.id] = t);
            this.taskList = order.map(id => map[id]).filter(Boolean);

            try {
                await fetch(BASE_URL + '/ajax/projects/' + projectId + '/reorder-tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ order: order })
                });
            } catch (e) {}
        }
    }));
});
</script>

<script>
document.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-copy-project-link');
    if (!button || button.disabled) return;

    button.disabled = true;
    button.classList.add('opacity-50');
    try {
        const response = await fetch(button.dataset.valueUrl, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Не удалось получить значение');
        }

        let copied = false;
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(data.value);
                copied = true;
            } catch (clipboardError) {}
        }
        if (!copied) {
            const temporary = document.createElement('textarea');
            temporary.value = data.value;
            temporary.style.position = 'fixed';
            temporary.style.opacity = '0';
            document.body.appendChild(temporary);
            temporary.select();
            copied = document.execCommand('copy');
            temporary.remove();
        }
        if (!copied) throw new Error('Браузер не разрешил копирование');
        if (typeof showToast === 'function') showToast('Скопировано', 'success');
    } catch (error) {
        if (typeof showToast === 'function') showToast(error.message || 'Не удалось скопировать', 'error');
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-50');
    }
});
</script>

<!-- Модалка просмотра DOCX -->
<style>
#docxContainer { overflow: auto; -webkit-overflow-scrolling: touch; }
#docxContainer .docx-wrapper { padding: 4px !important; overflow: hidden !important; }
#docxContainer .docx-wrapper > section {
    box-shadow: none !important;
    max-width: 100% !important;
    width: 100% !important;
    min-width: 0 !important;
    padding: 10px !important;
    overflow-wrap: break-word;
    word-wrap: break-word;
}
#docxContainer .docx-wrapper > section img {
    max-width: 100% !important;
    height: auto !important;
}
#docxContainer .docx-wrapper > section table {
    max-width: 100% !important;
    width: 100% !important;
}
</style>
<div id="docxModal" class="fixed inset-0 z-[300] bg-black/60 hidden flex items-center justify-center p-2 sm:p-4">
    <div class="bg-white w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] rounded-xl shadow-2xl flex flex-col overflow-hidden">
        <div class="flex items-center justify-between p-3 sm:p-4 border-b flex-shrink-0">
            <h3 id="docxModalTitle" class="text-sm font-medium text-gray-700 truncate"></h3>
            <div class="flex items-center gap-2">
                <a id="docxModalDownload" href="#" class="text-xs text-blue-600 hover:text-blue-700">Скачать</a>
                <button id="docxModalClose" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div id="docxContainer" class="flex-1 overflow-auto p-2 sm:p-4"></div>
        <div id="docxLoading" class="hidden flex-1 flex items-center justify-center p-8">
            <span class="text-gray-400 text-sm">Загрузка документа...</span>
        </div>
    </div>
</div>

<!-- docx-preview: рендеринг .docx в браузере без сервера -->
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.3/dist/docx-preview.min.js"></script>
<script>
(function() {
    const modal = document.getElementById('docxModal');
    const container = document.getElementById('docxContainer');
    const loading = document.getElementById('docxLoading');
    const titleEl = document.getElementById('docxModalTitle');
    const downloadLink = document.getElementById('docxModalDownload');
    const closeBtn = document.getElementById('docxModalClose');

    if (!modal) return;

    // Открытие модалки по клику на кнопку «Просмотр»
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-docx-preview');
        if (!btn) return;

        const url = btn.dataset.url;
        const title = btn.dataset.title || 'Документ';

        titleEl.textContent = title;
        downloadLink.href = url;
        container.innerHTML = '';
        container.classList.add('hidden');
        loading.classList.remove('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('Ошибка загрузки');
            const blob = await response.blob();

            loading.classList.add('hidden');
            container.classList.remove('hidden');

            await docx.renderAsync(blob, container, null, {
                className: 'docx-preview-content',
                inWrapper: true,
                ignoreWidth: true,
                ignoreHeight: true,
                ignoreFonts: false,
                breakPages: false,
                useBase64URL: true,
            });
        } catch (err) {
            loading.classList.add('hidden');
            container.classList.remove('hidden');
            container.innerHTML = '<p class="text-center text-red-500 text-sm py-8">Не удалось загрузить документ</p>';
        }
    });

    // Закрытие
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

    function closeModal() {
        modal.classList.add('hidden');
        container.innerHTML = '';
        document.body.style.overflow = '';
    }
})();
</script>
