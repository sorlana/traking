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
 * Скрыть мобильный загрузочный экран после старта приложения.
 */
function initAppLoadingScreen() {
    const loader = document.getElementById('app-loading-screen');
    if (!loader || !document.body.classList.contains('app-loading-active')) return;

    let loaderSeen = false;
    try {
        loaderSeen = sessionStorage.getItem('flowtaskLoaderSeen') === '1';
    } catch (e) {}

    if (loaderSeen) {
        loader.remove();
        document.body.classList.remove('app-loading-active', 'app-loading-done');
        return;
    }

    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    if (!isMobile) {
        loader.remove();
        document.body.classList.remove('app-loading-active');
        return;
    }

    try {
        sessionStorage.setItem('flowtaskLoaderSeen', '1');
    } catch (e) {}

    const startedAt = performance.now();
    const minVisibleMs = 1450;
    let hidden = false;

    function hideLoader() {
        if (hidden) return;
        hidden = true;
        document.body.classList.add('app-loading-done');
        window.setTimeout(() => {
            loader.remove();
            document.body.classList.remove('app-loading-active', 'app-loading-done');
        }, 320);
    }

    function hideAfterMinimumTime() {
        const elapsed = performance.now() - startedAt;
        window.setTimeout(hideLoader, Math.max(0, minVisibleMs - elapsed));
    }

    if (document.readyState === 'complete') {
        hideAfterMinimumTime();
    } else {
        window.addEventListener('load', hideAfterMinimumTime, { once: true });
    }

    window.setTimeout(hideLoader, 3500);
}

initAppLoadingScreen();

/**
 * Компактные выпадающие поля для мобильных модалок.
 * Используются в фильтрах и быстрых формах создания.
 */
function setMobileFilterOption(button) {
    const field = button.closest('.mobile-filter-field');
    if (!field) return;

    const input = field.querySelector('input[type="hidden"], select');
    const labelNode = field.querySelector('.mobile-filter-label');
    const details = field.querySelector('details');
    const value = button.dataset.value || '';
    const label = button.dataset.label || button.textContent.trim();

    field.querySelectorAll('.mobile-filter-option').forEach((option) => option.classList.remove('is-selected'));
    button.classList.add('is-selected');

    if (input) {
        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (labelNode) labelNode.textContent = label;
    field.classList.remove('mobile-filter-field-error');
    if (details) details.removeAttribute('open');
}

document.addEventListener('click', (event) => {
    const option = event.target.closest('.mobile-filter-option');
    if (!option) return;
    setMobileFilterOption(option);
}, true);

document.addEventListener('toggle', (event) => {
    const opened = event.target;
    if (!opened.matches('.mobile-filter-details') || !opened.open) return;

    document.querySelectorAll('.mobile-filter-details[open]').forEach((details) => {
        if (details !== opened) details.removeAttribute('open');
    });
}, true);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('[data-mobile-form-validation]')) return;

    let firstInvalidField = null;
    form.querySelectorAll('[data-required-mobile-select]').forEach((field) => {
        const input = field.querySelector('input[type="hidden"], select');
        const isEmpty = !input || !input.value;
        field.classList.toggle('mobile-filter-field-error', isEmpty);
        if (isEmpty && !firstInvalidField) firstInvalidField = field;
    });

    if (!firstInvalidField) return;

    event.preventDefault();
    const details = firstInvalidField.querySelector('details');
    const trigger = firstInvalidField.querySelector('.mobile-filter-trigger');
    if (details) details.setAttribute('open', '');
    if (trigger) trigger.focus();
}, true);

window.setMobileFilterOption = setMobileFilterOption;

function enhanceNativeSelect(select) {
    if (select.dataset.mobileEnhanced === 'true') return;

    const field = select.closest('div');
    if (!field) return;

    field.classList.add('mobile-filter-field');
    select.dataset.mobileEnhanced = 'true';
    select.style.display = 'none';

    const selectedOption = select.options[select.selectedIndex] || select.options[0];
    const details = document.createElement('details');
    details.className = 'mobile-filter-details';

    const summary = document.createElement('summary');
    summary.className = 'mobile-filter-trigger';

    const label = document.createElement('span');
    label.className = 'mobile-filter-label';
    label.textContent = selectedOption ? selectedOption.textContent : '';

    const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    arrow.setAttribute('class', 'mobile-filter-arrow w-4 h-4 text-gray-400');
    arrow.setAttribute('fill', 'none');
    arrow.setAttribute('stroke', 'currentColor');
    arrow.setAttribute('viewBox', '0 0 24 24');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('stroke-width', '2');
    path.setAttribute('d', 'M19 9l-7 7-7-7');
    arrow.appendChild(path);

    summary.append(label, arrow);

    const menu = document.createElement('div');
    menu.className = 'mobile-filter-menu';

    Array.from(select.options).forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mobile-filter-option';
        if (option.selected) button.classList.add('is-selected');
        button.dataset.value = option.value;
        button.dataset.label = option.textContent;
        button.textContent = option.textContent;
        menu.appendChild(button);
    });

    details.append(summary, menu);
    select.insertAdjacentElement('afterend', details);
}

function enhanceProjectFilterSelects() {
    if (!window.matchMedia('(max-width: 1023px)').matches) return;
    document
        .querySelectorAll('[x-show="showFilters"] form[action$="/projects"] select')
        .forEach(enhanceNativeSelect);
}

document.addEventListener('DOMContentLoaded', enhanceProjectFilterSelects);
enhanceProjectFilterSelects();
window.addEventListener('resize', enhanceProjectFilterSelects);
document.addEventListener('click', (event) => {
    if (!event.target.closest('button, a, summary')) return;
    requestAnimationFrame(enhanceProjectFilterSelects);
}, true);

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

/**
 * Валидация значения затраченного времени (клиентская)
 *
 * @param {*} value — значение из поля ввода
 * @returns {{ valid: boolean, error: string|null }}
 */
function validateTimeSpent(value) {
    const num = parseFloat(value);

    if (isNaN(num) || value === '' || value === null) {
        return { valid: false, error: 'Введите корректное числовое значение' };
    }

    if (num <= 0) {
        return { valid: false, error: 'Время должно быть положительным числом' };
    }

    if (num > 999.5) {
        return { valid: false, error: 'Время не может превышать 999.5 часов' };
    }

    // Проверка кратности 0.5: num % 0.5 === 0
    // Используем округление для избежания проблем с плавающей точкой
    if (Math.round(num * 2) !== num * 2) {
        return { valid: false, error: 'Время должно быть кратно 0.5 часа' };
    }

    return { valid: true, error: null };
}

// Делаем функцию доступной глобально для тестирования
window.validateTimeSpent = validateTimeSpent;

/**
 * Обработчик сохранения затраченного времени (AJAX)
 * Использует делегирование событий на document
 */
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.js-save-time');
    if (!btn) return;

    // Находим контейнер с data-task-id и поле ввода
    const container = btn.closest('[data-task-id]');
    if (!container) return;

    const taskId = container.dataset.taskId;
    const input = container.querySelector('.js-time-input');
    if (!input) return;

    const rawValue = input.value.trim();

    // Клиентская валидация
    const validation = validateTimeSpent(rawValue);
    if (!validation.valid) {
        showToast(validation.error, 'error');
        return;
    }

    const timeSpent = parseFloat(rawValue);

    // Блокируем кнопку для предотвращения повторной отправки
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        const response = await fetch(`${BASE_URL}/tasks/${taskId}/time`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ time_spent: timeSpent, type: container.dataset.timeType || 'executor' }),
        });

        // Проверяем, что ответ — JSON (сервер может вернуть HTML при ошибке)
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Time save error (non-JSON):', response.status, text.substring(0, 500));
            showToast('Ошибка сервера: ' + (text.substring(0, 100) || response.status), 'error');
            return;
        }

        const data = await response.json();

        if (response.ok && data.success) {
            const savedValue = data.time_spent !== undefined ? data.time_spent : timeSpent;
            input.value = savedValue;

            // Переключаем в режим просмотра
            switchToViewMode(container, savedValue);
            showToast('Время сохранено', 'success');
        } else {
            // Ошибка от сервера
            const errorMsg = data.error || 'Ошибка при сохранении времени';
            showToast(errorMsg, 'error');
        }
    } catch (err) {
        showToast('Ошибка сети. Попробуйте позже.', 'error');
    } finally {
        // Разблокируем кнопку
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
});

/**
 * Обработчик кнопки «Редактировать» — переключает в режим ввода
 */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-edit-time');
    if (!btn) return;

    const container = btn.closest('[data-task-id]');
    if (!container) return;

    switchToEditMode(container);
});

/**
 * Переключить контейнер времени в режим просмотра
 * @param {HTMLElement} container
 * @param {number} value — сохранённое значение
 */
function switchToViewMode(container, value) {
    const display = container.querySelector('.js-time-display');
    const input = container.querySelector('.js-time-input');
    const unit = container.querySelector('.js-time-unit');
    const saveBtn = container.querySelector('.js-save-time');
    const editBtn = container.querySelector('.js-edit-time');

    // Обновляем текст отображения
    display.textContent = value + ' ч';
    display.style.display = '';

    // Скрываем поле ввода и кнопку сохранения
    input.classList.add('hidden');
    unit.classList.add('hidden');
    saveBtn.classList.add('hidden');

    // Показываем кнопку редактирования
    editBtn.classList.remove('hidden');
}

/**
 * Переключить контейнер времени в режим редактирования
 * @param {HTMLElement} container
 */
function switchToEditMode(container) {
    const display = container.querySelector('.js-time-display');
    const input = container.querySelector('.js-time-input');
    const unit = container.querySelector('.js-time-unit');
    const saveBtn = container.querySelector('.js-save-time');
    const editBtn = container.querySelector('.js-edit-time');

    // Скрываем текст
    display.style.display = 'none';

    // Показываем поле ввода и кнопку сохранения
    input.classList.remove('hidden');
    unit.classList.remove('hidden');
    saveBtn.classList.remove('hidden');

    // Скрываем кнопку редактирования
    editBtn.classList.add('hidden');

    // Фокус на поле ввода
    input.focus();
}

/**
 * Alpine.js компонент: Таймер учёта времени для задачи
 *
 * Логика:
 * - Запуск: начинает отсчёт секунд
 * - Пауза: останавливает отсчёт, сохраняет накопленное время
 * - Продолжить: продолжает отсчёт от накопленного
 * - Стоп: суммирует с предыдущим значением, сохраняет на сервер через AJAX
 * - localStorage: сохраняет состояние таймера при закрытии страницы
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('taskTimer', (taskId, timeType) => ({
        taskId: taskId,
        timeType: timeType || 'executor',
        running: false,
        paused: false,
        seconds: 0,          // Текущая сессия: накопленные секунды
        intervalId: null,
        storageKey: `timer_${taskId}`,

        init() {
            // Восстановление из localStorage
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    this.seconds = state.seconds || 0;
                    if (state.running && state.startedAt) {
                        // Таймер работал — пересчитать с учётом прошедшего времени
                        const elapsed = Math.floor((Date.now() - state.startedAt) / 1000);
                        this.seconds += elapsed;
                        this.startInterval();
                        this.running = true;
                    } else if (state.paused && this.seconds > 0) {
                        this.paused = true;
                    }
                } catch (e) {
                    localStorage.removeItem(this.storageKey);
                }
            }

            // Сохранение состояния при закрытии/уходе со страницы
            window.addEventListener('beforeunload', () => this.saveState());
        },

        get display() {
            const h = Math.floor(this.seconds / 3600);
            const m = Math.floor((this.seconds % 3600) / 60);
            const s = this.seconds % 60;
            if (h > 0) {
                return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            }
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        start() {
            this.running = true;
            this.paused = false;
            this.startInterval();
            this.saveState();
        },

        pause() {
            this.running = false;
            this.paused = true;
            this.stopInterval();
            this.saveState();
        },

        resume() {
            this.running = true;
            this.paused = false;
            this.startInterval();
            this.saveState();
        },

        async stop() {
            this.stopInterval();
            this.running = false;
            this.paused = false;

            if (this.seconds === 0) {
                localStorage.removeItem(this.storageKey);
                return;
            }

            // Конвертируем секунды в часы с округлением до 0.5
            const hours = this.seconds / 3600;
            const rounded = Math.round(hours * 2) / 2; // Округление до 0.5
            const timeToSave = Math.max(rounded, 0.5); // Минимум 0.5 ч

            // Суммируем с текущим значением из поля ввода
            const selector = this.timeType === 'manager'
                ? `.js-time-container[data-task-id="${this.taskId}"][data-time-type="manager"]`
                : `.js-time-container[data-task-id="${this.taskId}"]:not([data-time-type="manager"])`;
            const container = document.querySelector(selector);
            let existingValue = 0;
            if (container) {
                const input = container.querySelector('.js-time-input');
                const display = container.querySelector('.js-time-display');
                if (input && input.value) {
                    existingValue = parseFloat(input.value) || 0;
                } else if (display && display.textContent) {
                    const match = display.textContent.match(/([\d.]+)/);
                    if (match) existingValue = parseFloat(match[1]) || 0;
                }
            }

            const totalTime = existingValue + timeToSave;
            // Ограничение 999.5
            const finalTime = Math.min(totalTime, 999.5);

            // Отправляем на сервер
            try {
                const response = await fetch(`${BASE_URL}/tasks/${this.taskId}/time`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ time_spent: finalTime, type: this.timeType }),
                });

                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    if (response.ok && data.success) {
                        // Обновляем отображение на вкладке Информация
                        if (container) {
                            switchToViewMode(container, data.time_spent);
                        }
                        showToast(`Время сохранено: +${timeToSave} ч (всего ${data.time_spent} ч)`, 'success');
                    } else {
                        showToast(data.error || 'Ошибка сохранения времени', 'error');
                    }
                } else {
                    showToast('Ошибка сервера при сохранении времени', 'error');
                }
            } catch (err) {
                showToast('Ошибка сети. Время не сохранено.', 'error');
            }

            // Обнуляем таймер
            this.seconds = 0;
            localStorage.removeItem(this.storageKey);
        },

        startInterval() {
            this.stopInterval();
            this.intervalId = setInterval(() => {
                this.seconds++;
            }, 1000);
        },

        stopInterval() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        saveState() {
            const state = {
                seconds: this.seconds,
                running: this.running,
                paused: this.paused,
                startedAt: this.running ? Date.now() : null,
            };
            localStorage.setItem(this.storageKey, JSON.stringify(state));
        }
    }));
});
