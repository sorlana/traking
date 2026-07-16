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

<style>
/* Дашборд: убираем общую прокрутку страницы */
@media (min-width: 768px) {
    body { height: 100vh; overflow: hidden; }
    main { overflow: hidden !important; height: calc(100vh - 3.5rem) !important; padding-bottom: 0 !important; display: flex !important; flex-direction: column !important; }
}
@media (max-width: 767px) {
    .dashboard-fixed-header {
        position: fixed;
        top: 3.5rem;
        left: 0;
        right: 0;
        z-index: 40;
        background: white;
        border-bottom: 1px solid #e5e7eb;
    }
    .dashboard-fixed-header .rounded-lg {
        border-radius: 0 !important;
        border-left: 0 !important;
        border-right: 0 !important;
        border-top: 0 !important;
    }
}
</style>

<script>
// Динамический расчёт padding-top для мобильного контента дашборда
function adjustDashboardPadding() {
    if (window.innerWidth >= 768) return;
    const header = document.querySelector('.dashboard-fixed-header');
    const content = document.querySelector('.dashboard-mobile-content');
    if (header && content) {
        content.style.paddingTop = header.offsetHeight + 8 + 'px';
    }
}
window.addEventListener('load', adjustDashboardPadding);
window.addEventListener('resize', adjustDashboardPadding);
setTimeout(adjustDashboardPadding, 100);
setTimeout(adjustDashboardPadding, 500);
</script>
</style>

<div x-data="dashboard" class="flex flex-col flex-1 min-h-0 md:overflow-hidden pb-4">

    <!-- Сообщение при отсутствии проектов -->
    <div x-show="projects.length === 0" class="bg-white rounded-lg shadow-sm border p-8 text-center">
        <p class="text-sm text-gray-400">Нет проектов</p>
    </div>

    <div x-show="projects.length > 0" x-cloak class="flex flex-col flex-1 min-h-0 gap-3">

            <!-- ФИКСИРОВАННАЯ ЧАСТЬ: вкладки проектов + статистика -->
            <div class="flex-shrink-0 space-y-3">

            <!-- На мобильном: плашка + переключатель в одном fixed-блоке -->
            <div class="dashboard-fixed-header">

            <!-- Project_Tabs: горизонтальная панель вкладок -->
            <div class="bg-white rounded-lg shadow-sm border md:rounded-lg md:shadow-sm md:border">
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
                        <!-- Иконка Дерева задач (только мобильный) -->
                        <button @click="showTreeModal = true; loadTree()" class="md:hidden ml-auto flex-shrink-0 p-1 text-gray-400 hover:text-blue-600 transition" title="Дерево задач">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16M10 12v6"/></svg>
                        </button>
                        <!-- Иконка Инфо (только мобильный) -->
                        <button @click="showInfoModal = true" class="md:hidden flex-shrink-0 p-1 text-gray-400 hover:text-blue-600 transition" title="Статистика">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                    </div>
                </div>
            </div>


            <!-- Stats_Panel: только десктоп -->
            <div class="hidden md:grid grid-cols-5 gap-4">
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
                <!-- Готово (на проверку) — зелёная рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-green-500 p-2 md:p-4">
                    <div class="text-xs text-gray-500 mb-1">Готово</div>
                    <div class="text-lg md:text-2xl font-bold text-gray-800" x-text="currentStats.done"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                </div>
                <!-- Закрыто (принято руководителем) — индиго рамка -->
                <div class="bg-white rounded-lg shadow-sm border border-t-4 border-t-indigo-500 p-2 md:p-4">
                    <div class="text-xs text-gray-500 mb-1">Закрыто</div>
                    <div class="text-lg md:text-2xl font-bold text-gray-800" x-text="currentStats.closed"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.closed / currentStats.total * 100) : 0) + '%'"></div>
                </div>
                <!-- 5-й столбец: Прогресс + Время -->
                <div class="space-y-2">
                    <!-- Прогресс-бар -->
                    <div class="bg-white rounded-lg shadow-sm border p-2 md:p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Прогресс</span>
                            <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden flex">
                                <div class="h-full bg-indigo-500 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.closed / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-green-500 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-orange-400 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                                <div class="h-full bg-amber-400 transition-all duration-300"
                                     :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700" x-text="(currentStats.total > 0 ? Math.round((currentStats.done + currentStats.closed) / currentStats.total * 100) : 0) + '%'"></span>
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
                                <template x-if="currentTimeData.manager > 0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-10">Рук.</span>
                                        <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden">
                                            <div class="h-full rounded bg-purple-400 transition-all duration-300" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.manager / currentTimeData.maxHours * 100) : 0) + '%'"></div>
                                        </div>
                                        <span class="text-xs font-medium text-purple-600 w-12 text-right" x-text="currentTimeData.manager + ' ч'"></span>
                                    </div>
                                </template>
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
            </div><!-- /flex-shrink-0 фиксированная часть -->

            <!-- Мобильный переключатель вкладок (часть fixed-блока) -->
            <div class="md:hidden flex border-b flex-shrink-0">
                <button @click="boardTab = 'in_progress'"
                        :class="boardTab === 'in_progress' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                        class="flex-1 py-2.5 text-sm font-medium text-center transition leading-tight">
                    В работе<br><span class="text-xs opacity-70" x-text="'(' + currentBoard.in_progress.length + ')'"></span>
                </button>
                <button @click="boardTab = 'revision'"
                        :class="boardTab === 'revision' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                        class="flex-1 py-2.5 text-sm font-medium text-center transition leading-tight">
                    Доработки<br><span class="text-xs opacity-70" x-text="'(' + currentBoard.revision.length + ')'"></span>
                </button>
                <button @click="boardTab = 'done'"
                        :class="boardTab === 'done' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                        class="flex-1 py-2.5 text-sm font-medium text-center transition leading-tight">
                    Готово<br><span class="text-xs opacity-70" x-text="'(' + currentBoard.done.length + ')'"></span>
                </button>
                <button @click="boardTab = 'closed'"
                        :class="boardTab === 'closed' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 border-b-2 border-transparent'"
                        class="flex-1 py-2.5 text-sm font-medium text-center transition leading-tight">
                    Закрыто<br><span class="text-xs opacity-70" x-text="'(' + currentBoard.closed.length + ')'"></span>
                </button>
            </div>
            </div><!-- /dashboard-fixed-header -->

            <!-- ПРОКРУЧИВАЕМАЯ ЧАСТЬ: канбан-доска -->
            <div class="flex-1 min-h-0 overflow-y-auto md:overflow-hidden dashboard-mobile-content">

            <!-- Мобильный контент задач (md:hidden) -->
            <div class="md:hidden">
                <div class="space-y-2">
                    <template x-for="task in (boardTab === 'in_progress' ? currentBoard.in_progress : boardTab === 'revision' ? currentBoard.revision : boardTab === 'done' ? currentBoard.done : currentBoard.closed)" :key="task.id">
                        <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                            <div class="flex items-start gap-2">
                                <span class="mobile-task-dot mt-1 w-2 h-2 rounded-full flex-shrink-0"
                                      :class="{
                                          'bg-red-500': task.priority === 'urgent',
                                          'bg-orange-500': task.priority === 'high',
                                          'bg-yellow-400': task.priority === 'medium',
                                          'bg-green-500': task.priority === 'low'
                                      }"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800" x-text="task.title"></div>
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
                    <template x-if="(boardTab === 'in_progress' ? currentBoard.in_progress : boardTab === 'revision' ? currentBoard.revision : boardTab === 'done' ? currentBoard.done : currentBoard.closed).length === 0">
                        <p class="text-sm text-gray-400 text-center py-4">Нет задач</p>
                    </template>
                </div>
            </div>

            <!-- Десктоп: четыре колонки (hidden на мобильном) -->
            <div class="hidden md:flex gap-4 h-full min-h-0">

                <!-- Колонка «В работе» -->
                <div class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="bg-amber-50 rounded-lg p-3 flex flex-col flex-1 min-h-0">
                        <h3 class="text-sm font-medium text-amber-800 mb-3 flex-shrink-0">В работе</h3>
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0">
                            <template x-for="task in currentBoard.in_progress" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mobile-task-dot mt-1 w-2 h-2 rounded-full flex-shrink-0"
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
                <div class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="bg-orange-50 rounded-lg p-3 flex flex-col flex-1 min-h-0">
                        <h3 class="text-sm font-medium text-orange-800 mb-3 flex-shrink-0">Доработки</h3>
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0">
                            <template x-for="task in currentBoard.revision" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mobile-task-dot mt-1 w-2 h-2 rounded-full flex-shrink-0"
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
                <div class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="bg-green-50 rounded-lg p-3 flex flex-col flex-1 min-h-0">
                        <h3 class="text-sm font-medium text-green-800 mb-3 flex-shrink-0">Готово</h3>
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0">
                            <template x-for="task in currentBoard.done" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mobile-task-dot mt-1 w-2 h-2 rounded-full flex-shrink-0"
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

                <!-- Колонка «Закрыто» (принято руководителем) -->
                <div class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="bg-indigo-50 rounded-lg p-3 flex flex-col flex-1 min-h-0">
                        <h3 class="text-sm font-medium text-indigo-800 mb-3 flex-shrink-0">Закрыто</h3>
                        <div class="space-y-2 overflow-y-auto flex-1 min-h-0">
                            <template x-for="task in currentBoard.closed" :key="task.id">
                                <a :href="BASE_URL + '/tasks/' + task.id" class="block bg-white rounded-lg shadow-sm border p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-2">
                                        <span class="mobile-task-dot mt-1 w-2 h-2 rounded-full flex-shrink-0"
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

            </div><!-- /прокручиваемая часть -->
        </div>
    </div>

    <!-- Модалка со статистикой (вне template) -->
    <div x-show="showInfoModal"
         @click.self="showInfoModal = false"
         class="project-stats-modal md:hidden fixed inset-0 z-[200] bg-black/50 flex items-end"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white w-full rounded-t-2xl p-4 pb-8 space-y-3 max-h-[70vh] overflow-y-auto"
             @click.stop>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-700">Статистика проекта</h3>
                <button @click="showInfoModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-4 gap-2">
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
                <div class="bg-white rounded-lg border border-t-4 border-t-indigo-500 p-2">
                    <div class="text-xs text-gray-500 mb-1">Закрыто</div>
                    <div class="text-lg font-bold text-gray-800" x-text="currentStats.closed"></div>
                    <div class="text-xs text-gray-400 mt-1" x-text="(currentStats.total > 0 ? Math.round(currentStats.closed / currentStats.total * 100) : 0) + '%'"></div>
                </div>
            </div>
            <div class="bg-white rounded-lg border p-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Прогресс</span>
                    <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden flex">
                        <div class="h-full bg-indigo-500" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.closed / currentStats.total * 100) : 0) + '%'"></div>
                        <div class="h-full bg-green-500" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.done / currentStats.total * 100) : 0) + '%'"></div>
                        <div class="h-full bg-orange-400" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.revision / currentStats.total * 100) : 0) + '%'"></div>
                        <div class="h-full bg-amber-400" :style="'width:' + (currentStats.total > 0 ? Math.round(currentStats.in_progress / currentStats.total * 100) : 0) + '%'"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-700" x-text="(currentStats.total > 0 ? Math.round((currentStats.done + currentStats.closed) / currentStats.total * 100) : 0) + '%'"></span>
                </div>
            </div>
            <template x-if="currentTimeData.estimated > 0 || currentTimeData.actual > 0">
                <div class="bg-white rounded-lg border p-3 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 w-10">План</span>
                        <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden"><div class="h-full bg-blue-400 rounded" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.estimated / currentTimeData.maxHours * 100) : 0) + '%'"></div></div>
                        <span class="text-xs font-medium text-gray-700 w-12 text-right" x-text="currentTimeData.estimated + ' ч'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 w-10">Факт</span>
                        <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden"><div class="h-full rounded" :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'bg-red-400' : 'bg-green-400'" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.actual / currentTimeData.maxHours * 100) : 0) + '%'"></div></div>
                        <span class="text-xs font-medium w-12 text-right" :class="currentTimeData.actual > currentTimeData.estimated && currentTimeData.estimated > 0 ? 'text-red-600' : 'text-green-600'" x-text="currentTimeData.actual + ' ч'"></span>
                    </div>
                    <template x-if="currentTimeData.manager > 0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 w-10">Рук.</span>
                            <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden"><div class="h-full rounded bg-purple-400" :style="'width:' + (currentTimeData.maxHours > 0 ? Math.round(currentTimeData.manager / currentTimeData.maxHours * 100) : 0) + '%'"></div></div>
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

    <!-- Модалка с деревом задач (вне template для корректного fixed позиционирования) -->
    <div x-show="showTreeModal"
         @click.self="showTreeModal = false"
         class="fixed inset-0 z-[200] bg-black/50 flex items-end md:items-center md:justify-center"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white w-full md:w-[600px] md:max-w-[90vw] rounded-t-2xl md:rounded-2xl p-4 pb-8 max-h-[80vh] overflow-y-auto"
             @click.stop>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-700">Дерево задач</h3>
                <button @click="showTreeModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- Легенда -->
            <div class="flex gap-3 mb-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>В работе</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-500"></span>Доработки</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span>Готово</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>Закрыто</span>
            </div>
            <!-- Загрузка -->
            <div x-show="treeLoading" class="text-center py-8 text-gray-400 text-sm">Загрузка...</div>
            <!-- Пусто -->
            <div x-show="!treeLoading && treeData.length === 0" class="text-center py-8 text-gray-400 text-sm">Нет задач</div>
            <!-- Дерево с линиями -->
            <div x-show="!treeLoading && treeData.length > 0" x-html="renderTree(treeData, 0)"></div>
        </div>
    </div>

</div>

<!-- Alpine.js data-компонент dashboard -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboard', () => ({
        boardTab: 'in_progress',
        projects: <?= json_encode($projects ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        boardData: <?= json_encode($boardData ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        timeData: <?= json_encode($timeData ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        activeProjectId: null,
        showInfoModal: false,
        showTreeModal: false,
        treeData: [],
        treeLoading: false,

        init() {
            if (this.projects.length > 0) {
                this.activeProjectId = this.projects[0].id;
            }
        },

        get currentBoard() {
            return this.boardData[this.activeProjectId] || { in_progress: [], revision: [], done: [], closed: [] };
        },

        get currentStats() {
            const board = this.currentBoard;
            const inProgress = board.in_progress.length;
            const revision = board.revision.length;
            const done = board.done.length;
            const closed = board.closed.length;
            return {
                in_progress: inProgress,
                revision: revision,
                done: done,
                closed: closed,
                total: inProgress + revision + done + closed,
            };
        },

        get currentTimeData() {
            const data = this.timeData[this.activeProjectId] || { estimated: 0, actual: 0, manager: 0 };
            const maxHours = Math.max(data.estimated, data.actual + data.manager, 1);
            return { ...data, maxHours, total: data.actual + data.manager };
        },

        selectProject(projectId) {
            this.activeProjectId = projectId;
        },

        async loadTree() {
            if (!this.activeProjectId) return;
            this.treeLoading = true;
            this.treeData = [];
            try {
                const res = await fetch(BASE_URL + '/ajax/projects/' + this.activeProjectId + '/tree', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.treeData = await res.json();
            } catch (e) {
                this.treeData = [];
            }
            this.treeLoading = false;
        },

        renderTree(nodes, depth) {
            if (!nodes || nodes.length === 0) return '';
            let html = '';
            const statusDot = (code) => {
                if (code === 'in_progress') return 'bg-yellow-500';
                if (code === 'revision') return 'bg-orange-500';
                if (code === 'done') return 'bg-green-500';
                if (code === 'closed') return 'bg-indigo-500';
                return 'bg-gray-400';
            };
            const statusBadge = (code, name) => {
                let cls = 'bg-gray-100 text-gray-600';
                if (code === 'in_progress') cls = 'bg-yellow-100 text-yellow-800';
                else if (code === 'revision') cls = 'bg-orange-100 text-orange-800';
                else if (code === 'done') cls = 'bg-green-100 text-green-800';
                else if (code === 'closed') cls = 'bg-indigo-100 text-indigo-800';
                return `<span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 ${cls}">${this.esc(name)}</span>`;
            };

            nodes.forEach((node, i) => {
                const isLast = i === nodes.length - 1;
                const hasChildren = node.children && node.children.length > 0;
                const connector = depth === 0 ? '' : (isLast ? '└─' : '├─');
                const lineClass = depth === 0 ? '' : 'border-l border-gray-300';

                html += `<div class="relative" style="padding-left: ${depth * 20}px;">`;
                // Вертикальная линия
                if (depth > 0) {
                    html += `<span class="absolute top-0 bottom-0 border-l border-gray-300" style="left: ${(depth - 1) * 20 + 9}px; ${isLast ? 'height: 16px;' : ''}"></span>`;
                    // Горизонтальная линия
                    html += `<span class="absolute border-t border-gray-300" style="left: ${(depth - 1) * 20 + 9}px; top: 16px; width: 11px;"></span>`;
                }
                html += `<div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 relative">`;
                html += `<span class="w-2 h-2 rounded-full flex-shrink-0 ${statusDot(node.status_code)}"></span>`;
                html += `<a href="${BASE_URL}/tasks/${node.id}" class="text-sm text-gray-800 hover:text-blue-600 font-medium flex-1 truncate">${this.esc(node.title)}</a>`;
                html += statusBadge(node.status_code, node.status_name || '');
                html += `</div>`;
                if (hasChildren) {
                    html += this.renderTree(node.children, depth + 1);
                }
                html += `</div>`;
            });
            return html;
        },

        esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }
    }));
});
</script>
