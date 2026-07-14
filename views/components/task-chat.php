<?php
/**
 * Компонент чата задачи — views/components/task-chat.php
 *
 * Единый мессенджер-интерфейс для комментариев, файлов и ссылок.
 * Сообщения отображаются в виде «пузырей» (bubbles) как в Telegram/WhatsApp.
 * Поддержка: кликабельные URL, контекстное меню (ответ, редактирование, удаление).
 *
 * Ожидаемые переменные:
 *   $task — массив данных задачи
 *   $comments — массив сообщений (с user_name, created_at, comment_text, files, links)
 */

$currentUserId = \Helpers\Auth::id();
$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);
?>

<div class="bg-white lg:rounded-lg lg:shadow-sm lg:border flex flex-col flex-1 min-h-0" x-data="taskChat()">

    <!-- Закреплённые сообщения -->
    <template x-if="messages.filter(m => m.is_pinned).length > 0">
        <div class="border-b bg-blue-50 px-3 py-2 flex-shrink-0" x-data="{ pinsOpen: false }">
            <!-- Одна строка: иконка + счётчик/текст + стрелка (фиксированная справа) -->
            <div class="flex items-center gap-2 cursor-pointer" @click="pinsOpen = !pinsOpen">
                <span class="text-blue-500 flex-shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg></span>
                <span class="text-xs font-medium text-blue-700 flex-shrink-0"
                      x-text="messages.filter(m => m.is_pinned).length"></span>
                <span class="text-xs text-blue-600 truncate flex-1"
                      x-show="!pinsOpen"
                      x-text="messages.filter(m => m.is_pinned)[0]?.comment_text || ''"></span>
                <svg class="w-4 h-4 text-blue-400 transition-transform flex-shrink-0 ml-auto" :class="pinsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <!-- Развёрнутый список -->
            <div x-show="pinsOpen" x-transition class="mt-2 space-y-1.5 max-h-40 overflow-y-auto">
                <template x-for="pin in messages.filter(m => m.is_pinned)" :key="'pin-' + pin.id">
                    <div class="flex items-center gap-2 text-xs bg-white rounded px-2 py-1.5 border border-blue-100">
                        <span class="font-medium text-blue-700 flex-shrink-0" x-text="pin.user_name"></span>
                        <span class="text-gray-700 truncate flex-1" x-text="pin.comment_text"></span>
                        <button @click.stop="togglePin(pin)" class="text-gray-400 hover:text-red-500 flex-shrink-0" title="Открепить">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Область сообщений (скролл) -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages" x-ref="chatMessages" style="visibility: hidden;">
        <!-- Пустое состояние -->
        <div x-show="messages.length === 0" class="flex items-center justify-center h-full">
            <p class="text-sm text-gray-400">Сообщений пока нет. Начните обсуждение!</p>
        </div>

        <template x-for="msg in messages" :key="msg.id">
            <div>
                <!-- Своё сообщение (справа) -->
                <div x-show="msg.user_id == currentUserId" class="flex justify-end">
                    <div class="max-w-[80%]"
                         @contextmenu.prevent="showContext($event, msg)"
                         @touchstart="startLongPress($event, msg)"
                         @touchend="cancelLongPress()"
                         @touchmove="cancelLongPress()">
                        <!-- Текстовая часть (синий фон) -->
                        <div x-show="msg.comment_text && msg.comment_text !== '📎 Файл'" class="bg-blue-500 text-white rounded-lg px-3 py-2" :class="{'mb-1': msg.files.length > 0}">
                            <!-- Цитата (ответ на сообщение) -->
                            <template x-if="getParentMessage(msg)">
                                <div class="bg-blue-400 bg-opacity-50 rounded px-2 py-1 mb-2 border-l-2 border-white border-opacity-70 text-xs">
                                    <div class="font-medium text-blue-100" x-text="getParentMessage(msg).user_name"></div>
                                    <div class="text-blue-200 truncate" x-text="getParentMessage(msg).comment_text"></div>
                                </div>
                            </template>
                            <div class="text-sm whitespace-pre-wrap" x-html="linkify(msg.comment_text)"></div>
                            <div x-show="!msg.files.length && !msg.links.length" class="text-xs text-blue-200 mt-1 text-right flex items-center justify-end gap-1">
                                <span x-text="formatTime(msg.created_at)"></span>
                                <!-- Галочки прочтения (WhatsApp-стиль) -->
                                <svg x-show="msg.read_by_others" class="w-4 h-4 text-blue-200" viewBox="0 0 16 12" fill="none"><path d="M1.5 6.5L5 10L11 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 6.5L9 10L15 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <svg x-show="!msg.read_by_others" class="w-4 h-4 text-blue-300 opacity-70" viewBox="0 0 16 12" fill="none"><path d="M3.5 6.5L7 10L13 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>
                        <!-- Прикреплённые файлы (без фона для изображений) -->
                        <template x-for="file in msg.files" :key="file.id">
                            <div class="mt-1">
                                <!-- Изображение — превью -->
                                <template x-if="isImage(file.file_type)">
                                    <img :src="BASE_URL + '/files/' + file.id + '/download?t=' + (file.updated || file.id)"
                                         @click="openModal(file, msg.user_id)"
                                         class="max-w-full max-h-48 rounded-lg cursor-pointer hover:opacity-90 transition shadow-sm border border-white/80"
                                         :alt="file.file_name">
                                </template>
                                <!-- Видео — встроенный плеер -->
                                <template x-if="isVideo(file.file_type)">
                                    <video :src="BASE_URL + '/files/' + file.id + '/download'"
                                           @click="openModal(file, msg.user_id)"
                                           controls preload="metadata"
                                           class="max-w-full max-h-56 rounded-lg shadow-sm cursor-pointer">
                                    </video>
                                </template>
                                <!-- Остальные файлы — иконка + имя -->
                                <template x-if="!isImage(file.file_type) && !isVideo(file.file_type)">
                                    <a :href="BASE_URL + '/files/' + file.id + '/download?t=' + (file.updated || file.id)"
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
                        <!-- Время (если есть файлы/ссылки — показываем отдельно) + галочки -->
                        <div x-show="msg.files.length || msg.links.length" class="text-xs text-gray-400 mt-1 text-right flex items-center justify-end gap-1">
                            <span x-text="formatTime(msg.created_at)"></span>
                            <!-- Галочки прочтения (WhatsApp-стиль) -->
                            <svg x-show="msg.read_by_others" class="w-4 h-4 text-blue-500" viewBox="0 0 16 12" fill="none"><path d="M1.5 6.5L5 10L11 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 6.5L9 10L15 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <svg x-show="!msg.read_by_others" class="w-4 h-4 text-gray-400" viewBox="0 0 16 12" fill="none"><path d="M3.5 6.5L7 10L13 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Чужое сообщение (слева) -->
                <div x-show="msg.user_id != currentUserId" class="flex justify-start">
                    <div class="max-w-[80%]"
                         @contextmenu.prevent="showContext($event, msg)"
                         @touchstart="startLongPress($event, msg)"
                         @touchend="cancelLongPress()"
                         @touchmove="cancelLongPress()">
                        <!-- Имя отправителя -->
                        <div class="text-xs font-medium text-gray-500 mb-1 ml-1" x-text="msg.user_name"></div>
                        <!-- Текстовая часть (серый фон) -->
                        <div x-show="msg.comment_text && msg.comment_text !== '📎 Файл'" class="bg-gray-100 rounded-lg px-3 py-2" :class="{'mb-1': msg.files.length > 0}">
                            <!-- Цитата (ответ на сообщение) -->
                            <template x-if="getParentMessage(msg)">
                                <div class="bg-gray-200 rounded px-2 py-1 mb-2 border-l-2 border-blue-400 text-xs">
                                    <div class="font-medium text-blue-600" x-text="getParentMessage(msg).user_name"></div>
                                    <div class="text-gray-600 truncate" x-text="getParentMessage(msg).comment_text"></div>
                                </div>
                            </template>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap" x-html="linkify(msg.comment_text)"></div>
                            <div x-show="!msg.files.length && !msg.links.length" class="text-xs text-gray-400 mt-1 text-right" x-text="formatTime(msg.created_at)"></div>
                        </div>
                        <!-- Прикреплённые файлы (без фона для изображений) -->
                        <template x-for="file in msg.files" :key="file.id">
                            <div class="mt-1">
                                <!-- Изображение — превью -->
                                <template x-if="isImage(file.file_type)">
                                    <img :src="BASE_URL + '/files/' + file.id + '/download?t=' + (file.updated || file.id)"
                                         @click="openModal(file, msg.user_id)"
                                         class="max-w-full max-h-48 rounded-lg cursor-pointer hover:opacity-90 transition shadow-sm border border-gray-200"
                                         :alt="file.file_name">
                                </template>
                                <!-- Видео — встроенный плеер -->
                                <template x-if="isVideo(file.file_type)">
                                    <video :src="BASE_URL + '/files/' + file.id + '/download'"
                                           @click="openModal(file, msg.user_id)"
                                           controls preload="metadata"
                                           class="max-w-full max-h-56 rounded-lg shadow-sm border border-gray-200 cursor-pointer">
                                    </video>
                                </template>
                                <!-- Остальные файлы — иконка + имя -->
                                <template x-if="!isImage(file.file_type) && !isVideo(file.file_type)">
                                    <a :href="BASE_URL + '/files/' + file.id + '/download?t=' + (file.updated || file.id)"
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

    <!-- Контекстное меню сообщения -->
    <div x-show="contextMenu.show"
         x-transition
         @click.outside="contextMenu.show = false"
         :style="'position: fixed; left: ' + contextMenu.x + 'px; top: ' + contextMenu.y + 'px;'"
         class="z-[200] bg-white rounded-lg shadow-lg border py-1 min-w-[150px]"
         style="display: none;">
        <!-- Ответить -->
        <button @click="replyToMessage(contextMenu.msg); contextMenu.show = false"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Ответить
        </button>
        <!-- Закрепить/Открепить -->
        <button @click="togglePin(contextMenu.msg); contextMenu.show = false"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <span x-text="contextMenu.msg && contextMenu.msg.is_pinned ? 'Открепить' : 'Закрепить'"></span>
        </button>
        <!-- Редактировать (только свои) -->
        <button x-show="contextMenu.msg && contextMenu.msg.user_id == currentUserId"
                @click="editMessage(contextMenu.msg); contextMenu.show = false"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Редактировать
        </button>
        <!-- Удалить (только свои) -->
        <button x-show="contextMenu.msg && contextMenu.msg.user_id == currentUserId"
                @click="deleteMessage(contextMenu.msg); contextMenu.show = false"
                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Удалить
        </button>
    </div>

    <!-- Модальное окно для просмотра изображения -->
    <div x-show="modalFile" x-transition.opacity
         @click.self="modalFile = null; modalZoom = 1; $nextTick(() => scrollToBottom());"
         @keydown.escape.window="modalFile = null; modalZoom = 1; $nextTick(() => scrollToBottom());"
         class="fixed inset-0 z-[100] bg-black bg-opacity-90 flex flex-col"
         style="display: none;">
        <!-- Верхняя панель -->
        <div class="flex items-center justify-between px-4 py-2 bg-black bg-opacity-50">
            <span class="text-white text-sm truncate" x-text="modalFile ? modalFile.file_name : ''"></span>
            <div class="flex items-center gap-2">
                <!-- Редактировать (только изображения, только свои) -->
                <button x-show="modalFile && isImage(modalFile.file_type) && modalFileOwnerId == currentUserId"
                        @click="$dispatch('open-editor', {file: modalFile}); modalFile = null; modalZoom = 1;"
                        class="text-white hover:text-green-300 p-2 rounded transition" title="Редактировать">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                <!-- Скачать -->
                <a :href="modalFile ? BASE_URL + '/files/' + modalFile.id + '/download' + '?force=1' : '#'"
                   class="text-white hover:text-blue-300 p-2 rounded transition" title="Скачать" download>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </a>
                <!-- Закрыть -->
                <button @click="modalFile = null; modalZoom = 1; $nextTick(() => scrollToBottom());"
                        class="text-white hover:text-red-300 p-2 rounded transition" title="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <!-- Область медиа (изображение или видео) -->
        <div class="flex-1 overflow-auto flex items-center justify-center" @click.self="modalFile = null; modalZoom = 1; $nextTick(() => scrollToBottom());">
            <!-- Изображение -->
            <img x-show="modalFile && isImage(modalFile.file_type)"
                 :src="modalFile ? BASE_URL + '/files/' + modalFile.id + '/download?t=' + (modalFile.updated || modalFile.id) : ''"
                 :style="'transform: scale(' + modalZoom + '); transition: transform 0.2s;'"
                 class="max-w-full max-h-full object-contain"
                 :alt="modalFile ? modalFile.file_name : ''">
            <!-- Видео -->
            <video x-show="modalFile && isVideo(modalFile.file_type)"
                   :src="modalFile && isVideo(modalFile.file_type) ? BASE_URL + '/files/' + modalFile.id + '/download' : ''"
                   controls autoplay
                   class="max-w-full max-h-full object-contain">
            </video>
        </div>
        <!-- Ползунок зума внизу (только для изображений) -->
        <div x-show="modalFile && isImage(modalFile.file_type)" class="px-4 py-3 bg-black bg-opacity-50 flex items-center gap-3">
            <button @click="modalZoom = Math.max(0.25, modalZoom - 0.25)" class="text-white hover:text-blue-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
            <input type="range" min="25" max="300" step="25"
                   :value="modalZoom * 100"
                   @input="modalZoom = $event.target.value / 100"
                   class="flex-1 h-1.5 bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-500">
            <button @click="modalZoom = Math.min(3, modalZoom + 0.25)" class="text-white hover:text-blue-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <span class="text-white text-xs w-12 text-center" x-text="Math.round(modalZoom * 100) + '%'"></span>
        </div>
    </div>

    <!-- Превью прикреплённого файла -->
    <div x-show="attachedFile" x-cloak class="px-4 py-2 border-t bg-gray-50 flex items-center gap-2" style="display:none">>
        <span class="text-xs text-gray-600">📎</span>
        <span class="text-xs text-gray-700 truncate flex-1" x-text="attachedFile ? attachedFile.name : ''"></span>
        <!-- Toggle Редактировать (только для изображений) -->
        <label x-show="attachedFile && isImageFile(attachedFile)"
               class="flex items-center gap-1 cursor-pointer select-none mr-2">
            <span class="text-xs text-gray-500">Редактировать</span>
            <div class="relative">
                <input type="checkbox" x-model="editBeforeSend" class="sr-only">
                <div class="w-8 h-4 rounded-full transition"
                     :class="editBeforeSend ? 'bg-blue-500' : 'bg-gray-300'"></div>
                <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white transition-transform"
                     :class="editBeforeSend ? 'translate-x-4' : ''"></div>
            </div>
        </label>
        <button @click="removeAttachment()" class="text-xs text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Блок «Ответ на...» или «Редактирование» -->
    <div x-show="replyTo || editingMsg" class="px-4 py-2 border-t bg-blue-50 flex items-center justify-between" style="display:none !important" :style="(replyTo || editingMsg) ? 'display:flex!important' : 'display:none!important'">>
        <div class="text-xs text-blue-700">
            <span x-show="replyTo">↩ Ответ: <strong x-text="replyTo ? replyTo.user_name : ''"></strong></span>
            <span x-show="editingMsg">✏️ Редактирование</span>
        </div>
        <button @click="cancelReply(); cancelEdit();" class="text-xs text-gray-500 hover:text-red-500">✕</button>
    </div>

    <!-- Поле ввода -->
    <div class="border-t p-3">
        <div class="flex items-end gap-2">
            <!-- Кнопка прикрепить файл -->
            <label class="cursor-pointer text-gray-400 hover:text-gray-600 flex-shrink-0 w-8 h-8 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <input type="file" class="hidden" x-ref="fileInput" @change="attachFile($event)"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.mp4,.mov">
            </label>

            <!-- Кнопка эмодзи -->
            <div class="relative flex-shrink-0 self-end">
                <button @click="emojiOpen = !emojiOpen" type="button"
                        class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                <div x-show="emojiOpen" @click.outside="emojiOpen = false" x-transition
                     class="absolute bottom-full left-0 mb-2 bg-white rounded-lg shadow-lg border p-2 grid grid-cols-6 gap-1 w-56 z-50"
                     style="display: none;">
                    <template x-for="emoji in emojis" :key="emoji">
                        <button type="button" @click="insertEmoji(emoji); emojiOpen = false"
                                class="text-xl hover:bg-gray-100 rounded p-1 text-center" x-text="emoji"></button>
                    </template>
                </div>
            </div>

            <!-- Textarea с авто-увеличением -->
            <textarea x-model="newMessage"
                      x-ref="messageInput"
                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                      @input="autoResize($event)"
                      placeholder="Написать..."
                      rows="1"
                      maxlength="5000"
                      class="flex-1 resize-none border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none overflow-hidden"
                      style="max-height: 150px;"></textarea>

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
                'parent_comment_id' => $c['parent_comment_id'] ? (int) $c['parent_comment_id'] : null,
                'is_pinned' => (int) ($c['is_pinned'] ?? 0),
                'read_by_others' => (bool) ($c['read_by_others'] ?? false),
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
        editBeforeSend: false,
        modalFile: null,
        modalFileOwnerId: null,
        modalZoom: 1,
        sending: false,
        errorMessage: '',
        lastMessageId: 0,
        currentUserId: <?= (int) $currentUserId ?>,
        taskId: <?= (int) $task['id'] ?>,
        taskStatus: '<?= e($task['status_code'] ?? 'in_progress') ?>',

        // Контекстное меню
        contextMenu: { show: false, x: 0, y: 0, msg: null },
        longPressTimer: null,
        replyTo: null,
        editingMsg: null,

        // Эмодзи-пикер
        emojiOpen: false,
        emojis: ['😊','😂','❤️','👍','🔥','✅','👏','🙏','💪','⭐','✨','🎉','💯','👀','🤔','😎','🙌','💡','📌','🚀','⚡','🎯','📎','✏️','🗂️','📋','⏰','🔔','💬','📷'],

        /**
         * Инициализация: скролл вниз при загрузке + polling + отметка прочтения
         */
        init() {
            const container = this.$refs.chatMessages;

            // Функция: скролл вниз + показать контейнер
            const revealChat = () => {
                if (container) {
                    container.scrollTop = container.scrollHeight;
                    container.style.visibility = 'visible';
                }
            };

            // Ждём загрузки всех изображений в чате, потом скроллим и показываем
            this.$nextTick(() => {
                this.updateLastMessageId();
                this.markMessagesAsRead();

                if (container) {
                    const images = container.querySelectorAll('img');
                    if (images.length === 0) {
                        revealChat();
                    } else {
                        let loaded = 0;
                        const total = images.length;
                        const onLoad = () => {
                            loaded++;
                            if (loaded >= total) revealChat();
                        };
                        images.forEach(img => {
                            if (img.complete) {
                                onLoad();
                            } else {
                                img.addEventListener('load', onLoad, { once: true });
                                img.addEventListener('error', onLoad, { once: true });
                            }
                        });
                        // Подстраховка: показать через 3 сек если изображения не загрузились
                        setTimeout(revealChat, 3000);
                    }
                } else {
                    revealChat();
                }
            });

            // Polling каждые 5 секунд
            setInterval(() => this.pollNewMessages(), 5000);

            // При возвращении на вкладку — помечаем прочитанными и скроллим
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.markMessagesAsRead();
                    this.scrollToBottom();
                }
            });

            // Слушаем событие из редактора: отправить отредактированный файл в чат
            window.addEventListener('editor-send-file', (e) => {
                const blob = e.detail.blob;
                const fileName = e.detail.fileName || 'image.png';
                const text = e.detail.text || '';
                const file = new File([blob], fileName, { type: blob.type });
                this.attachedFile = file;
                this.editBeforeSend = false;
                this.newMessage = text;
                this.$nextTick(() => this.sendMessage());
            });

            // Слушаем событие из редактора: закрыли без сохранения — отправляем как есть
            window.addEventListener('editor-cancel-send', (e) => {
                this.editBeforeSend = false;
                this.$nextTick(() => this.sendMessage());
            });
        },

        /**
         * Получить родительское сообщение (для цитирования)
         */
        getParentMessage(msg) {
            if (!msg.parent_comment_id) return null;
            return this.messages.find(m => m.id === msg.parent_comment_id) || null;
        },

        /**
         * Превращает URL в тексте в кликабельные ссылки (с экранированием HTML)
         */
        linkify(text) {
            if (!text) return '';
            // Экранируем HTML
            const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            // Заменяем URL на кликабельные ссылки
            const urlRegex = /(https?:\/\/[^\s<]+)/gi;
            return escaped.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener" class="underline hover:no-underline">$1</a>');
        },

        /**
         * Показать контекстное меню (правая кнопка мыши)
         */
        showContext(event, msg) {
            this.contextMenu = { show: true, x: event.clientX, y: event.clientY, msg: msg };
        },

        /**
         * Начать long press (для мобильных устройств)
         */
        startLongPress(event, msg) {
            this.longPressTimer = setTimeout(() => {
                const touch = event.touches[0];
                this.contextMenu = { show: true, x: touch.clientX, y: touch.clientY, msg: msg };
            }, 500);
        },

        /**
         * Отменить long press
         */
        cancelLongPress() {
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },

        /**
         * Ответить на сообщение
         */
        replyToMessage(msg) {
            this.replyTo = msg;
            this.$refs.messageInput.focus();
        },

        /**
         * Отменить ответ
         */
        cancelReply() {
            this.replyTo = null;
        },

        /**
         * Редактировать сообщение
         */
        editMessage(msg) {
            this.editingMsg = msg;
            this.newMessage = msg.comment_text;
            this.$refs.messageInput.focus();
        },

        /**
         * Отменить редактирование
         */
        cancelEdit() {
            this.editingMsg = null;
            this.newMessage = '';
        },

        /**
         * Удалить сообщение
         */
        async deleteMessage(msg) {
            if (!confirm('Удалить сообщение?')) return;
            try {
                const response = await fetch(BASE_URL + `/comments/${msg.id}/delete`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                if (data.success) {
                    this.messages = this.messages.filter(m => m.id !== msg.id);
                }
            } catch(e) {}
        },

        /**
         * Отметить чужие сообщения как прочитанные (AJAX)
         */
        markMessagesAsRead() {
            // Помечаем прочитанными ТОЛЬКО если вкладка активна (пользователь реально видит чат)
            if (document.visibilityState !== 'visible') return;

            // Собираем ID чужих сообщений
            const unreadIds = this.messages
                .filter(m => m.user_id != this.currentUserId)
                .map(m => m.id);

            if (unreadIds.length === 0) return;

            const formData = new FormData();
            unreadIds.forEach(id => formData.append('message_ids[]', id));

            fetch(BASE_URL + `/tasks/${this.taskId}/messages/read`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            }).catch(() => {});
        },

        /**
         * Обновить lastMessageId из текущих сообщений
         */
        updateLastMessageId() {
            if (this.messages.length > 0) {
                this.lastMessageId = this.messages[this.messages.length - 1].id;
            }
        },

        /**
         * Polling: запрос новых/изменённых/удалённых сообщений с сервера
         */
        async pollNewMessages() {
            if (!this.taskId) return;
            try {
                // Передаём список ID текущих сообщений для определения удалённых
                const currentIds = this.messages.map(m => m.id).join(',');
                const response = await fetch(BASE_URL + `/ajax/tasks/${this.taskId}/messages?after=${this.lastMessageId}&ids=${currentIds}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                const data = await response.json();

                // Новые сообщения
                if (data.messages && data.messages.length > 0) {
                    let hasNewFromOthers = false;
                    data.messages.forEach(msg => {
                        if (!this.messages.find(m => m.id === msg.id)) {
                            this.messages.push(msg);
                            if (msg.user_id != this.currentUserId) hasNewFromOthers = true;
                        }
                    });
                    this.updateLastMessageId();
                    this.$nextTick(() => this.scrollToBottom());
                    // Отмечаем новые чужие сообщения как прочитанные
                    this.markMessagesAsRead();
                    // Мигаем фавиконкой если вкладка в фоне и пришло чужое сообщение
                    if (hasNewFromOthers && document.visibilityState !== 'visible' && typeof FaviconBlinker !== 'undefined') {
                        FaviconBlinker.start();
                    }
                    // Звуковое уведомление при новых сообщениях от других
                    if (hasNewFromOthers && typeof NotificationSound !== 'undefined') {
                        NotificationSound.play();
                    }
                }

                // Удалённые сообщения
                if (data.deleted && data.deleted.length > 0) {
                    this.messages = this.messages.filter(m => !data.deleted.includes(m.id));
                }

                // Обновлённые сообщения (отредактированные или с замененными файлами)
                if (data.updated && data.updated.length > 0) {
                    data.updated.forEach(upd => {
                        const idx = this.messages.findIndex(m => m.id === upd.id);
                        if (idx !== -1) {
                            // Полная замена объекта для реактивности Alpine.js
                            const existing = this.messages[idx];
                            this.messages[idx] = {
                                ...existing,
                                comment_text: upd.comment_text,
                                files: upd.files && upd.files.length > 0
                                    ? upd.files.map(f => ({...f, updated: f.updated || Date.now()}))
                                    : (existing.files || []).map(f => ({...f, updated: Date.now()})),
                            };
                        }
                    });
                    // Пересоздаём массив для гарантии реактивности
                    this.messages = [...this.messages];
                }

                // Обновляем статус прочтения (галочки становятся синими)
                if (data.newly_read && data.newly_read.length > 0) {
                    data.newly_read.forEach(id => {
                        const idx = this.messages.findIndex(m => m.id === id);
                        if (idx !== -1) this.messages[idx].read_by_others = true;
                    });
                }

                // Отслеживаем смену статуса задачи
                if (data.task_status && data.task_status !== this.taskStatus) {
                    this.taskStatus = data.task_status;
                    // Звук при смене статуса
                    if (typeof NotificationSound !== 'undefined') {
                        NotificationSound.play();
                    }
                    // Обновляем бейдж статуса на странице (если есть)
                    const statusBadge = document.querySelector('[data-task-status]');
                    if (statusBadge) {
                        const statusLabels = {in_progress: 'В работе', revision: 'Доработки', done: 'Готово', closed: 'Сделано'};
                        statusBadge.textContent = statusLabels[data.task_status] || data.task_status;
                    }
                }
            } catch(e) {}
        },

        /**
         * Отправить сообщение (с файлом или без, с поддержкой ответа и редактирования)
         */
        async sendMessage() {
            const text = this.newMessage.trim();
            if (text === '' && !this.attachedFile) return;
            if (this.sending) return;

            // Если toggle «Редактировать» активен и прикреплено изображение — открываем редактор
            if (this.editBeforeSend && this.attachedFile && this.isImageFile(this.attachedFile)) {
                this.$dispatch('open-editor-local', { file: this.attachedFile, text: text });
                return;
            }

            this.sending = true;
            this.errorMessage = '';

            try {
                // Режим редактирования
                if (this.editingMsg) {
                    const formData = new FormData();
                    formData.append('comment_text', text);

                    const response = await fetch(BASE_URL + `/comments/${this.editingMsg.id}/edit`, {
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

                    if (data.success) {
                        // Обновляем текст в локальном массиве
                        const idx = this.messages.findIndex(m => m.id === this.editingMsg.id);
                        if (idx !== -1) {
                            this.messages[idx].comment_text = text;
                        }
                        this.newMessage = '';
                        this.editingMsg = null;

                        // Сбрасываем высоту textarea
                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.style.height = 'auto';
                        }
                    } else {
                        this.errorMessage = data.error || 'Ошибка при редактировании';
                    }
                    return;
                }

                // Режим создания нового сообщения (с возможным ответом)
                const formData = new FormData();
                formData.append('comment_text', text || '');

                // Если отвечаем на сообщение — передаём parent_comment_id
                if (this.replyTo) {
                    formData.append('parent_comment_id', this.replyTo.id);
                }

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
                        parent_comment_id: this.replyTo ? this.replyTo.id : null,
                        is_pinned: 0,
                        read_by_others: false,
                        files: data.comment.files || [],
                        links: data.comment.links || [],
                    });
                    this.newMessage = '';
                    this.replyTo = null;
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
         * Вставить эмодзи в textarea в позицию курсора
         */
        insertEmoji(emoji) {
            const input = this.$refs.messageInput;
            const start = input.selectionStart;
            const end = input.selectionEnd;
            this.newMessage = this.newMessage.substring(0, start) + emoji + this.newMessage.substring(end);
            this.$nextTick(() => {
                input.focus();
                input.selectionStart = input.selectionEnd = start + emoji.length;
            });
        },

        /**
         * Закрепить/открепить сообщение
         */
        async togglePin(msg) {
            try {
                const response = await fetch(BASE_URL + `/comments/${msg.id}/pin`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                if (data.success) {
                    const idx = this.messages.findIndex(m => m.id === msg.id);
                    if (idx !== -1) {
                        this.messages[idx].is_pinned = data.is_pinned;
                    }
                }
            } catch(e) {}
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
            this.editBeforeSend = false;
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
            el.style.height = Math.min(el.scrollHeight, 150) + 'px';
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
         * Проверка: является ли файл изображением (по расширению)
         */
        isImage(fileType) {
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes((fileType || '').toLowerCase());
        },

        /**
         * Проверка: является ли файл видео (по расширению)
         */
        isVideo(fileType) {
            return ['mp4', 'mov', 'webm'].includes((fileType || '').toLowerCase());
        },

        /**
         * Проверка: является ли прикреплённый File объект изображением (по имени/типу)
         */
        isImageFile(file) {
            if (!file) return false;
            if (file.type && file.type.startsWith('image/')) return true;
            const ext = (file.name || '').split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        },

        /**
         * Открыть модальное окно для просмотра изображения
         */
        openModal(file, ownerId) {
            this.modalFile = file;
            this.modalFileOwnerId = ownerId || null;
            this.modalZoom = 1;
        },
    };
}
</script>
