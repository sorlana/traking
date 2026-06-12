<?php
/**
 * Карточка задачи — views/tasks/show.php
 *
 * Главный рабочий экран задачи:
 * информация, подзадачи, комментарии, файлы, кнопки действий.
 */
$layout = 'layouts/app';

$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);

// Карта приоритетов
$priorityLabels = [
    'low' => ['label' => 'Низкий', 'class' => 'bg-gray-100 text-gray-700'],
    'medium' => ['label' => 'Средний', 'class' => 'bg-blue-100 text-blue-700'],
    'high' => ['label' => 'Высокий', 'class' => 'bg-orange-100 text-orange-700'],
    'urgent' => ['label' => 'Срочный', 'class' => 'bg-red-100 text-red-700'],
];

// Карта статусов → цвета
$statusColors = [
    'new' => 'bg-blue-100 text-blue-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'review' => 'bg-purple-100 text-purple-800',
    'done' => 'bg-green-100 text-green-800',
    'closed' => 'bg-gray-100 text-gray-800',
    'cancelled' => 'bg-red-100 text-red-800',
];

// Цвета точек статусов для дочерних задач
$statusDots = [
    'new' => 'bg-blue-500',
    'in_progress' => 'bg-yellow-500',
    'review' => 'bg-purple-500',
    'done' => 'bg-green-500',
    'closed' => 'bg-gray-400',
    'cancelled' => 'bg-red-400',
];

$isOverdue = !empty($task['deadline'])
    && strtotime($task['deadline']) < strtotime(date('Y-m-d'))
    && !in_array($task['status_code'] ?? '', ['closed', 'cancelled', 'done']);

$prio = $priorityLabels[$task['priority'] ?? 'medium'] ?? $priorityLabels['medium'];
$statusClass = $statusColors[$task['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-800';

$canEdit = can('create_task', (int) $task['project_id']);
?>

<div class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
        <a href="<?= url('/projects/' . (int) $task['project_id']) ?>" class="hover:text-blue-600">
            <?= e($task['project_title'] ?? 'Проект') ?>
        </a>
        <?php if ($parent ?? null): ?>
            <span>→</span>
            <a href="<?= url('/tasks/' . (int) $parent['id']) ?>" class="hover:text-blue-600">
                <?= e($parent['title']) ?>
            </a>
        <?php endif; ?>
        <span>→</span>
        <span class="text-gray-800 font-medium"><?= e($task['title']) ?></span>
    </nav>

    <!-- Заголовок + действия -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= e($task['title']) ?></h1>
            <?php if ($isOverdue): ?>
                <span class="inline-block mt-1 px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                    ⚠ Просрочена
                </span>
            <?php endif; ?>
        </div>

        <?php if ($canEdit): ?>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('/tasks/' . (int) $task['id'] . '/edit') ?>"
               class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200 transition">
                Редактировать
            </a>
            <a href="<?= url('/tasks/create') ?>?project_id=<?= (int) $task['project_id'] ?>&parent_id=<?= (int) $task['id'] ?>"
               class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200 transition">
                + Подзадача
            </a>
            <?php if ($task['status_code'] !== 'closed' && $task['status_code'] !== 'cancelled'): ?>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/close') ?>"
                      onsubmit="return confirm('Закрыть задачу?')" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-3 py-1.5 bg-green-100 text-green-700 rounded-md text-sm hover:bg-green-200 transition"
                            <?= !$canClose['can'] ? 'disabled title="Есть незавершённые подзадачи"' : '' ?>>
                        Закрыть задачу
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Левая колонка: описание + подзадачи + комментарии -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Описание -->
            <?php if (!empty($task['description'])): ?>
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Описание</h3>
                <div class="text-gray-700 whitespace-pre-wrap"><?= e($task['description']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Подзадачи -->
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Подзадачи (<?= count($children) ?>)</h3>
                    <?php if ($canEdit): ?>
                        <a href="<?= url('/tasks/create') ?>?project_id=<?= (int) $task['project_id'] ?>&parent_id=<?= (int) $task['id'] ?>"
                           class="text-sm text-blue-600 hover:text-blue-800">+ Добавить</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($children)): ?>
                    <p class="text-sm text-gray-400">Подзадач нет</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($children as $child): ?>
                            <?php
                            $childOverdue = !empty($child['deadline'])
                                && strtotime($child['deadline']) < strtotime(date('Y-m-d'))
                                && !in_array($child['status_code'] ?? '', ['closed', 'cancelled', 'done']);
                            $dotColor = $statusDots[$child['status_code'] ?? ''] ?? 'bg-gray-400';
                            ?>
                            <div class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 <?= $childOverdue ? 'bg-red-50' : '' ?>">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 <?= $dotColor ?>"></span>
                                <a href="<?= url('/tasks/' . (int) $child['id']) ?>" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-1">
                                    <?= e($child['title']) ?>
                                </a>
                                <span class="text-xs text-gray-500 hidden sm:inline">
                                    <?= e($child['assigned_name'] ?? '') ?>
                                </span>
                                <?php if (!empty($child['deadline'])): ?>
                                    <span class="text-xs <?= $childOverdue ? 'text-red-600 font-medium' : 'text-gray-400' ?>">
                                        <?= date('d.m', strtotime($child['deadline'])) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs px-1.5 py-0.5 rounded <?= $statusColors[$child['status_code'] ?? ''] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= e($child['status_name'] ?? '') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Комментарии (компонент с Alpine.js) -->
            <?php include BASE_PATH . '/views/components/comments.php'; ?>

            <!-- Файлы -->
            <div class="bg-white rounded-lg shadow-sm border p-5" x-data="filesComponent()">
                <h3 class="text-sm font-medium text-gray-500 mb-4">
                    Файлы (<span x-text="files.length"><?= count($files) ?></span>)
                </h3>

                <!-- Список файлов -->
                <div class="space-y-2" x-show="files.length > 0">
                    <template x-for="file in files" :key="file.id">
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded">
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <a :href="BASE_URL + '/files/' + file.id + '/download'" class="text-sm text-blue-600 hover:text-blue-800 truncate block" x-text="file.file_name"></a>
                                <p class="text-xs text-gray-400">
                                    <span x-text="file.uploader_name"></span> •
                                    <span x-text="formatDate(file.created_at)"></span> •
                                    <span x-text="formatSize(file.file_size)"></span>
                                </p>
                            </div>
                            <button @click="deleteFile(file.id)"
                                    x-show="file.uploaded_by == currentUserId || isAdminOrManager"
                                    class="text-xs text-red-500 hover:text-red-700 flex-shrink-0">
                                Удалить
                            </button>
                        </div>
                    </template>
                </div>

                <p class="text-sm text-gray-400" x-show="files.length === 0">Файлов нет</p>

                <!-- Форма загрузки файла -->
                <div class="mt-4 pt-4 border-t">
                    <form @submit.prevent="uploadFile()" enctype="multipart/form-data">
                        <div class="flex items-center gap-3">
                            <input type="file" x-ref="fileInput" @change="selectedFile = $event.target.files[0]"
                                   class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.mp4,.mov">
                            <button type="submit"
                                    :disabled="!selectedFile || uploading"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0">
                                <span x-show="!uploading">Загрузить</span>
                                <span x-show="uploading">Загрузка...</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Макс. 50 МБ. Форматы: jpg, png, gif, webp, pdf, doc(x), xls(x), zip, rar, mp4, mov</p>
                        <p class="text-xs text-red-500 mt-1" x-show="fileError" x-text="fileError"></p>
                    </form>
                </div>
            </div>

            <!-- Ссылки -->
            <div class="bg-white rounded-lg shadow-sm border p-5" x-data="linksComponent()">
                <h3 class="text-sm font-medium text-gray-500 mb-4">
                    Ссылки (<span x-text="links.length"><?= count($links) ?></span>)
                </h3>

                <!-- Список ссылок -->
                <div class="space-y-2" x-show="links.length > 0">
                    <template x-for="link in links" :key="link.id">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                            </svg>
                            <a :href="link.url" target="_blank" rel="noopener"
                               class="text-sm text-blue-600 hover:text-blue-800 truncate flex-1"
                               x-text="link.title || link.url"></a>
                            <span class="text-xs text-gray-400" x-text="link.user_name"></span>
                            <button @click="deleteLink(link.id)"
                                    x-show="link.user_id == currentUserId || isAdminOrManager"
                                    class="text-xs text-red-500 hover:text-red-700 flex-shrink-0">
                                Удалить
                            </button>
                        </div>
                    </template>
                </div>

                <p class="text-sm text-gray-400" x-show="links.length === 0">Ссылок нет</p>

                <!-- Форма добавления ссылки -->
                <div class="mt-4 pt-4 border-t">
                    <form @submit.prevent="addLink()">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="url" x-model="newUrl" placeholder="https://..."
                                   class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   required>
                            <input type="text" x-model="newTitle" placeholder="Название (необязательно)"
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <p class="text-xs text-red-500" x-show="linkError" x-text="linkError"></p>
                            <button type="submit"
                                    :disabled="!newUrl.trim() || linkSending"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Добавить ссылку
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- История действий -->
            <?php include BASE_PATH . '/views/components/activity-log.php'; ?>

            <script>
            function filesComponent() {
                return {
                    files: <?= json_encode(array_map(function($f) {
                        return [
                            'id' => (int) $f['id'],
                            'file_name' => $f['file_name'],
                            'file_size' => (int) $f['file_size'],
                            'file_type' => $f['file_type'] ?? '',
                            'uploaded_by' => (int) $f['uploaded_by'],
                            'uploader_name' => $f['uploader_name'] ?? '',
                            'created_at' => $f['created_at'],
                        ];
                    }, $files), JSON_UNESCAPED_UNICODE) ?>,
                    selectedFile: null,
                    uploading: false,
                    fileError: '',
                    currentUserId: <?= (int) \Helpers\Auth::id() ?>,
                    isAdminOrManager: <?= ($roleId === 1 || $roleId === 2) ? 'true' : 'false' ?>,
                    taskId: <?= (int) $task['id'] ?>,

                    async uploadFile() {
                        if (!this.selectedFile || this.uploading) return;
                        this.uploading = true;
                        this.fileError = '';

                        try {
                            const formData = new FormData();
                            formData.append('file', this.selectedFile);

                            const response = await fetch(BASE_URL + `/tasks/${this.taskId}/files`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData,
                            });

                            const data = await response.json();

                            if (data.success && data.file) {
                                this.files.unshift({
                                    id: parseInt(data.file.id),
                                    file_name: data.file.file_name,
                                    file_size: data.file.file_size,
                                    file_type: data.file.file_type,
                                    uploaded_by: this.currentUserId,
                                    uploader_name: data.file.uploader_name,
                                    created_at: data.file.created_at,
                                });
                                this.selectedFile = null;
                                this.$refs.fileInput.value = '';
                            } else {
                                this.fileError = data.error || 'Ошибка при загрузке файла';
                            }
                        } catch (e) {
                            this.fileError = 'Ошибка сети. Попробуйте ещё раз.';
                        } finally {
                            this.uploading = false;
                        }
                    },

                    async deleteFile(fileId) {
                        if (!confirm('Удалить файл?')) return;

                        try {
                            const response = await fetch(BASE_URL + `/files/${fileId}/delete`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.files = this.files.filter(f => f.id !== fileId);
                            } else {
                                alert(data.error || 'Ошибка при удалении');
                            }
                        } catch (e) {
                            alert('Ошибка сети.');
                        }
                    },

                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        const d = new Date(dateStr);
                        const pad = (n) => n.toString().padStart(2, '0');
                        return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()}`;
                    },

                    formatSize(bytes) {
                        if (bytes < 1024) return bytes + ' Б';
                        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
                        return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
                    },
                };
            }

            function linksComponent() {
                return {
                    links: <?= json_encode(array_map(function($l) {
                        return [
                            'id' => (int) $l['id'],
                            'url' => $l['url'],
                            'title' => $l['title'] ?? '',
                            'user_id' => (int) $l['user_id'],
                            'user_name' => $l['user_name'] ?? '',
                            'created_at' => $l['created_at'],
                        ];
                    }, $links), JSON_UNESCAPED_UNICODE) ?>,
                    newUrl: '',
                    newTitle: '',
                    linkSending: false,
                    linkError: '',
                    currentUserId: <?= (int) \Helpers\Auth::id() ?>,
                    isAdminOrManager: <?= ($roleId === 1 || $roleId === 2) ? 'true' : 'false' ?>,
                    taskId: <?= (int) $task['id'] ?>,

                    async addLink() {
                        if (!this.newUrl.trim() || this.linkSending) return;
                        this.linkSending = true;
                        this.linkError = '';

                        try {
                            const formData = new FormData();
                            formData.append('url', this.newUrl.trim());
                            formData.append('title', this.newTitle.trim());

                            const response = await fetch(BASE_URL + `/tasks/${this.taskId}/links`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData,
                            });

                            const data = await response.json();

                            if (data.success && data.link) {
                                this.links.unshift({
                                    id: parseInt(data.link.id),
                                    url: data.link.url,
                                    title: data.link.title,
                                    user_id: this.currentUserId,
                                    user_name: data.link.user_name,
                                    created_at: data.link.created_at,
                                });
                                this.newUrl = '';
                                this.newTitle = '';
                            } else {
                                this.linkError = data.error || 'Ошибка при добавлении ссылки';
                            }
                        } catch (e) {
                            this.linkError = 'Ошибка сети. Попробуйте ещё раз.';
                        } finally {
                            this.linkSending = false;
                        }
                    },

                    async deleteLink(linkId) {
                        if (!confirm('Удалить ссылку?')) return;

                        try {
                            const response = await fetch(BASE_URL + `/links/${linkId}/delete`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.links = this.links.filter(l => l.id !== linkId);
                            } else {
                                alert(data.error || 'Ошибка при удалении');
                            }
                        } catch (e) {
                            alert('Ошибка сети.');
                        }
                    },
                };
            }
            </script>
        </div>

        <!-- Правая колонка: информация + действия -->
        <div class="space-y-6">

            <!-- Информация о задаче -->
            <div class="bg-white rounded-lg shadow-sm border p-5 space-y-4">
                <h3 class="text-sm font-medium text-gray-500 border-b pb-2">Информация</h3>

                <!-- Статус -->
                <div>
                    <label class="text-xs text-gray-400">Статус</label>
                    <?php if ($canEdit && $task['status_code'] !== 'closed'): ?>
                        <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/status') ?>" class="mt-1" x-data>
                            <?= csrf_field() ?>
                            <select name="status_id" onchange="this.form.submit()"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" <?= (int) $task['status_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                        <?= e($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <div class="mt-1">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                <?= e($task['status_name'] ?? '') ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Приоритет -->
                <div>
                    <label class="text-xs text-gray-400">Приоритет</label>
                    <div class="mt-1">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium <?= $prio['class'] ?>">
                            <?= $prio['label'] ?>
                        </span>
                    </div>
                </div>

                <!-- Исполнитель -->
                <div>
                    <label class="text-xs text-gray-400">Исполнитель</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['assigned_name'] ?? 'Не назначен') ?></p>
                </div>

                <!-- Автор -->
                <div>
                    <label class="text-xs text-gray-400">Автор</label>
                    <p class="text-sm text-gray-800 mt-1"><?= e($task['creator_name'] ?? '—') ?></p>
                </div>

                <!-- Срок -->
                <div>
                    <label class="text-xs text-gray-400">Срок</label>
                    <p class="text-sm mt-1 <?= $isOverdue ? 'text-red-600 font-medium' : 'text-gray-800' ?>">
                        <?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : 'Не установлен' ?>
                    </p>
                </div>

                <!-- Создана -->
                <div>
                    <label class="text-xs text-gray-400">Создана</label>
                    <p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></p>
                </div>

                <?php if ($task['closed_at']): ?>
                <div>
                    <label class="text-xs text-gray-400">Закрыта</label>
                    <p class="text-sm text-gray-600 mt-1"><?= date('d.m.Y H:i', strtotime($task['closed_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Переназначение исполнителя -->
            <?php if ($canEdit && $task['status_code'] !== 'closed'): ?>
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="text-sm font-medium text-gray-500 border-b pb-2 mb-3">Переназначить</h3>
                <form method="POST" action="<?= url('/tasks/' . (int) $task['id'] . '/reassign') ?>">
                    <?= csrf_field() ?>
                    <select name="assigned_to" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-2">
                        <option value="">Не назначен</option>
                        <?php foreach ($projectUsers as $pu): ?>
                            <option value="<?= (int) $pu['id'] ?>" <?= (int) ($task['assigned_to'] ?? 0) === (int) $pu['id'] ? 'selected' : '' ?>>
                                <?= e($pu['name']) ?> (<?= $pu['project_role'] === 'manager' ? 'руководитель' : 'исполнитель' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700 transition">
                        Назначить
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Блокирующие подзадачи (если задачу нельзя закрыть) -->
            <?php if (!$canClose['can'] && !empty($canClose['blocking'])): ?>
            <div class="bg-orange-50 rounded-lg border border-orange-200 p-4">
                <h4 class="text-sm font-medium text-orange-800 mb-2">Блокирующие подзадачи</h4>
                <p class="text-xs text-orange-600 mb-2">Задачу нельзя закрыть, пока не завершены:</p>
                <ul class="space-y-1">
                    <?php foreach (array_slice($canClose['blocking'], 0, 10) as $blocking): ?>
                        <li class="text-xs text-orange-700">
                            • <?= e($blocking['title']) ?> <span class="text-orange-500">(<?= e($blocking['status_name']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (count($canClose['blocking']) > 10): ?>
                        <li class="text-xs text-orange-500">...и ещё <?= count($canClose['blocking']) - 10 ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
