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
                    <div class="flex gap-1 p-2 min-w-max">
                        <template x-for="project in projects" :key="project.id">
                            <button
                                x-on:click="selectProject(project.id)"
                                :class="activeProjectId === project.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap transition-colors"
                                x-text="project.title"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Stats_Panel: три карточки статистики -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            return {
                in_progress: board.in_progress.length,
                revision: board.revision.length,
                done: board.done.length,
            };
        },

        selectProject(projectId) {
            this.activeProjectId = projectId;
        }
    }));
});
</script>
