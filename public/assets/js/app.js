/**
 * Traking — Общий JavaScript
 *
 * Содержит:
 * - CSRF-токен для fetch-запросов
 * - Утилита postJSON для AJAX-запросов
 * - Toast-уведомления
 * - Регистрация Service Worker
 */

// CSRF-токен из meta-тега (вставляется в layout)
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

/**
 * Выполнить POST-запрос через fetch с CSRF-токеном
 *
 * @param {string} url — адрес эндпоинта
 * @param {Object} data — данные для отправки (key-value)
 * @returns {Promise<Object>} — ответ в формате JSON
 */
async function postJSON(url, data = {}) {
    const formData = new FormData();
    formData.append('_token', csrfToken);

    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    });

    return response.json();
}

/**
 * Выполнить GET-запрос через fetch (AJAX)
 *
 * @param {string} url — адрес эндпоинта
 * @returns {Promise<Object>} — ответ в формате JSON
 */
async function getJSON(url) {
    const response = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    return response.json();
}

/**
 * Показать toast-уведомление в правом нижнем углу
 *
 * @param {string} message — текст сообщения
 * @param {string} type — тип: 'success', 'error', 'info'
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');

    // Цвет фона в зависимости от типа
    const bgClasses = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
    };
    const bgClass = bgClasses[type] || bgClasses.success;

    toast.className = `${bgClass} text-white text-sm px-4 py-3 rounded-lg shadow-lg fade-in transition-opacity duration-300`;
    toast.textContent = message;

    container.appendChild(toast);

    // Автоматическое скрытие через 3 секунды
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Регистрация Service Worker для PWA
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    });
}

/**
 * Мигающая фавиконка при непрочитанных уведомлениях
 */
const FaviconBlinker = {
    originalHref: null,
    blinkInterval: null,
    isBlinking: false,
    // Красная версия фавиконки (SVG data URI)
    alertFavicon: 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#EF4444"/><path d="M16 4L8 28h3.5l4.5-12 4.5 12H24L16 4z" fill="white"/><path d="M16 4L14.5 28h3L16 4z" fill="#EF4444"/></svg>'),

    start() {
        if (this.isBlinking) return;
        this.isBlinking = true;

        const link = document.querySelector('link[rel="icon"]');
        if (!link) return;
        this.originalHref = link.href;

        let visible = true;
        this.blinkInterval = setInterval(() => {
            link.href = visible ? this.alertFavicon : this.originalHref;
            visible = !visible;
        }, 800);
    },

    stop() {
        if (!this.isBlinking) return;
        this.isBlinking = false;

        if (this.blinkInterval) {
            clearInterval(this.blinkInterval);
            this.blinkInterval = null;
        }

        const link = document.querySelector('link[rel="icon"]');
        if (link && this.originalHref) {
            link.href = this.originalHref;
        }
    }
};

// Останавливаем мигание при возвращении на вкладку
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        FaviconBlinker.stop();
    }
});
