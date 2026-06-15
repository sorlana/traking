<?php
/**
 * Единый дашборд — views/dashboard/unified.php
 *
 * Канбан-доска с вкладками проектов для ролей Manager и Executor.
 * Использует Alpine.js для переключения проектов без перезагрузки страницы.
 *
 * Переменные из контроллера:
 * - $projects  — массив проектов пользователя
 * - $boardData — задачи, сгруппированные по проектам и статусам
 * - $roleId    — роль пользователя (2 = Manager, 3 = Executor)
 */
$layout = 'layouts/app';
?>

<div x-data="dashboard" class="space-y-4">

    <!-- Сообщение при отсутствии проектов -->
    <template x-if="projects.length === 0">
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <p class="text-sm text-gray-400">Нет проектов</p>
        </div>
    </template>

    <template x-if="projects.length > 0">
        <div class="space-y-4">

            <!-- Project_Tabs: горизонтальная панель вкладок -->
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="overflow-x-auto">
                    <div class="flex items-center gap-4 p-3 min-w-max">
                        <template x-for="project in projects" :key="project.id">
                            <button
                                x-on:click="selectProject(project.id)"
                                :class="activeProjectId === project.id
                                    ? 'text-blue-600 font-semibold'
                                    : 'text-gray-600 hover:text-blue-600'"
                                class="text-sm whitespace-nowrap transition-colors"
                                x-text="project.title"
                            ></button>
                        </template>
                        <!-- Иконка Инфо (только мобильный) -->
                        <button @click="showInfoModal = true" class="md:hidden ml-auto flex-shrink-0 p-1 text-gray-400 hover:text-blue-600 transition" title="Статистика">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Модалка со статистикой (мобильная) -->
            <div x-show="showInfoModal"
                 @click.self="showInfoModal = false"
                 class="md:hidden fixed inset-0 z-[80] bg-black/50 flex items-end"
                 style="display: none;"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="bg-white w-full rounded-t-2xl p-4 pb-8 space-y-3 max-h-[70vh] overflow-y-auto transform transition-transform duration-200"
                     :class="showInfoModal ? 'translate-y-0' : 'translate-y-full'"
                     @click.stop>
                    <!-- Заголовок модалки -->
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700">Статистика проекта</h3>
                        <button @click="showInfoModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <!-- Три карточки -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-white rounded-lg border border-t-4 border-t-amber-400 p-2">
                            <div class="text-xs text-gray-500 mb-1">В работе</div>
                            <div class="text-lg font-bold text-gray-800" x-text="currentStats.in_progress"></div>
                            <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                        </div>
                        <div class="bg-white rounded-lg border border-t-4 border-t-orange-500 p-2">
                            <div class="text-xs text-gray-500 mb-1">Доработки</div>
                            <div class="text-lg font-bold text-gray-800" x-text="currentStats.revision"></div>
                            <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                        </div>
                        <div class="bg-white rounded-lg border border-t-4 border-t-green-500 p-2">
                            <div class="text-xs text-gray-500 mb-1">Готово</div>
                            <div class="text-lg font-bold text-gray-800" x-text="currentStats.done"></div>
                            <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                        </div>
                    </div>
                    <!-- Прогресс -->
                    <div class="bg-white rounded-lg border p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Прогресс</span>
                            <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden flex">
                                <div class="h-full bg-green-500" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-orange-400" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-amber-400" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700" x-text="(currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></span>
                        </div>
                    </div>
                    <!-- План/Факт -->
                    <template x-if="currentTimeData.estimated > 0 || currentTimeData.actual > 0">
                        <div class="bg-white rounded-lg border p-3 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 w-10">План</span>
                                <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                    <div class="h-full bg-blue-400 rounded" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.estimated / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-700 w-12 text-right" x-text="currentTimeData.estimated + ' ч'"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 w-10">Факт</span>
                                <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                    <div class="h-full rounded" :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'bg-red-400' : 'bg-green-400'" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.actual / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                </div>
                                <span class="text-xs font-medium w-12 text-right" :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'text-red-600' : 'text-green-600'" x-text="currentTimeData.actual + ' ч'"></span>
                            </div>
                            <template x-if="currentTimeData.manager > 0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 w-10">Рук.</span>
                                    <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                        <div class="h-full rounded bg-purple-400" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.manager / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                    </div>
                                    <span class="text-xs font-medium text-purple-600 w-12 text-right" x-text="currentTimeData.manager + ' ч'"></span>
                                </div>
                            </template>
                            <template x-if="currentTimeData.estimated > 0">
                                <div class="text-center pt-1 border-t">
                                    <span class="text-xs font-bold" :class="currentTimeData.actual > currentTimeData.estimated ? 'text-red-600' : 'text-green-600'" x-text="Math.round(currentTimeData.actual / currentTimeData.estimated * 100) + '%'"></span>
                                    <span class="text-xs text-gray-400"> от плана</span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Stats_Panel: только десктоп -->
            <div class="hidden md:grid grid-cols-4 gap-4">
                <!-- В работе — жёлтая/amber рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-amber-400 p-2 md:p-4">
                    <div class="text-xs text-gray-500 mb-1">В работе</div>
                    <div class="text-lg md:text-2xl font-bold text-gray-800" x-text="currentStats.in_progress"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                </div>
                <!-- Доработки — оранжевая рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-orange-500 p-2 md:p-4">
                    <div class="text-xs text-gray-500 mb-1">Доработки</div>
                    <div class="text-lg md:text-2xl font-bold text-gray-800" x-text="currentStats.revision"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                </div>
                <!-- Готово — зелёная рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-green-500 p-2 md:p-4">
                    <div class="text-xs text-gray-500 mb-1">Готово</div>
                    <div class="text-lg md:text-2xl font-bold text-gray-800" x-text="currentStats.done"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                </div>
                <!-- 4-й столбец: Прогресс + Время -->
                <div class="space-y-2">
                    <!-- Прогресс-бар -->
                    <div class="bg-white rounded-lg shadow-sm border p-2 md:p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Прогресс</span>
                            <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden flex">
                                <div class="h-full bg-green-500 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-orange-400 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-amber-400 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700" x-text="(currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></span>
                        </div>
                    </div>
                    <!-- Время: план vs факт (компактный вид) -->
                    <template x-if="currentTimeData.estimated > 0 || currentTimeData.actual > 0">
                        <div class="bg-white rounded-lg shadow-sm border p-2 md:p-3">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 w-10">План</span>
                                    <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                        <div class="h-full bg-blue-400 rounded transition-all duration-300"
                                             :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.estimated / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700 w-12 text-right" x-text="currentTimeData.estimated + ' ч'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 w-10">Факт</span>
                                    <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                        <div class="h-full rounded transition-all duration-300"
                                             :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'bg-red-400' : 'bg-green-400'"
                                             :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.actual / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                    </div>
                                    <span class="text-xs font-medium w-12 text-right"
                                          :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'text-red-600' : 'text-green-600'"
                                          x-text="currentTimeData.actual + ' ч'"></span>
                                </div>
                                <template x-if="currentTimeData.estimated > 0">
                                    <div class="text-center pt-1 border-t">
                                        <span class="text-xs font-bold"
                                              :class="currentTimeData.actual > currentTimeData.estimated ? 'text-red-600' : 'text-green-600'"
                                              x-text="Math.round(currentTimeData.actual / currentTimeData.estimated * 100) + '%'"></span>
                                        <span class="text-xs text-gray-400"> от плана</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Task_Board: десктоп — три колонки, мобильный — вкладки -->

            <!-- Мобильные вкладки (md:hidden) -->
            <div class="md:hidden" x-data="{ boardTab: 'in_progress' }">
                <!-- Переключатель вкладок -->
                <div class="flex border-b mb-3">
                    <button @click="boardTab = 'in_progress'"
                            :class="boardTab === 'in_progress' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                            class="flex-1 py-2.5 text-sm font-medium text-center transition">
                        В работе <span class="text-xs opacity-70" x-text="'(' + currentBoard.in_progress.length + ')'"></span>
                    </button>
                    <button @click="boardTab = 'revision'"
                            :class="boardTab === 'revision' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                            class="flex-1 py-2.5 text-sm font-medium text-center transition">
                        Доработки <span class="text-xs opacity-70" x-text="'(' + currentBoard.revision.length + ')'"></span>
                    </button>
                    <button @click="boardTab = 'done'"
                            :class="boardTab === 'done' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                            class="flex-1 py-2.5 text-sm font-medium text-center transition">
                        Готово <span class="text-xs opacity-70" x-text="'(' + currentBoard.done.length + ')'"></span>
                    </button>
                </div>

                <!-- Содержимое вкладок -->
                <div class="space-y-2">
                    <template x-for="task in (boardTab === 'in_progress' ? currentBoard.in_progress : boardTab === 'revision' ? currentBoard.revision : currentBoard.done)" :key="task.id">
                        <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                            <div class="flex items-start gap-2">
                                <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                                      :class="{
                                          'bg-red-500': task.priority === 'urgent',
                                          'bg-orange-500': task.priority === 'high',
                                          'bg-yellow-400': task.priority === 'medium',
                                          'bg-green-500': task.priority === 'low'
                                      }"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate" x-text="task.title"></div>
                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                        <template x-if="task.deadline">
                                            <span class="text-xs text-gray-500" x-text="task.deadline"></span>
                                        </template>
                                        <span class="text-xs text-gray-400" x-text="task.assigned_name || 'Не назначен'"></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </template>
                    <template x-if="(boardTab === 'in_progress' ? currentBoard.in_progress : boardTab === 'revision' ? currentBoard.revision : currentBoard.done).length === 0">
                        <p class="text-sm text-gray-400 text-center py-4">Нет задач</p>
                    </template>
                </div>
            </div>

            <!-- Десктоп: три колонки (hidden на мобильном) -->
            <div class="hidden md:flex gap-4">

                <!-- Колонка «В работе» -->
                <div class="flex-1 min-w-0">
                    <div class="bg-amber-50 rounded-lg p-3">
                        <h3 class="text-sm font-medium text-amber-800 mb-3">В работе</h3>
                        <div class="space-y-2">
                            <template x-for="task in currentBoard.in_progress" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                                              :class="{
                                                  'bg-red-500': task.priority === 'urgent',
                                                  'bg-orange-500': task.priority === 'high',
                                                  'bg-yellow-400': task.priority === 'medium',
                                                  'bg-green-500': task.priority === 'low'
                                              }"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="task.title"></div>
                                            <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                <template x-if="task.deadline">
                                                    <span class="text-xs text-gray-500" x-text="task.deadline"></span>
                                                </template>
                                                <span class="text-xs text-gray-400" x-text="task.assigned_name || 'Не назначен'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Колонка «Доработки» -->
                <div class="flex-1 min-w-0">
                    <div class="bg-orange-50 rounded-lg p-3">
                        <h3 class="text-sm font-medium text-orange-800 mb-3">Доработки</h3>
                        <div class="space-y-2">
                            <template x-for="task in currentBoard.revision" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                                              :class="{
                                                  'bg-red-500': task.priority === 'urgent',
                                                  'bg-orange-500': task.priority === 'high',
                                                  'bg-yellow-400': task.priority === 'medium',
                                                  'bg-green-500': task.priority === 'low'
                                              }"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="task.title"></div>
                                            <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                <template x-if="task.deadline">
                                                    <span class="text-xs text-gray-500" x-text="task.deadline"></span>
                                                </template>
                                                <span class="text-xs text-gray-400" x-text="task.assigned_name || 'Не назначен'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Колонка «Готово» -->
                <div class="flex-1 min-w-0">
                    <div class="bg-green-50 rounded-lg p-3">
                        <h3 class="text-sm font-medium text-green-800 mb-3">Готово</h3>
                        <div class="space-y-2">
                            <template x-for="task in currentBoard.done" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0"
                                              :class="{
                                                  'bg-red-500': task.priority === 'urgent',
                                                  'bg-orange-500': task.priority === 'high',
                                                  'bg-yellow-400': task.priority === 'medium',
                                                  'bg-green-500': task.priority === 'low'
                                              }"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="task.title"></div>
                                            <div class="mt-1 flex items-center gap-2 flex-wrap">
                                                <template x-if="task.deadline">
                                                    <span class="text-xs text-gray-500" x-text="task.deadline"></span>
                                                </template>
                                                <span class="text-xs text-gray-400" x-text="task.assigned_name || 'Не назначен'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>

</div>

<!-- Alpine.js data-компонент dashboard -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboard', () => ({
        projects: <?= json_encode($projects ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        boardData: <?= json_encode($boardData ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        timeData: <?= json_encode($timeData ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        activeProjectId: null,
        showInfoModal: false,

        init() {
            if (this.projects.length > 0) {
                this.activeProjectId = this.projects[0].id;
            }
        },

        get currentBoard() {
            return this.boardData[this.activeProjectId] || { in_progress: [], revision: [], done: [] };
        },

        get currentStats() {
            const board = this.currentBoard;
            const inProgress = board.in_progress.length;
            const revision = board.revision.length;
            const done = board.done.length;
            return {
                in_progress: inProgress,
                revision: revision,
                done: done,
                total: inProgress + revision + done,
            };
        },

        get currentTimeData() {
            const data = this.timeData[this.activeProjectId] || { estimated: 0, actual: 0, manager: 0 };
            const maxHours = Math.max(data.estimated, data.actual + data.manager, 1);
            return { ...data, maxHours, total: data.actual + data.manager };
        },

        selectProject(projectId) {
            this.activeProjectId = projectId;
        }
    }));
});
</script>
