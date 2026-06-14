<?php
/**
 * Страница помощи — Руководство пользователя
 * views/help/index.php
 */
$layout = 'layouts/app';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Руководство пользователя</h1>
        <p class="text-gray-500">Подробная информация о работе с системой Traking для каждой роли.</p>
    </div>

    <!-- Навигация по разделам -->
    <div class="bg-white rounded-lg shadow-sm border p-4" x-data="{ role: 'manager' }">
        <div class="flex gap-2 mb-6">
            <button @click="role = 'manager'"
                    :class="role === 'manager' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition">
                Руководитель
            </button>
            <button @click="role = 'executor'"
                    :class="role === 'executor' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition">
                Исполнитель
            </button>
        </div>

        <!-- ============================================================ -->
        <!-- РУКОВОДИТЕЛЬ -->
        <!-- ============================================================ -->
        <div x-show="role === 'manager'" x-transition>
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Функции Руководителя</h2>
            <p class="text-sm text-gray-500 mb-6">Руководитель управляет проектами, задачами и командой исполнителей.</p>

            <!-- Дашборд -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Дашборд
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• Обзор всех проектов и их статусов</li>
                    <li>• Количество активных задач, просроченные дедлайны</li>
                    <li>• Быстрый доступ к задачам, требующим внимания</li>
                </ul>
            </div>

            <!-- Проекты -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Управление проектами
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Создание проекта</strong> — название, описание, дедлайн, приоритет</li>
                    <li>• <strong>Редактирование</strong> — изменение параметров проекта в любое время</li>
                    <li>• <strong>Участники</strong> — добавление/удаление исполнителей в проект</li>
                    <li>• <strong>Документы</strong> — прикрепление ссылок на документацию проекта</li>
                    <li>• <strong>Статусы</strong> — смена статуса проекта (активный, на паузе, закрыт)</li>
                    <li>• <strong>Удаление</strong> — удаление проекта (необратимо)</li>
                </ul>
            </div>

            <!-- Задачи -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Управление задачами
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Создание задачи</strong> — название, описание, приоритет, дедлайн, назначение исполнителя</li>
                    <li>• <strong>Доработки (подзадачи)</strong> — создание дочерних задач для детализации работы</li>
                    <li>• <strong>Назначение/переназначение</strong> — выбор или смена исполнителя</li>
                    <li>• <strong>Смена статуса</strong> — перевод задачи между статусами (новая, в работе, на проверке, выполнена)</li>
                    <li>• <strong>Закрытие задачи</strong> — завершение задачи (только если все дочерние закрыты)</li>
                    <li>• <strong>Удаление</strong> — удаление задачи (необратимо)</li>
                    <li>• <strong>Фильтрация</strong> — по статусу, приоритету, исполнителю, дедлайну</li>
                </ul>
            </div>

            <!-- Коммуникация -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Комментарии и файлы
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Комментарии</strong> — переписка по задаче в формате чата</li>
                    <li>• <strong>Файлы</strong> — прикрепление файлов к комментариям</li>
                    <li>• <strong>Ссылки</strong> — добавление ссылок к комментариям</li>
                    <li>• <strong>Закрепление</strong> — закрепление важных сообщений</li>
                    <li>• <strong>Редактирование/удаление</strong> — своих комментариев</li>
                </ul>
            </div>

            <!-- Контроль -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Контроль и отчётность
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Затраченное время</strong> — просмотр времени, внесённого исполнителями</li>
                    <li>• <strong>Суммарное время</strong> — общее время по задаче и всем доработкам</li>
                    <li>• <strong>История действий</strong> — полная хронология изменений по каждой задаче</li>
                    <li>• <strong>Уведомления</strong> — оповещения о смене статусов, новых комментариях</li>
                </ul>
            </div>

            <!-- Уведомления -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Уведомления
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• Push-уведомления в браузере (можно включить в настройках)</li>
                    <li>• Колокольчик с счётчиком непрочитанных</li>
                    <li>• Режим «Не беспокоить» — отключение звуков и push</li>
                </ul>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ИСПОЛНИТЕЛЬ -->
        <!-- ============================================================ -->
        <div x-show="role === 'executor'" x-transition>
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Функции Исполнителя</h2>
            <p class="text-sm text-gray-500 mb-6">Исполнитель работает с назначенными задачами, отчитывается о прогрессе и затраченном времени.</p>

            <!-- Дашборд -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Дашборд
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• Список ваших активных задач</li>
                    <li>• Просроченные задачи (требуют внимания)</li>
                    <li>• Быстрый переход к текущей работе</li>
                </ul>
            </div>

            <!-- Задачи -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Работа с задачами
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Просмотр задач</strong> — список назначенных вам задач с фильтрацией</li>
                    <li>• <strong>Карточка задачи</strong> — описание, приоритет, дедлайн, файлы, история</li>
                    <li>• <strong>Смена статуса</strong> — «В работе», «На проверке», «Выполнена»</li>
                    <li>• <strong>Доработки</strong> — просмотр и работа с подзадачами</li>
                    <li>• <strong>Дерево задач</strong> — визуальное дерево подзадач</li>
                </ul>
            </div>

            <!-- Учёт времени -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Учёт затраченного времени
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Ввод времени</strong> — нажмите иконку карандаша на вкладке «Информация» задачи</li>
                    <li>• <strong>Формат</strong> — часы с шагом 0.5 (например: 0.5, 1, 1.5, 2, 8)</li>
                    <li>• <strong>Редактирование</strong> — можно изменить время до закрытия задачи</li>
                    <li>• <strong>Ограничения</strong> — только назначенный исполнитель может вносить время</li>
                    <li>• <strong>Блокировка</strong> — после закрытия задачи (или родительской) редактирование недоступно</li>
                </ul>
            </div>

            <!-- Коммуникация -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Комментарии и файлы
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• <strong>Отправка комментариев</strong> — обсуждение задачи в формате чата</li>
                    <li>• <strong>Прикрепление файлов</strong> — загрузка файлов к сообщениям</li>
                    <li>• <strong>Добавление ссылок</strong> — ссылки на внешние ресурсы</li>
                    <li>• <strong>Редактирование/удаление</strong> — своих комментариев</li>
                    <li>• <strong>Статус прочтения</strong> — галочки показывают, прочитано ли сообщение</li>
                </ul>
            </div>

            <!-- Уведомления -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Уведомления
                </h3>
                <ul class="text-sm text-gray-600 space-y-1 ml-7">
                    <li>• Уведомления о назначении на задачу</li>
                    <li>• Уведомления о новых комментариях</li>
                    <li>• Уведомления о смене статуса задачи</li>
                    <li>• Push-уведомления в браузере (настраивается)</li>
                    <li>• Режим «Не беспокоить»</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Общая информация -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Общие возможности</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
            <div>
                <h4 class="font-medium text-gray-800 mb-2">Настройки профиля</h4>
                <ul class="space-y-1">
                    <li>• Смена пароля</li>
                    <li>• Включение/отключение push-уведомлений</li>
                    <li>• Режим «Не беспокоить»</li>
                    <li>• Настройка звуков</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-800 mb-2">PWA (мобильное приложение)</h4>
                <ul class="space-y-1">
                    <li>• Установите сайт на главный экран телефона</li>
                    <li>• Работает как приложение (полноэкранный режим)</li>
                    <li>• Получайте push-уведомления</li>
                    <li>• Оффлайн-страница при отсутствии интернета</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Статусы задач -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Статусы задач</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-3 font-medium text-gray-700">Статус</th>
                        <th class="text-left py-2 px-3 font-medium text-gray-700">Описание</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600">
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-400 mr-2"></span>Новая</td>
                        <td class="py-2 px-3">Задача создана, ожидает начала работы</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500 mr-2"></span>В работе</td>
                        <td class="py-2 px-3">Исполнитель работает над задачей</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-500 mr-2"></span>На проверке</td>
                        <td class="py-2 px-3">Работа выполнена, ожидает проверки руководителем</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 mr-2"></span>Выполнена</td>
                        <td class="py-2 px-3">Задача выполнена и проверена</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 mr-2"></span>Закрыта</td>
                        <td class="py-2 px-3">Задача завершена, редактирование заблокировано</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
