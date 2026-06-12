<?php
/**
 * Компонент чата задачи — views/components/task-chat.php
 *
 * Единый мессенджер-интерфейс для комментариев, файлов и ссылок.
 * Сообщения отображаются в виде «пузырей» (bubbles) как в Telegram/WhatsApp.
 *
 * Ожидаемые переменные:
 *   $task — массив данных задачи
 *   $comments — массив сообщений (с user_name, created_at, comment_text, files, links)
 */

$currentUserId = \Helpers\Auth::id();
$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);
?>

<div class="bg-white rounded-lg shadow-sm border flex flex-col" style="height: 500px;" x-data="taskChat()">
    <!-- Заголовок -->
    <div class="px-4 py-3 border-b bg-gray-50 rounded-t-lg flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700">
            Чат задачи (<span x-text="messages.length"><?= count($comments) ?></span>)
        </h3>
    </div>

    <!-- Область сообщений (скролл) -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages" x-ref="chatMessages">
        <!-- Пустое состояние -->
        <div x-show="messages.length === 0" class="flex items-center justify-center h-full">
            <p class="text-sm text-gray-400">Сообщений пока нет. Начните обсуждение!</p>
        </div>

        <template x-for="msg in messages" :key="msg.id">
            <div>
                <!-- Своё сообщение (справа) -->
                <div x-show="msg.user_id == currentUserId" class="flex justify-end">
                    <div class="max-w-[80%]">
                        <!-- Текстовая часть (синий фон) -->
                        <div x-show="msg.comment_text && msg.comment_text !== '📎 Файл'" class="bg-blue-500 text-white rounded-lg px-3 py-2" :class="{'mb-1': msg.files.length > 0}">
                            <div class="text-sm whitespace-pre-wrap" x-text="msg.comment_text"></div>
                            <div x-show="!msg.files.length && !msg.links.length" class="text-xs text-blue-200 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                        </div>
                        <!-- Прикреплённые файлы (без фона для изображений) -->
                        <template x-for="file in msg.files" :key="file.id">
                            <div class="mt-1">
                                <!-- Изображение — превью без фона -->
                                <template x-if="isImage(file.file_type)">
                                    <img :src="BASE_URL + '/files/' + file.id + '/download'"
                                         @click="openModal(file)"
                                         class="max-w-full max-h-48 rounded-lg cursor-pointer hover:opacity-90 transition shadow-sm"
                                         :alt="file.file_name">
                                </template>
                                <!-- Не изображение — иконка + имя -->
                                <template x-if="!isImage(file.file_type)">
                                    <a :href="BASE_URL + '/files/' + file.id + '/download'"
                                       class="flex items-center gap-2 p-2 bg-blue-500 rounded-lg text-xs text-white hover:bg-blue-600 transition">
                                        <span>📄</span>
                                        <span x-text="file.file_name + ' (' + formatSize(file.file_size) + ')'"></span>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <!-- Ссылки -->
                        <template x-for="link in msg.links" :key="link.id">
                            <a :href="link.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 mt-1 p-2 bg-blue-500 rounded-lg text-xs text-white hover:bg-blue-600 transition">
                                <span>🔗</span>
                                <span x-text="link.title || link.url"></span>
                            </a>
                        </template>
                        <!-- Время (если есть файлы/ссылки — показываем отдельно) -->
                        <div x-show="msg.files.length || msg.links.length" class="text-xs text-gray-400 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                    </div>
                </div>

                <!-- Чужое сообщение (слева) -->
                <div x-show="msg.user_id != currentUserId" class="flex justify-start">
                    <div class="max-w-[80%]">
                        <!-- Имя отправителя -->
                        <div class="text-xs font-medium text-gray-500 mb-1 ml-1" x-text="msg.user_name"></div>
                        <!-- Текстовая часть (серый фон) -->
                        <div x-show="msg.comment_text && msg.comment_text !== '📎 Файл'" class="bg-gray-100 rounded-lg px-3 py-2" :class="{'mb-1': msg.files.length > 0}">
                            <div class="text-sm text-gray-800 whitespace-pre-wrap" x-text="msg.comment_text"></div>
                            <div x-show="!msg.files.length && !msg.links.length" class="text-xs text-gray-400 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                        </div>
                        <!-- Прикреплённые файлы (без фона для изображений) -->
                        <template x-for="file in msg.files" :key="file.id">
                            <div class="mt-1">
                                <!-- Изображение — превью без фона -->
                                <template x-if="isImage(file.file_type)">
                                    <img :src="BASE_URL + '/files/' + file.id + '/download'"
                                         @click="openModal(file)"
                                         class="max-w-full max-h-48 rounded-lg cursor-pointer hover:opacity-90 transition shadow-sm"
                                         :alt="file.file_name">
                                </template>
                                <!-- Не изображение — иконка + имя -->
                                <template x-if="!isImage(file.file_type)">
                                    <a :href="BASE_URL + '/files/' + file.id + '/download'"
                                       class="flex items-center gap-2 p-2 bg-gray-100 rounded-lg border text-xs text-blue-600 hover:bg-blue-50 transition">
                                        <span>📄</span>
                                        <span x-text="file.file_name + ' (' + formatSize(file.file_size) + ')'"></span>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <!-- Ссылки -->
                        <template x-for="link in msg.links" :key="link.id">
                            <a :href="link.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 mt-1 p-2 bg-gray-100 rounded-lg border text-xs text-blue-600 hover:bg-blue-50 transition">
                                <span>🔗</span>
                                <span x-text="link.title || link.url"></span>
                            </a>
                        </template>
                        <!-- Время -->
                        <div x-show="msg.files.length || msg.links.length" class="text-xs text-gray-400 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Модальное окно для просмотра изображения -->
    <div x-show="modalFile" x-transition.opacity
         @click.self="modalFile = null; modalZoom = 1;"
         @keydown.escape.window="modalFile = null; modalZoom = 1;"
         class="fixed inset-0 z-[100] bg-black bg-opacity-80 flex items-center justify-center p-4"
         style="display: none;">
        <div class="relative max-w-5xl max-h-[90vh] flex flex-col items-center">
            <!-- Панель кнопок (правый верхний угол) -->
            <div class="absolute top-2 right-2 flex items-center gap-2 z-10">
                <!-- Уменьшить -->
                <button @click="modalZoom = Math.max(0.25, modalZoom - 0.25)"
                        class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full shadow transition"
                        title="Уменьшить">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <!-- Увеличить -->
                <button @click="modalZoom = Math.min(3, modalZoom + 0.25)"
                        class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full shadow transition"
                        title="Увеличить">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <!-- Скачать -->
                <a :href="modalFile ? BASE_URL + '/files/' + modalFile.id + '/download' + '?force=1' : '#'"
                   class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full shadow transition"
                   title="Скачать" download>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </a>
                <!-- Закрыть -->
                <button @click="modalFile = null; modalZoom = 1;"
                        class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full shadow transition"
                        title="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Изображение с зумом -->
            <div class="overflow-auto max-h-[85vh] max-w-full">
                <img :src="modalFile ? BASE_URL + '/files/' + modalFile.id + '/download' : ''"
                     :style="'transform: scale(' + modalZoom + '); transform-origin: center center; transition: transform 0.2s;'"
                     class="max-w-full rounded-lg shadow-2xl"
                     :alt="modalFile ? modalFile.file_name : ''">
            </div>
            <!-- Масштаб -->
            <div class="mt-2 text-white text-xs opacity-70" x-text="Math.round(modalZoom * 100) + '%'"></div>
        </div>
    </div>

    <!-- Превью прикреплённого файла -->
    <div x-show="attachedFile" class="px-4 py-2 border-t bg-gray-50 flex items-center gap-2">
        <span class="text-xs text-gray-600">📎</span>
        <span class="text-xs text-gray-700 truncate flex-1" x-text="attachedFile ? attachedFile.name : ''"></span>
        <button @click="removeAttachment()" class="text-xs text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Поле ввода -->
    <div class="border-t p-3">
        <div class="flex items-end gap-2">
            <!-- Кнопка прикрепить файл -->
            <label class="cursor-pointer text-gray-400 hover:text-gray-600 p-1 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <input type="file" class="hidden" x-ref="fileInput" @change="attachFile($event)"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.mp4,.mov">
            </label>

            <!-- Textarea с авто-увеличением -->
            <textarea x-model="newMessage"
                      x-ref="messageInput"
                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                      @input="autoResize($event)"
                      placeholder="Написать сообщение..."
                      rows="1"
                      maxlength="5000"
                      class="flex-1 resize-none border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                      style="max-height: 80px; overflow-y: auto;"></textarea>

            <!-- Кнопка отправить -->
            <button @click="sendMessage()"
                    :disabled="sending || (newMessage.trim() === '' && !attachedFile)"
                    class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
        <!-- Ошибка -->
        <p class="text-xs text-red-500 mt-1" x-show="errorMessage" x-text="errorMessage"></p>
    </div>
</div>

<script>
function taskChat() {
    return {
        messages: <?= json_encode(array_map(function($c) {
            return [
                'id' => (int) $c['id'],
                'task_id' => (int) $c['task_id'],
                'user_id' => (int) $c['user_id'],
                'comment_text' => $c['comment_text'],
                'user_name' => $c['user_name'] ?? $c['user_login'] ?? '',
                'created_at' => $c['created_at'],
                'files' => array_map(function($f) {
                    return [
                        'id' => (int) $f['id'],
                        'file_name' => $f['file_name'],
                        'file_size' => (int) $f['file_size'],
                        'file_type' => $f['file_type'] ?? '',
                    ];
                }, $c['files'] ?? []),
                'links' => array_map(function($l) {
                    return [
                        'id' => (int) $l['id'],
                        'url' => $l['url'],
                        'title' => $l['title'] ?? '',
                    ];
                }, $c['links'] ?? []),
            ];
        }, $comments), JSON_UNESCAPED_UNICODE) ?>,
        newMessage: '',
        attachedFile: null,
        modalFile: null,
        modalZoom: 1,
        sending: false,
        errorMessage: '',
        currentUserId: <?= (int) $currentUserId ?>,
        taskId: <?= (int) $task['id'] ?>,

        /**
         * Инициализация: скролл вниз при загрузке
         */
        init() {
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        /**
         * Отправить сообщение (с файлом или без)
         */
        async sendMessage() {
            const text = this.newMessage.trim();
            if (text === '' && !this.attachedFile) return;
            if (this.sending) return;

            this.sending = true;
            this.errorMessage = '';

            try {
                const formData = new FormData();
                formData.append('comment_text', text || '');

                // Если есть прикреплённый файл — добавляем
                if (this.attachedFile) {
                    formData.append('file', this.attachedFile);
                }

                const response = await fetch(BASE_URL + `/tasks/${this.taskId}/comments`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });

                const responseText = await response.text();
                let data;
                try { data = JSON.parse(responseText); } catch(parseErr) {
                    this.errorMessage = 'Ошибка сервера';
                    return;
                }

                if (data.success && data.comment) {
                    this.messages.push({
                        id: parseInt(data.comment.id),
                        task_id: this.taskId,
                        user_id: this.currentUserId,
                        comment_text: data.comment.comment_text,
                        user_name: data.comment.user_name,
                        created_at: data.comment.created_at,
                        files: data.comment.files || [],
                        links: data.comment.links || [],
                    });
                    this.newMessage = '';
                    this.removeAttachment();
                    this.$nextTick(() => this.scrollToBottom());

                    // Сбрасываем высоту textarea
                    if (this.$refs.messageInput) {
                        this.$refs.messageInput.style.height = 'auto';
                    }
                } else {
                    this.errorMessage = data.error || 'Ошибка при отправке сообщения';
                }
            } catch (e) {
                this.errorMessage = 'Ошибка сети. Попробуйте ещё раз.';
            } finally {
                this.sending = false;
            }
        },

        /**
         * Прикрепить файл
         */
        attachFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Проверка размера (50 МБ)
            if (file.size > 50 * 1024 * 1024) {
                this.errorMessage = 'Файл слишком большой (максимум 50 МБ)';
                this.$refs.fileInput.value = '';
                return;
            }

            this.attachedFile = file;
            this.errorMessage = '';
        },

        /**
         * Убрать прикреплённый файл
         */
        removeAttachment() {
            this.attachedFile = null;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        /**
         * Авто-увеличение textarea
         */
        autoResize(event) {
            const el = event.target;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 80) + 'px';
        },

        /**
         * Скролл к последнему сообщению
         */
        scrollToBottom() {
            const container = this.$refs.chatMessages;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        /**
         * Форматирование времени (HH:MM)
         */
        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            const pad = (n) => n.toString().padStart(2, '0');

            // Если сегодня — только время
            if (d.toDateString() === now.toDateString()) {
                return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }

            // Если в этом году — дата без года
            if (d.getFullYear() === now.getFullYear()) {
                return `${pad(d.getDate())}.${pad(d.getMonth()+1)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }

            // Полная дата
            return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        /**
         * Форматирование размера файла
         */
        formatSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' Б';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
            return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
        },

        /**
         * Проверка: является ли файл изображением
         */
        isImage(fileType) {
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes((fileType || '').toLowerCase());
        },

        /**
         * Открыть модальное окно для просмотра изображения
         */
        openModal(file) {
            this.modalFile = file;
            this.modalZoom = 1;
        },
    };
}
</script>
