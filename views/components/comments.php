<?php
/**
 * Компонент комментариев — views/components/comments.php
 *
 * Включается из карточки задачи (show.php).
 * Использует Alpine.js для отправки комментариев без перезагрузки.
 *
 * Ожидаемые переменные:
 *   $task — массив данных задачи
 *   $comments — массив комментариев (с user_name, created_at, comment_text)
 */

$currentUserId = \Helpers\Auth::id();
$currentUser = \Helpers\Auth::user();
$roleId = (int) ($currentUser['role_id'] ?? 0);
?>

<div class="bg-white rounded-lg shadow-sm border p-5" x-data="commentsComponent()">
    <h3 class="text-sm font-medium text-gray-500 mb-4">
        Комментарии (<span x-text="comments.length"><?= count($comments) ?></span>)
    </h3>

    <!-- Список комментариев -->
    <div class="space-y-4" x-show="comments.length > 0">
        <template x-for="comment in comments" :key="comment.id">
            <div class="border-l-2 border-gray-200 pl-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-gray-800" x-text="comment.user_name"></span>
                        <span class="text-xs text-gray-400" x-text="formatDate(comment.created_at)"></span>
                    </div>
                    <!-- Кнопки редактирования/удаления для своих комментариев -->
                    <div class="flex gap-2" x-show="comment.user_id == currentUserId || isAdmin">
                        <button @click="startEdit(comment)"
                                class="text-xs text-blue-500 hover:text-blue-700"
                                x-show="!comment.editing">
                            Редактировать
                        </button>
                        <button @click="deleteComment(comment.id)"
                                class="text-xs text-red-500 hover:text-red-700"
                                x-show="!comment.editing">
                            Удалить
                        </button>
                    </div>
                </div>

                <!-- Режим просмотра -->
                <p class="text-sm text-gray-700 whitespace-pre-wrap"
                   x-show="!comment.editing"
                   x-text="comment.comment_text"></p>

                <!-- Режим редактирования -->
                <div x-show="comment.editing" class="mt-1">
                    <textarea x-model="comment.editText"
                              class="ui-control"
                              rows="3"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button @click="saveEdit(comment)"
                                class="ui-btn ui-btn-primary">
                            Сохранить
                        </button>
                        <button @click="cancelEdit(comment)"
                                class="ui-btn ui-btn-secondary">
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Сообщение если нет комментариев -->
    <p class="text-sm text-gray-400" x-show="comments.length === 0">Комментариев пока нет</p>

    <!-- Форма добавления комментария -->
    <div class="mt-4 pt-4 border-t">
        <form @submit.prevent="addComment()">
            <textarea x-model="newComment"
                      placeholder="Написать комментарий..."
                      class="ui-control resize-y"
                      rows="3"
                      maxlength="5000"></textarea>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400" x-show="errorMessage" x-text="errorMessage"
                      class="text-xs text-red-500"></span>
                <button type="submit"
                        :disabled="sending || newComment.trim() === ''"
                        class="ui-btn ui-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!sending">Отправить</span>
                    <span x-show="sending">Отправка...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function commentsComponent() {
    return {
        comments: <?= json_encode(array_map(function($c) {
            return [
                'id' => (int) $c['id'],
                'task_id' => (int) $c['task_id'],
                'user_id' => (int) $c['user_id'],
                'comment_text' => $c['comment_text'],
                'user_name' => $c['user_name'] ?? $c['user_login'] ?? '',
                'created_at' => $c['created_at'],
                'editing' => false,
                'editText' => '',
            ];
        }, $comments), JSON_UNESCAPED_UNICODE) ?>,
        newComment: '',
        sending: false,
        errorMessage: '',
        currentUserId: <?= (int) $currentUserId ?>,
        isAdmin: <?= $roleId === 1 ? 'true' : 'false' ?>,
        taskId: <?= (int) $task['id'] ?>,

        /**
         * Добавить новый комментарий через AJAX
         */
        async addComment() {
            if (this.newComment.trim() === '' || this.sending) return;

            this.sending = true;
            this.errorMessage = '';

            try {
                const formData = new FormData();
                formData.append('comment_text', this.newComment.trim());

                const response = await fetch(BASE_URL + `/tasks/${this.taskId}/comments`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const text = await response.text();
                let data;
                try { data = JSON.parse(text); } catch(parseErr) {
                    this.errorMessage = 'Ошибка сервера: ' + text.substring(0, 200);
                    return;
                }

                if (data.success && data.comment) {
                    this.comments.push({
                        id: parseInt(data.comment.id),
                        task_id: this.taskId,
                        user_id: this.currentUserId,
                        comment_text: data.comment.comment_text,
                        user_name: data.comment.user_name,
                        created_at: data.comment.created_at,
                        editing: false,
                        editText: '',
                    });
                    this.newComment = '';
                } else {
                    this.errorMessage = data.error || 'Ошибка при добавлении комментария';
                }
            } catch (e) {
                this.errorMessage = 'Ошибка сети. Попробуйте ещё раз.';
            } finally {
                this.sending = false;
            }
        },

        /**
         * Начать редактирование комментария
         */
        startEdit(comment) {
            comment.editing = true;
            comment.editText = comment.comment_text;
        },

        /**
         * Отменить редактирование
         */
        cancelEdit(comment) {
            comment.editing = false;
            comment.editText = '';
        },

        /**
         * Сохранить отредактированный комментарий
         */
        async saveEdit(comment) {
            if (comment.editText.trim() === '') return;

            try {
                const formData = new FormData();
                formData.append('comment_text', comment.editText.trim());

                const response = await fetch(BASE_URL + `/comments/${comment.id}/edit`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    comment.comment_text = comment.editText.trim();
                    comment.editing = false;
                    comment.editText = '';
                } else {
                    alert(data.error || 'Ошибка при редактировании');
                }
            } catch (e) {
                alert('Ошибка сети. Попробуйте ещё раз.');
            }
        },

        /**
         * Удалить комментарий
         */
        async deleteComment(commentId) {
            if (!confirm('Удалить комментарий?')) return;

            try {
                const formData = new FormData();

                const response = await fetch(BASE_URL + `/comments/${commentId}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    this.comments = this.comments.filter(c => c.id !== commentId);
                } else {
                    alert(data.error || 'Ошибка при удалении');
                }
            } catch (e) {
                alert('Ошибка сети. Попробуйте ещё раз.');
            }
        },

        /**
         * Форматирование даты для отображения
         */
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const pad = (n) => n.toString().padStart(2, '0');
            return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }
    };
}
</script>
