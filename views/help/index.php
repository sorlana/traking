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
        <p class="text-gray-500">Traking — система управления проектами и задачами. Ниже описаны все функции системы для каждой роли пользователя.</p>
    </div>

    <!-- Навигация по разделам -->
    <div class="bg-white rounded-lg shadow-sm border p-4 md:p-6" x-data="{ role: 'manager' }">
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
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Функции Руководителя</h2>
            <p class="text-sm text-gray-500 mb-6">Руководитель создаёт проекты, ставит задачи исполнителям, контролирует сроки и затраченное время. Видит все задачи проектов, к которым подключён.</p>

            <!-- Дашборд -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Дашборд (Канбан-доска)
                </h3>
                <p class="text-sm text-gray-600 mb-3">Главная страница после входа. Показывает задачи в виде канбан-доски с тремя колонками: «В работе», «Доработки», «Готово».</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Вкладки проектов</strong> — переключайтесь между проектами кликом по названию. Задачи в колонках обновляются автоматически.</li>
                    <li>• <strong>Статистика</strong> — три карточки показывают количество задач в каждом статусе и процент от общего числа.</li>
                    <li>• <strong>Прогресс-бар</strong> — цветная полоса показывает общий прогресс проекта: зелёный = готово, оранжевый = доработки, жёлтый = в работе.</li>
                    <li>• <strong>Диаграмма «План vs Факт»</strong> — если в проекте указано расчётное время, отображается сравнение планового и фактического времени. Зелёный — в пределах плана, красный — перерасход.</li>
                    <li>• <strong>Карточки задач</strong> — кликните на задачу для перехода в карточку. Цветная точка слева — приоритет (красный = срочный, оранжевый = высокий).</li>
                </ul>
            </div>

            <!-- Проекты -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Управление проектами
                </h3>
                <p class="text-sm text-gray-600 mb-3">Проект объединяет задачи и участников. Перейдите в раздел «Проекты» через верхнее меню.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Создание проекта</strong> — нажмите «Создать проект». Обязательное поле — название. Дополнительно: описание, срок сдачи, расчётное время (планируемые часы).</li>
                    <li>• <strong>Расчётное время</strong> — укажите планируемое количество часов на весь проект. Это значение будет сравниваться с фактическим временем (суммой затраченного времени по всем задачам проекта) на дашборде.</li>
                    <li>• <strong>Участники</strong> — на странице проекта добавляйте исполнителей кнопкой «Добавить участника». Только участники проекта могут быть назначены на задачи.</li>
                    <li>• <strong>Документы</strong> — прикрепляйте ссылки на внешние документы (ТЗ, макеты, спецификации). Они будут видны всем участникам.</li>
                    <li>• <strong>Статусы проекта</strong> — «Активный», «На паузе», «Закрыт». Закрытый проект нельзя редактировать.</li>
                    <li>• <strong>Удаление</strong> — удаляет проект со всеми задачами. Действие необратимо, требует подтверждения.</li>
                </ul>
            </div>

            <!-- Задачи -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Управление задачами
                </h3>
                <p class="text-sm text-gray-600 mb-3">Задачи — основные единицы работы. Каждая задача принадлежит проекту и может быть назначена на исполнителя.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Создание задачи</strong> — на странице проекта или в списке задач нажмите «Создать задачу». Укажите название, описание, приоритет (срочный/высокий/средний/низкий), дедлайн и исполнителя.</li>
                    <li>• <strong>Доработки (подзадачи)</strong> — внутри задачи можно создавать дочерние задачи для детализации. Они наследуют проект родительской задачи.</li>
                    <li>• <strong>Назначение исполнителя</strong> — выберите исполнителя из участников проекта. Можно переназначить в любое время, исполнитель получит уведомление.</li>
                    <li>• <strong>Смена статуса</strong> — используйте выпадающий список статусов для перевода задачи (Новая → В работе → На проверке → Выполнена).</li>
                    <li>• <strong>Закрытие задачи</strong> — кнопка «Закрыть» доступна, если все дочерние задачи уже закрыты. После закрытия редактирование и ввод времени блокируются.</li>
                    <li>• <strong>Фильтрация</strong> — в списке задач фильтруйте по статусу, приоритету, исполнителю, дедлайну. Фильтры комбинируются.</li>
                </ul>
            </div>

            <!-- Контроль времени -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Контроль затраченного времени
                </h3>
                <p class="text-sm text-gray-600 mb-3">Руководитель видит время, внесённое исполнителями, но не может его редактировать.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Просмотр на карточке задачи</strong> — на вкладке «Информация» отображается затраченное время исполнителя.</li>
                    <li>• <strong>Суммарное время</strong> — если у задачи есть доработки, показывается суммарное время (задача + все дочерние).</li>
                    <li>• <strong>Диаграмма на дашборде</strong> — по каждому проекту сравнивается расчётное время (план) и фактическое (сумма по всем задачам). Зелёный = в норме, красный = перерасход.</li>
                    <li>• <strong>История изменений</strong> — в истории действий задачи видно, когда и какое время внёс или изменил исполнитель.</li>
                </ul>
            </div>

            <!-- Коммуникация -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Комментарии и файлы
                </h3>
                <p class="text-sm text-gray-600 mb-3">Внутри каждой задачи — чат для обсуждения с исполнителем.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Комментарии</strong> — пишите сообщения в формате чата. Новые сообщения подгружаются автоматически.</li>
                    <li>• <strong>Файлы</strong> — прикрепляйте файлы к комментариям (документы, скриншоты). Максимальный размер зависит от настроек сервера.</li>
                    <li>• <strong>Ссылки</strong> — добавляйте URL-ссылки к комментариям для ссылки на внешние ресурсы.</li>
                    <li>• <strong>Закрепление</strong> — важные сообщения можно закрепить, они будут видны в начале чата.</li>
                    <li>• <strong>Статус прочтения</strong> — галочки рядом с сообщениями показывают, прочитал ли собеседник.</li>
                    <li>• <strong>Редактирование</strong> — свои сообщения можно отредактировать или удалить.</li>
                </ul>
            </div>

            <!-- Уведомления -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Уведомления
                </h3>
                <p class="text-sm text-gray-600 mb-3">Система уведомляет о событиях, требующих вашего внимания.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Колокольчик</strong> — в правом верхнем углу показывает количество непрочитанных уведомлений. Кликните для просмотра списка.</li>
                    <li>• <strong>Push-уведомления</strong> — всплывающие уведомления браузера даже когда вкладка неактивна. Включаются в настройках.</li>
                    <li>• <strong>Типы событий</strong> — новые комментарии, смена статуса задачи, назначение исполнителя, загрузка файлов.</li>
                    <li>• <strong>Режим «Не беспокоить»</strong> — отключает звуки и push-уведомления. Включается в настройках (иконка шестерёнки).</li>
                </ul>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ИСПОЛНИТЕЛЬ -->
        <!-- ============================================================ -->
        <div x-show="role === 'executor'" x-transition>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Функции Исполнителя</h2>
            <p class="text-sm text-gray-500 mb-6">Исполнитель работает с назначенными ему задачами: меняет статус, отмечает время, общается с руководителем через комментарии. Видит только задачи, на которые назначен.</p>

            <!-- Дашборд -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Дашборд
                </h3>
                <p class="text-sm text-gray-600 mb-3">После входа вы попадаете на канбан-доску, где отображаются только назначенные на вас задачи, сгруппированные по статусам.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Колонки</strong> — «В работе», «Доработки», «Готово». Задачи перемещаются между колонками при смене статуса.</li>
                    <li>• <strong>Вкладки проектов</strong> — если вы участвуете в нескольких проектах, переключайтесь между ними вверху страницы.</li>
                    <li>• <strong>Прогресс</strong> — полоса прогресса показывает, какая доля ваших задач выполнена.</li>
                </ul>
            </div>

            <!-- Задачи -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Работа с задачами
                </h3>
                <p class="text-sm text-gray-600 mb-3">Задачи — ваша основная рабочая единица. Кликните на задачу в списке или дашборде для перехода в карточку.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Карточка задачи</strong> — содержит описание, приоритет, дедлайн, прикреплённые файлы, дерево подзадач и историю действий.</li>
                    <li>• <strong>Вкладки карточки</strong> — «Задача» (описание и чат), «Информация» (боковая панель с метаданными и временем), «Доработки» (дочерние задачи), «История» (хронология изменений).</li>
                    <li>• <strong>Смена статуса</strong> — кнопка «Выполнена» или выпадающий список статусов. При смене статуса руководитель получает уведомление.</li>
                    <li>• <strong>Доработки</strong> — если руководитель создал подзадачи, они отображаются на отдельной вкладке. Каждая доработка — самостоятельная задача со своим временем.</li>
                </ul>
            </div>

            <!-- Учёт времени -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Учёт затраченного времени
                </h3>
                <p class="text-sm text-gray-600 mb-3">Вы фиксируете количество часов, затраченных на задачу. Это помогает руководителю оценивать трудозатраты. Доступно два способа: ручной ввод и таймер.</p>

                <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">Ручной ввод</h4>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• Откройте карточку задачи → вкладка «Информация» → поле «Затраченное время».</li>
                    <li>• Нажмите иконку карандаша (✏️) для перехода в режим редактирования.</li>
                    <li>• Введите количество часов с шагом 0.5 (например: 0.5, 1, 1.5, 2, 4, 8).</li>
                    <li>• Нажмите иконку галочки (✓) для сохранения. Значение автоматически отправляется на сервер.</li>
                    <li>• При повторном вводе новое значение <strong>заменяет</strong> предыдущее (не суммирует). Для суммирования используйте таймер.</li>
                </ul>

                <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">Таймер (секундомер)</h4>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• В заголовке карточки задачи (рядом со статусом) есть иконка часов (⏱).</li>
                    <li>• <strong>Нажмите на часы</strong> — запускается отсчёт. Появляется время (00:00) и кнопки «Пауза» и «Стоп».</li>
                    <li>• <strong>Пауза (⏸)</strong> — приостанавливает отсчёт. Кнопка заменяется на «Продолжить» (▶).</li>
                    <li>• <strong>Продолжить (▶)</strong> — возобновляет отсчёт с того же места.</li>
                    <li>• <strong>Стоп (⏹)</strong> — останавливает таймер и сохраняет результат. Время округляется до 0.5 часа (минимум 0.5 ч) и <strong>прибавляется</strong> к текущему значению затраченного времени.</li>
                    <li>• Таймер можно запускать несколько раз — время суммируется при каждом нажатии Стоп.</li>
                    <li>• <strong>Сохранение при закрытии</strong> — если вы закроете страницу с работающим таймером, его состояние сохранится. При возврате на страницу таймер продолжит работу с учётом прошедшего времени.</li>
                </ul>

                <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">Ограничения</h4>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• Время может вносить только назначенный на задачу исполнитель.</li>
                    <li>• После закрытия задачи или родительской задачи ввод/редактирование времени блокируется.</li>
                    <li>• Максимальное значение — 999.5 часов.</li>
                    <li>• Все изменения времени записываются в историю действий задачи.</li>
                </ul>
            </div>

            <!-- Коммуникация -->
            <div class="mb-6 border-b pb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Комментарии и файлы
                </h3>
                <p class="text-sm text-gray-600 mb-3">Общение с руководителем по задаче происходит в чате внутри карточки задачи.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Отправка сообщений</strong> — введите текст в поле внизу и нажмите Enter или кнопку отправки. Сообщения появляются в реальном времени.</li>
                    <li>• <strong>Прикрепление файлов</strong> — нажмите иконку скрепки, выберите файл. Он загрузится и прикрепится к вашему сообщению.</li>
                    <li>• <strong>Ссылки</strong> — добавьте URL-ссылку через иконку ссылки. Указывается URL и название.</li>
                    <li>• <strong>Редактирование</strong> — наведите на своё сообщение, появится меню: редактировать или удалить.</li>
                    <li>• <strong>Статус прочтения</strong> — синие галочки рядом с вашим сообщением означают, что собеседник его прочитал.</li>
                </ul>
            </div>

            <!-- Уведомления -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Уведомления
                </h3>
                <p class="text-sm text-gray-600 mb-3">Вы получаете уведомления о событиях, связанных с вашими задачами.</p>
                <ul class="text-sm text-gray-600 space-y-2 ml-4">
                    <li>• <strong>Назначение на задачу</strong> — когда руководитель назначает вас исполнителем.</li>
                    <li>• <strong>Новые комментарии</strong> — когда руководитель пишет в чат задачи.</li>
                    <li>• <strong>Смена статуса</strong> — когда статус вашей задачи меняется.</li>
                    <li>• <strong>Колокольчик</strong> — в правом верхнем углу. Красный счётчик = непрочитанные уведомления.</li>
                    <li>• <strong>Push-уведомления</strong> — всплывающие уведомления браузера. Работают даже когда вкладка закрыта. Включите в настройках.</li>
                    <li>• <strong>Режим «Не беспокоить»</strong> — отключает все звуки и push. Полезно в нерабочее время.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Общая информация -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Общие возможности</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
            <div>
                <h4 class="font-medium text-gray-800 mb-2">Настройки</h4>
                <p class="mb-2">Перейдите в настройки через иконку шестерёнки (⚙) в правом верхнем углу.</p>
                <ul class="space-y-1">
                    <li>• Включение/отключение push-уведомлений в браузере</li>
                    <li>• Режим «Не беспокоить» — полное отключение оповещений</li>
                    <li>• Настройка звуков уведомлений</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-800 mb-2">Установка на телефон (PWA)</h4>
                <p class="mb-2">Traking работает как мобильное приложение без установки из магазина.</p>
                <ul class="space-y-1">
                    <li>• Откройте сайт в браузере телефона</li>
                    <li>• Нажмите «Добавить на главный экран» (или «Установить» в Chrome)</li>
                    <li>• Приложение откроется в полноэкранном режиме</li>
                    <li>• Push-уведомления работают как в обычном приложении</li>
                    <li>• При отсутствии интернета отображается оффлайн-страница</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Статусы задач -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Статусы задач</h2>
        <p class="text-sm text-gray-600 mb-4">Задачи проходят через следующие статусы. Переход между статусами выполняется вручную.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-3 font-medium text-gray-700">Статус</th>
                        <th class="text-left py-2 px-3 font-medium text-gray-700">Описание</th>
                        <th class="text-left py-2 px-3 font-medium text-gray-700">Кто меняет</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600">
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-400 mr-2"></span>Новая</td>
                        <td class="py-2 px-3">Задача создана, ожидает начала работы</td>
                        <td class="py-2 px-3 text-xs text-gray-500">Автоматически при создании</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500 mr-2"></span>В работе</td>
                        <td class="py-2 px-3">Исполнитель приступил к выполнению</td>
                        <td class="py-2 px-3 text-xs text-gray-500">Исполнитель / Руководитель</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-500 mr-2"></span>На проверке</td>
                        <td class="py-2 px-3">Работа завершена, ожидает проверки руководителем</td>
                        <td class="py-2 px-3 text-xs text-gray-500">Исполнитель</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 mr-2"></span>Выполнена</td>
                        <td class="py-2 px-3">Руководитель подтвердил выполнение</td>
                        <td class="py-2 px-3 text-xs text-gray-500">Руководитель / Исполнитель</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 mr-2"></span>Закрыта</td>
                        <td class="py-2 px-3">Задача завершена, редактирование заблокировано</td>
                        <td class="py-2 px-3 text-xs text-gray-500">Руководитель</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
