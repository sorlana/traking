# Requirements Document

## Introduction

Единый дашборд для ролей Руководитель (Manager) и Исполнитель (Executor). Заменяет текущий раздельный дашборд единым интерфейсом с вкладками проектов, панелью статистики и тремя колонками задач (канбан-стиль). Каждая роль видит задачи согласно своим правам доступа.

## Glossary

- **Dashboard** — главная страница `/dashboard`, отображающая сводку по задачам пользователя
- **Project_Tabs** — горизонтальная панель вкладок в верхней части дашборда; каждая вкладка соответствует одному проекту пользователя
- **Stats_Panel** — блок из трёх карточек статистики, показывающий количество задач по статусам (В работе, Доработки, Готово) с цветовой индикацией
- **Task_Board** — область с тремя колонками для отображения карточек задач, сгруппированных по статусам
- **Task_Card** — визуальное представление одной задачи в колонке (заголовок, приоритет, дедлайн, исполнитель)
- **Manager** — пользователь с `role_id = 2` (Руководитель)
- **Executor** — пользователь с `role_id = 3` (Исполнитель)
- **Active_Statuses** — статусы `in_progress`, `revision`, `done` (отображаются на доске)
- **System** — серверная часть приложения Traking (PHP, контроллер + модель)
- **UI** — клиентская часть интерфейса (HTML/Tailwind CSS/Alpine.js)

## Requirements

### Requirement 1: Отображение вкладок проектов

**User Story:** Как Руководитель или Исполнитель, я хочу видеть вкладки с моими проектами в верхней части дашборда, чтобы быстро переключаться между проектами.

#### Acceptance Criteria

1. WHEN the Dashboard page loads, THE System SHALL retrieve all projects where the current user is a participant (via `project_users` table) and render Project_Tabs for each project.
2. THE UI SHALL display each Project_Tab with the project title as the tab label.
3. WHEN a user clicks on a Project_Tab, THE UI SHALL set the clicked tab as active and display data only for the selected project without a full page reload.
4. WHEN the Dashboard page loads for the first time in a session, THE UI SHALL activate the first Project_Tab by default.
5. IF the user has no projects assigned, THEN THE Dashboard SHALL display a message "Нет проектов" instead of Project_Tabs and Task_Board.

### Requirement 2: Панель статистики

**User Story:** Как Руководитель или Исполнитель, я хочу видеть панель со счётчиками задач по статусам для выбранного проекта, чтобы быстро оценить состояние дел.

#### Acceptance Criteria

1. WHEN a Project_Tab is active, THE System SHALL calculate task counts for statuses `in_progress`, `revision`, and `done` within the selected project, filtered by the user's role-based visibility rules.
2. THE UI SHALL display the Stats_Panel with three cards: "В работе" (yellow/orange top border), "Доработки" (orange/red top border), "Готово" (green top border).
3. THE Stats_Panel SHALL show the numeric count of tasks for each status card.
4. WHEN the active Project_Tab changes, THE Stats_Panel SHALL update its counts to reflect the newly selected project.

### Requirement 3: Колонки задач (Task Board)

**User Story:** Как Руководитель или Исполнитель, я хочу видеть задачи выбранного проекта в трёх колонках по статусам, чтобы наглядно отслеживать прогресс.

#### Acceptance Criteria

1. WHEN a Project_Tab is active, THE System SHALL retrieve tasks for the selected project and group them into three columns: "В работе" (`in_progress`), "Доработки" (`revision`), "Готово" (`done`).
2. THE UI SHALL render Task_Board with three vertical columns, each having a column header with the status name.
3. THE UI SHALL render each task as a Task_Card within its corresponding status column.
4. THE Task_Card SHALL display the task title, priority indicator, deadline (if set), and assigned user name.
5. WHEN a user clicks on a Task_Card, THE System SHALL navigate the user to the task detail page (`/tasks/{id}`).
6. THE Task_Board SHALL exclude tasks with status `closed` from display.

### Requirement 4: Фильтрация по роли (Руководитель)

**User Story:** Как Руководитель, я хочу видеть все задачи своих проектов на дашборде, чтобы контролировать работу команды.

#### Acceptance Criteria

1. WHILE the current user has the Manager role, THE System SHALL display all tasks in the selected project regardless of the `assigned_to` field.
2. WHILE the current user has the Manager role, THE Task_Card SHALL display the name of the assigned Executor for each task.

### Requirement 5: Фильтрация по роли (Исполнитель)

**User Story:** Как Исполнитель, я хочу видеть на дашборде только задачи, назначенные мне, чтобы сосредоточиться на своей работе.

#### Acceptance Criteria

1. WHILE the current user has the Executor role, THE System SHALL display only tasks where `assigned_to` equals the current user ID within the selected project.
2. WHILE the current user has the Executor role, THE Stats_Panel SHALL count only tasks assigned to the current user.

### Requirement 6: Адаптивная вёрстка

**User Story:** Как пользователь мобильного устройства, я хочу, чтобы дашборд корректно отображался на экранах разной ширины, чтобы работать с задачами с телефона.

#### Acceptance Criteria

1. WHILE the viewport width is less than 768px, THE UI SHALL display Task_Board columns stacked vertically instead of side-by-side.
2. WHILE the viewport width is less than 768px, THE Project_Tabs SHALL be horizontally scrollable if they overflow the screen width.
3. THE Stats_Panel SHALL adapt its layout to show cards in a single row on desktop and stacked on narrow screens.

### Requirement 7: Замена текущего дашборда

**User Story:** Как разработчик системы, я хочу, чтобы новый единый дашборд заменил существующий для ролей Manager и Executor, сохранив при этом дашборд Admin без изменений.

#### Acceptance Criteria

1. WHEN a user with the Manager role navigates to `/dashboard`, THE System SHALL render the unified Dashboard view.
2. WHEN a user with the Executor role navigates to `/dashboard`, THE System SHALL render the unified Dashboard view.
3. WHEN a user with the Admin role navigates to `/dashboard`, THE System SHALL continue rendering the existing admin dashboard without changes.
4. THE System SHALL serve the unified Dashboard at the existing route `/dashboard` without introducing additional routes for the board view.
