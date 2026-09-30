# Implementation Plan: Unified Dashboard

## Overview

Реализация единого дашборда для ролей Manager и Executor. Заменяет раздельные дашборды (`renderManager` / `renderExecutor`) единым интерфейсом с вкладками проектов, панелью статистики и канбан-доской задач. Используется существующий стек: PHP (бэкенд), Tailwind CSS + Alpine.js (фронтенд). Миграции БД не требуются.

## Tasks

- [x] 1. Создание DashboardService
  - [x] 1.1 Создать файл `app/Services/DashboardService.php` с методами `getBoardData`, `getProjectBoardTasks`, `groupTasksByStatus`, `filterTasksByRole`
    - Реализовать `getBoardData($userId, $roleId)` — получение списка проектов пользователя через `Project::getUserProjects()` и формирование `boardData` (задачи по каждому проекту, сгруппированные по статусам)
    - Реализовать `getProjectBoardTasks($projectId, $userId, $roleId)` — получение задач проекта с JOIN на статусы и пользователей, фильтрация только по Active_Statuses (`in_progress`, `revision`, `done`)
    - Реализовать `groupTasksByStatus($tasks)` — распределение задач в три группы по `status_code`
    - Реализовать `filterTasksByRole($tasks, $userId, $roleId)` — для Manager (role_id=2) возвращать все задачи, для Executor (role_id=3) — только `assigned_to = $userId`
    - _Requirements: 1.1, 3.1, 3.6, 4.1, 5.1, 5.2_

  - [ ]* 1.2 Написать property-тест: корректность выборки проектов пользователя
    - **Property 1: Для любого пользователя и набора записей в `project_users`, метод возвращает ровно те проекты, где `user_id` совпадает с ID пользователя**
    - **Validates: Requirements 1.1**

  - [ ]* 1.3 Написать property-тест: корректность группировки задач по статусам
    - **Property 2: Для любого набора задач каждая задача с `in_progress` попадает в колонку «В работе», `revision` — «Доработки», `done` — «Готово»; задачи с другими статусами исключаются**
    - **Validates: Requirements 3.1, 3.6**

  - [ ]* 1.4 Написать property-тест: ролевая фильтрация задач
    - **Property 3: Manager видит все задачи проекта, Executor — только назначенные на него**
    - **Validates: Requirements 4.1, 5.1, 5.2**

- [x] 2. Checkpoint — Проверить сервисный слой
  - Убедиться, что все тесты проходят. Задать вопросы пользователю, если что-то неясно.

- [x] 3. Обновление DashboardController
  - [x] 3.1 Добавить метод `renderUnified(array $user)` в `DashboardController`
    - Создать экземпляр `DashboardService`, вызвать `getBoardData($userId, $roleId)`
    - Передать `projects`, `boardData`, `roleId` в шаблон `dashboard/unified`
    - _Requirements: 7.1, 7.2, 7.4_

  - [x] 3.2 Заменить вызовы `renderManager` и `renderExecutor` на `renderUnified` в методе `index()`
    - В `switch` для case 2 и case 3 вызвать `$this->renderUnified($user)`
    - Убедиться, что case 1 (Admin) остаётся без изменений (`renderAdmin`)
    - _Requirements: 7.1, 7.2, 7.3_

  - [ ]* 3.3 Написать юнит-тесты для DashboardController
    - Проверить, что Manager и Executor получают unified-шаблон
    - Проверить, что Admin по-прежнему получает admin-шаблон
    - _Requirements: 7.1, 7.2, 7.3_

- [x] 4. Создание view-шаблона unified dashboard
  - [x] 4.1 Создать файл `storage/views/dashboard/unified.php` с Alpine.js компонентом
    - Реализовать блок Project_Tabs — горизонтальная панель вкладок с переключением через `x-on:click`
    - Реализовать блок Stats_Panel — три карточки с числом задач (В работе / Доработки / Готово) с цветовой индикацией (жёлтая, оранжевая, зелёная рамка)
    - Реализовать блок Task_Board — три колонки, каждая содержит Task_Card компоненты
    - Реализовать Task_Card — отображение заголовка, приоритета, дедлайна (если установлен), имени исполнителя; клик ведёт на `/tasks/{id}`
    - Реализовать Alpine.js `dashboard` data-компонент: `projects`, `boardData`, `activeProjectId`, `init()`, `currentBoard`, `currentStats`, `selectProject()`
    - При отсутствии проектов — показать сообщение «Нет проектов»
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4, 3.5, 4.2_

  - [ ]* 4.2 Написать property-тест: полнота данных карточки задачи
    - **Property 4: Для любой задачи с заголовком, приоритетом и исполнителем — карточка содержит все обязательные поля**
    - **Validates: Requirements 3.4, 4.2**

- [x] 5. Адаптивная вёрстка (responsive)
  - [x] 5.1 Добавить responsive-классы Tailwind в шаблон `dashboard/unified.php`
    - Task_Board: на viewport < 768px колонки располагаются вертикально (`flex-col` на `md:flex-row`)
    - Project_Tabs: горизонтальный скролл при переполнении на мобильных (`overflow-x-auto`)
    - Stats_Panel: карточки в одну строку на десктопе, стопка на узких экранах (`grid-cols-1 md:grid-cols-3`)
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 6. Интеграция и финальная проверка
  - [x] 6.1 Убедиться, что маршрут `/dashboard` корректно обслуживает unified-шаблон
    - Проверить, что `config/routes.php` не требует изменений (маршрут уже указывает на `DashboardController::index`)
    - Проверить корректную передачу JSON-данных из PHP в Alpine.js (экранирование через `json_encode` с флагами `JSON_HEX_TAG | JSON_HEX_AMP`)
    - Проверить граничные случаи: пользователь без проектов, проект без задач, задача без дедлайна, задача без исполнителя
    - _Requirements: 7.4, 1.5, 3.4_

  - [ ]* 6.2 Написать интеграционные тесты для полного цикла
    - Тест: Manager видит все задачи всех своих проектов
    - Тест: Executor видит только назначенные задачи
    - Тест: переключение вкладок обновляет данные
    - _Requirements: 4.1, 5.1, 5.2, 1.3_

- [x] 7. Final checkpoint — Финальная проверка
  - Убедиться, что все тесты проходят. Задать вопросы пользователю, если что-то неясно.

## Notes

- Задачи, помеченные `*`, являются опциональными и могут быть пропущены для ускорения MVP
- Каждая задача ссылается на конкретные требования для трассировки
- Checkpoints обеспечивают инкрементальную валидацию
- Property-тесты валидируют универсальные свойства корректности (100 итераций со случайными данными)
- Юнит-тесты валидируют конкретные примеры и граничные случаи
- Миграции БД не требуются — используются существующие таблицы
- Файл `routes.php` не требует изменений — маршрут `/dashboard` уже ведёт на `DashboardController::index`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4", "3.1"] },
    { "id": 2, "tasks": ["3.2", "4.1"] },
    { "id": 3, "tasks": ["3.3", "4.2", "5.1"] },
    { "id": 4, "tasks": ["6.1"] },
    { "id": 5, "tasks": ["6.2"] }
  ]
}
```
