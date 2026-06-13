<?php
/**
 * Редактор изображений — views/components/image-editor.php
 *
 * Полноэкранный редактор на Canvas + Alpine.js.
 * Появляется при нажатии «Редактировать» в модалке просмотра изображения.
 * Связь с чатом через Alpine $dispatch('open-editor', {file: ...}).
 *
 * Инструменты:
 *   - Маркер (свободное рисование, палитра 6 цветов, 3 размера)
 *   - Комментарий (текстовый блок на цветном фоне, перетаскивание)
 *   - Перемещение (двигать комментарий после добавления)
 *   - Отмена (undo)
 */
?>

<!-- Редактор изображений -->
<div x-data="imageEditor()"
     @open-editor.window="openEditor($event.detail.file)"
     x-show="editorOpen"
     x-transition
     class="fixed inset-0 z-[150] bg-gray-900 flex"
     style="display: none;">

    <!-- Левая панель инструментов -->
    <div class="w-14 bg-blue-700 flex flex-col items-center py-4 gap-3">
        <!-- Маркер -->
        <button @click="setTool('marker')"
                :class="tool === 'marker' ? 'bg-blue-500' : 'hover:bg-blue-600'"
                class="w-10 h-10 rounded-lg flex items-center justify-center text-white transition"
                title="Маркер">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
        </button>

        <!-- Комментарий -->
        <button @click="setTool('comment')"
                :class="tool === 'comment' ? 'bg-blue-500' : 'hover:bg-blue-600'"
                class="w-10 h-10 rounded-lg flex items-center justify-center text-white transition"
                title="Комментарий">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
        </button>

        <!-- Перемещение (активно когда есть комментарий для перетаскивания) -->
        <button @click="setTool('move')"
                :class="tool === 'move' ? 'bg-blue-500' : 'hover:bg-blue-600'"
                :disabled="!commentText"
                class="w-10 h-10 rounded-lg flex items-center justify-center text-white transition disabled:opacity-30"
                title="Переместить комментарий">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
        </button>

        <!-- Отмена (undo) -->
        <button @click="undo()"
                class="w-10 h-10 rounded-lg flex items-center justify-center text-white hover:bg-blue-600 transition"
                title="Отмена">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
        </button>

        <div class="border-t border-blue-500 w-8 my-2"></div>

        <!-- Доп.панель для маркера: цвет + размер -->
        <template x-if="tool === 'marker'">
            <div class="space-y-2 flex flex-col items-center">
                <template x-for="c in colors" :key="c">
                    <button @click="color = c"
                            :class="color === c ? 'ring-2 ring-white ring-offset-1 ring-offset-blue-700' : ''"
                            :style="'background-color: ' + c"
                            class="w-6 h-6 rounded-full"></button>
                </template>
                <div class="border-t border-blue-500 w-8 my-2"></div>
                <!-- Размер линии -->
                <button @click="lineWidth = 3"
                        :class="lineWidth === 3 ? 'bg-blue-500' : ''"
                        class="w-8 h-8 rounded flex items-center justify-center">
                    <div class="w-3 h-0.5 bg-white rounded"></div>
                </button>
                <button @click="lineWidth = 6"
                        :class="lineWidth === 6 ? 'bg-blue-500' : ''"
                        class="w-8 h-8 rounded flex items-center justify-center">
                    <div class="w-4 h-1 bg-white rounded"></div>
                </button>
                <button @click="lineWidth = 12"
                        :class="lineWidth === 12 ? 'bg-blue-500' : ''"
                        class="w-8 h-8 rounded flex items-center justify-center">
                    <div class="w-5 h-1.5 bg-white rounded"></div>
                </button>
            </div>
        </template>

        <!-- Доп.панель для комментария: цвет фона + цвет текста -->
        <template x-if="tool === 'comment' || tool === 'move'">
            <div class="space-y-2 flex flex-col items-center">
                <span class="text-[10px] text-blue-200">Фон</span>
                <template x-for="c in commentColors" :key="'cc-'+c">
                    <button @click="commentBg = c"
                            :class="commentBg === c ? 'ring-2 ring-white ring-offset-1 ring-offset-blue-700' : ''"
                            :style="'background-color: ' + c"
                            class="w-6 h-6 rounded-full border border-blue-400"></button>
                </template>
                <div class="border-t border-blue-500 w-8 my-2"></div>
                <span class="text-[10px] text-blue-200">Текст</span>
                <template x-for="c in textColors" :key="'tc-'+c">
                    <button @click="commentTextColor = c"
                            :class="commentTextColor === c ? 'ring-2 ring-white ring-offset-1 ring-offset-blue-700' : ''"
                            :style="'background-color: ' + c"
                            class="w-6 h-6 rounded-full border border-blue-400"></button>
                </template>
                <div class="border-t border-blue-500 w-8 my-2"></div>
                <span class="text-[10px] text-blue-200">Размер</span>
                <button @click="fontSize = 12" :class="fontSize === 12 ? 'bg-blue-500' : ''" class="w-8 h-8 rounded flex items-center justify-center text-white text-[10px]">S</button>
                <button @click="fontSize = 16" :class="fontSize === 16 ? 'bg-blue-500' : ''" class="w-8 h-8 rounded flex items-center justify-center text-white text-xs">M</button>
                <button @click="fontSize = 22" :class="fontSize === 22 ? 'bg-blue-500' : ''" class="w-8 h-8 rounded flex items-center justify-center text-white text-sm">L</button>
            </div>
        </template>
    </div>

    <!-- Центр: Canvas + панели -->
    <div class="flex-1 flex flex-col">
        <!-- Верхняя панель -->
        <div class="bg-gray-800 px-4 py-2 flex items-center justify-between">
            <span class="text-white text-sm">Редактирование изображения</span>
            <div class="flex gap-2">
                <button @click="saveImage()"
                        class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">
                    Сохранить
                </button>
                <button @click="closeEditor()"
                        class="px-4 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition">
                    Отмена
                </button>
            </div>
        </div>

        <!-- Canvas область -->
        <div class="flex-1 flex items-center justify-center bg-gray-800 p-4 overflow-auto" x-ref="canvasContainer">
            <canvas x-ref="editorCanvas"
                    @mousedown.prevent="startDraw($event)"
                    @mousemove.prevent="draw($event)"
                    @mouseup="endDraw()"
                    @mouseleave="endDraw()"
                    @touchstart.prevent="startDrawTouch($event)"
                    @touchmove.prevent="drawTouch($event)"
                    @touchend="endDraw()"
                    class="max-w-full max-h-full cursor-crosshair shadow-lg border border-white/30"></canvas>
        </div>

        <!-- Поле ввода комментария (для инструмента Comment) -->
        <div x-show="tool === 'comment' && !commentText"
             class="bg-gray-800 px-4 py-3 border-t border-gray-700">
            <div class="flex gap-2 max-w-lg mx-auto">
                <input type="text" x-model="commentInput" x-ref="commentTextInput"
                       @keydown.enter="addComment()"
                       placeholder="Введите текст комментария..."
                       class="flex-1 px-3 py-2 rounded-lg bg-gray-700 text-white text-sm border-0 focus:ring-2 focus:ring-blue-500 outline-none placeholder-gray-400">
                <button @click="addComment()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    Добавить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function imageEditor() {
    return {
        editorOpen: false,
        editingFile: null,
        tool: 'marker',
        color: '#EF4444',
        lineWidth: 4,
        colors: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#000000'],
        commentColors: ['#EF4444', '#34D399', '#60A5FA', '#F87171', '#A78BFA', '#FFFFFF'],
        commentBg: '#EF4444',
        commentTextColor: '#FFFFFF',
        textColors: ['#FFFFFF', '#000000', '#EF4444', '#3B82F6'],
        fontSize: 14,
        commentInput: '',
        commentText: null,
        commentPos: null,
        dragging: false,
        drawing: false,
        history: [],
        ctx: null,
        img: null,

        /**
         * Открыть редактор с переданным файлом
         */
        openEditor(file) {
            if (!file) return;
            this.editingFile = file;
            this.editorOpen = true;
            this.history = [];
            this.commentText = null;
            this.commentInput = '';
            this.tool = 'marker';

            this.$nextTick(() => {
                const canvas = this.$refs.editorCanvas;
                this.ctx = canvas.getContext('2d');
                this.img = new Image();
                this.img.crossOrigin = 'anonymous';
                this.img.onload = () => {
                    // Масштабируем canvas под размер экрана
                    const maxW = Math.min(this.img.width, window.innerWidth - 100);
                    const maxH = Math.min(this.img.height, window.innerHeight - 150);
                    const scale = Math.min(maxW / this.img.width, maxH / this.img.height, 1);
                    canvas.width = this.img.width * scale;
                    canvas.height = this.img.height * scale;
                    this.ctx.drawImage(this.img, 0, 0, canvas.width, canvas.height);
                    this.saveState();
                };
                this.img.src = BASE_URL + '/files/' + file.id + '/download';
            });
        },

        /**
         * Закрыть редактор
         */
        closeEditor() {
            this.editorOpen = false;
            this.editingFile = null;
        },

        /**
         * Установить активный инструмент
         */
        setTool(t) {
            this.tool = t;
            // При переключении на маркер — сбрасываем комментарий
            if (t === 'marker') {
                this.commentText = null;
            }
            if (t === 'comment') {
                this.$nextTick(() => {
                    if (this.$refs.commentTextInput) {
                        this.$refs.commentTextInput.focus();
                    }
                });
            }
        },

        /**
         * Сохранить текущее состояние canvas в историю (для undo)
         */
        saveState() {
            try {
                const canvas = this.$refs.editorCanvas;
                this.history.push(canvas.toDataURL());
            } catch(e) {
                console.error('saveState error (tainted canvas?):', e);
            }
        },

        /**
         * Отменить последнее действие
         */
        undo() {
            if (this.history.length <= 1) return;
            this.history.pop();
            const lastState = this.history[this.history.length - 1];
            const img = new Image();
            img.onload = () => {
                const canvas = this.$refs.editorCanvas;
                this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                this.ctx.drawImage(img, 0, 0);
            };
            img.src = lastState;
        },

        /**
         * Получить координаты курсора относительно canvas
         */
        getPos(e) {
            const canvas = this.$refs.editorCanvas;
            const rect = canvas.getBoundingClientRect();
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        },

        /**
         * Начало рисования / перетаскивания комментария
         */
        startDraw(e) {
            // Перемещение комментария (если текст уже добавлен)
            if ((this.tool === 'comment' || this.tool === 'move') && this.commentText) {
                this.dragging = true;
                this.commentPos = this.getPos(e);
                return;
            }
            if (this.tool !== 'marker') return;
            this.drawing = true;
            const pos = this.getPos(e);
            this.ctx.beginPath();
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.lineWidth;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.moveTo(pos.x, pos.y);
            // Рисуем точку сразу при клике
            this.ctx.lineTo(pos.x + 0.1, pos.y + 0.1);
            this.ctx.stroke();
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        },

        /**
         * Процесс рисования / перетаскивания
         */
        draw(e) {
            // Перетаскивание комментария
            if ((this.tool === 'comment' || this.tool === 'move') && this.dragging && this.commentText) {
                const pos = this.getPos(e);
                this.commentPos = pos;
                const lastState = this.history[this.history.length - 1];
                const img = new Image();
                img.onload = () => {
                    const canvas = this.$refs.editorCanvas;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.ctx.drawImage(img, 0, 0);
                    this.drawCommentBlock(pos.x, pos.y);
                };
                img.src = lastState;
                return;
            }
            if (!this.drawing || this.tool !== 'marker') return;
            const pos = this.getPos(e);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        },

        /**
         * Конец рисования / перетаскивания
         */
        endDraw() {
            if (this.dragging && this.commentText) {
                this.dragging = false;
                this.saveState();
                this.commentText = null;
                return;
            }
            if (this.drawing) {
                this.drawing = false;
                this.saveState();
            }
        },

        /**
         * Начало рисования (touch)
         */
        startDrawTouch(e) {
            const touch = e.touches[0];
            this.startDraw({ clientX: touch.clientX, clientY: touch.clientY });
        },

        /**
         * Процесс рисования (touch)
         */
        drawTouch(e) {
            const touch = e.touches[0];
            this.draw({ clientX: touch.clientX, clientY: touch.clientY });
        },

        /**
         * Добавить текстовый комментарий на canvas
         */
        addComment() {
            if (!this.commentInput.trim()) return;
            this.commentText = this.commentInput.trim();
            this.commentInput = '';
            // НЕ рисуем сразу — только сохраняем текст и переключаем на перемещение
            // Комментарий будет нарисован при первом клике/перетаскивании
            this.tool = 'move';
        },

        /**
         * Нарисовать блок комментария на canvas
         */
        drawCommentBlock(x, y) {
            const text = this.commentText;
            if (!text) return;
            this.ctx.font = this.fontSize + 'px sans-serif';
            const metrics = this.ctx.measureText(text);
            const padding = 8;
            const w = metrics.width + padding * 2;
            const h = this.fontSize + padding * 2;

            // Фон блока
            this.ctx.fillStyle = this.commentBg;
            this.ctx.beginPath();
            this.ctx.roundRect(x, y - h / 2, w, h, 6);
            this.ctx.fill();

            // Текст — используем выбранный цвет текста
            this.ctx.fillStyle = this.commentTextColor;
            this.ctx.textBaseline = 'middle';
            this.ctx.fillText(text, x + padding, y);
        },

        /**
         * Сохранить отредактированное изображение (замена оригинального файла)
         */
        async saveImage() {
            const canvas = this.$refs.editorCanvas;
            canvas.toBlob(async (blob) => {
                if (!blob) return;

                const formData = new FormData();
                formData.append('file', blob);

                try {
                    const response = await fetch(BASE_URL + `/files/${this.editingFile.id}/replace`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.closeEditor();
                        window.location.reload();
                    } else {
                        alert('Ошибка: ' + (data.error || 'не удалось сохранить'));
                    }
                } catch (e) {
                    alert('Ошибка сохранения');
                }
            }, 'image/png');
        }
    };
}
</script>
