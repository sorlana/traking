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
                    <div class="flex gap-4 p-3 min-w-max">
                        <template x-for="project in projects" :key="project.id">
                            <button
                                x-on:click="selectProject(project.id)"
                                :class="activeProjectId === project.id
                                    ? 'text-blue-600 font-semibold border-b-2 border-blue-600'
                                    : 'text-gray-600 hover:text-blue-600'"
                                class="pb-1 text-sm whitespace-nowrap transition-colors"
                                x-text="project.title"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Stats_Panel: три карточки статистики -->
            <!-- Stats_Panel: три карточки статистики + диаграмма прогресса -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- В работе — жёлтая/amber рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-amber-400 p-4">
                    <div class="text-xs text-gray-500 mb-1">В работе</div>
                    <div class="text-2xl font-bold text-gray-800" x-text="currentStats.in_progress"></div>
                </div>
                <!-- Доработки — оранжевая рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-orange-500 p-4">
                    <div class="text-xs text-gray-500 mb-1">Доработки</div>
                    <div class="text-2xl font-bold text-gray-800" x-text="currentStats.revision"></div>
                </div>
                <!-- Готово — зелёная рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-green-500 p-4">
                    <div class="text-xs text-gray-500 mb-1">Готово</div>
                    <div class="text-2xl font-bold text-gray-800" x-text="currentStats.done"></div>
                </div>
                <!-- Прогресс — сводная диаграмма -->
                <div class="bg-white rounded-lg shadow-sm border p-4 flex flex-col justify-center">
                    <div class="text-xs text-gray-500 mb-2">Прогресс</div>
                    <div class="flex items-center gap-2">
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
            </div>

            <!-- Task_Board: три колонки с карточками задач -->
            <div class="flex flex-col md:flex-row gap-4">

                <!-- Колонка «В работе» -->
                <div class="flex-1 min-w-0">
                    <div class="bg-amber-50 rounded-lg p-3">
                        <h3 class="text-sm font-medium text-amber-800 mb-3">В работе</h3>
                        <div class="space-y-2">
                            <template x-for="task in currentBoard.in_progress" :key="task.id">
                                <!-- Task_Card -->
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <!-- Индикатор приоритета -->
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
                                <!-- Task_Card -->
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
                                <!-- Task_Card -->
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
        activeProjectId: null,

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

        selectProject(projectId) {
            this.activeProjectId = projectId;
        }
    }));
});
</script>
