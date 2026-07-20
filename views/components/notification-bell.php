<?php
/**
 * Компонент колокольчика уведомлений — views/components/notification-bell.php
 *
 * Alpine.js-компонент: показывает количество непрочитанных, dropdown со списком.
 * Polling каждые 30 секунд для обновления счётчика.
 */
?>
<div x-data="notificationBell()" x-init="init()" @keydown.escape.window="open = false" class="relative">
    <!-- Кнопка колокольчика -->
    <button type="button" @click="toggleDropdown()" class="desktop-header-icon p-1 relative"
            :aria-label="unreadCount > 0 ? `Уведомления: непрочитанных ${unreadCount}` : 'Уведомления'"
            :aria-expanded="open.toString()" aria-controls="notification-menu">
        <!-- Обычный колокольчик -->
        <svg x-show="!dndEnabled" class="w-5 h-5" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <!-- Перечёркнутый колокольчик (DND) -->
        <svg x-show="dndEnabled" class="w-5 h-5 text-red-400" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/>
        </svg>
        <!-- Бейдж с количеством -->
        <span x-show="unreadCount > 0 && !dndEnabled" x-text="unreadCount > 99 ? '99+' : unreadCount"
              x-transition
              class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full min-w-[18px]" aria-hidden="true">
        </span>
    </button>

    <!-- Выпадающий список уведомлений -->
    <div id="notification-menu" x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50 overflow-hidden" aria-label="Последние уведомления">

        <!-- Заголовок dropdown -->
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b">
            <h3 class="text-sm font-medium text-gray-800">Уведомления</h3>
            <div class="flex items-center gap-2">
                <!-- DND toggle (перечёркнутый колокольчик) -->
                <button type="button" @click.stop="toggleDnd()" class="a11y-icon-button p-1 rounded hover:bg-gray-100 transition"
                        :title="dndEnabled ? 'Включить уведомления' : 'Выключить уведомления'"
                        :aria-label="dndEnabled ? 'Включить уведомления' : 'Выключить уведомления'"
                        :aria-pressed="dndEnabled.toString()">
                    <!-- Колокольчик (если уведомления включены) -->
                    <svg x-show="!dndEnabled" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <!-- Перечёркнутый колокольчик (если DND) -->
                    <svg x-show="dndEnabled" class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/>
                    </svg>
                </button>
                <a href="<?= url('/notifications') ?>" class="text-xs text-blue-600 hover:text-blue-800">Все</a>
            </div>
        </div>

        <!-- Список уведомлений -->
        <div class="max-h-80 overflow-y-auto divide-y">
            <template x-if="items.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-500" role="status">
                    Нет уведомлений
                </div>
            </template>

            <template x-for="item in items" :key="item.id">
                <a :href="item.task_id ? BASE_URL + '/tasks/' + item.task_id : BASE_URL + '/notifications'"
                   @click="markAsRead(item)"
                   class="block px-4 py-3 hover:bg-gray-50 transition"
                   :class="{ 'bg-blue-50': !item.is_read }">
                    <div class="flex items-start gap-3">
                        <!-- Иконка по типу -->
                        <div class="flex-shrink-0 mt-0.5">
                            <span x-show="item.type === 'task_assigned'" class="text-blue-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <span x-show="item.type === 'comment_added'" class="text-green-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </span>
                            <span x-show="item.type === 'status_changed'" class="text-yellow-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </span>
                            <span x-show="item.type === 'file_uploaded'" class="text-purple-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </span>
                        </div>
                        <!-- Текст -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate" :class="item.is_read ? 'text-gray-500' : 'text-gray-800 font-medium'" x-text="item.title"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="formatTime(item.created_at)"></p>
                        </div>
                        <!-- Точка непрочитанного -->
                        <span x-show="!item.is_read" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-2" aria-hidden="true"></span>
                    </div>
                </a>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 bg-gray-50 border-t text-center" x-show="unreadCount > 0">
            <button type="button" @click="markAllRead()" class="text-xs text-blue-600 hover:text-blue-800">
                Отметить все как прочитанные
            </button>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        open: false,
        unreadCount: 0,
        items: [],
        pollInterval: null,
        dndEnabled: <?= (int) ($GLOBALS['_user_dnd'] ?? 0) ?>,

        init() {
            this.fetchCount();
            // Polling каждые 30 секунд
            this.pollInterval = setInterval(() => this.fetchCount(), 30000);
        },

        toggleDropdown() {
            this.open = !this.open;
            if (this.open) {
                this.fetchList();
            }
        },

        async toggleDnd() {
            try {
                const response = await fetch(BASE_URL + '/settings/dnd', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.success) this.dndEnabled = data.dnd_enabled;
            } catch (e) {}
        },

        async fetchCount() {
            try {
                const res = await fetch(BASE_URL + '/ajax/notifications/count', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const newCount = data.count || 0;
                // Если появились новые непрочитанные и вкладка в фоне — мигаем
                if (newCount > this.unreadCount && document.visibilityState !== 'visible' && typeof FaviconBlinker !== 'undefined') {
                    FaviconBlinker.start();
                }
                this.unreadCount = newCount;
            } catch (e) {}
        },

        async fetchList() {
            try {
                const res = await fetch(BASE_URL + '/ajax/notifications/list', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.items = data.items || [];
            } catch (e) {}
        },

        async markAsRead(item) {
            if (item.is_read) return;
            try {
                await fetch(BASE_URL + `/notifications/${item.id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_token=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content || '')
                });
                item.is_read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            } catch (e) {}
        },

        async markAllRead() {
            try {
                await fetch(BASE_URL + '/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '_token=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content || '')
                });
                this.items.forEach(item => item.is_read = true);
                this.unreadCount = 0;
            } catch (e) {}
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            const diffMs = now - d;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMin < 1) return 'только что';
            if (diffMin < 60) return diffMin + ' мин. назад';
            if (diffHours < 24) return diffHours + ' ч. назад';
            if (diffDays < 7) return diffDays + ' дн. назад';

            const pad = (n) => n.toString().padStart(2, '0');
            return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()}`;
        }
    };
}
</script>
