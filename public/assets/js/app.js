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
