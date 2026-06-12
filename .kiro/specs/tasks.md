# Задачи реализации: Система Traking

> **Платформа:** Shared-хостинг Hostiman (тариф RuGold 200₽/мес)
> **Стек:** Чистый PHP 8.2 + MySQL + vanilla JS + Tailwind (CDN)
> **Деплой:** SSH + git pull или FTP
> **Без:** Laravel, Node.js на сервере, Composer на сервере (vendor собирается локально)

---

## Этап 1. Инициализация проекта и архитектура

- [ ] 1.1. Создать структуру каталогов проекта:
  ```
  traking/
  ├── app/
  │   ├── Controllers/
  │   ├── Models/
  │   ├── Services/
  │   ├── Middleware/
  │   └── Helpers/
  ├── config/
  │   ├── database.php
  │   ├── app.php
  │   └── routes.php
  ├── public/
  │   ├── index.php          (единая точка входа)
  │   ├── .htaccess
  │   ├── assets/
  │   │   ├── css/
  │   │   ├── js/
  │   │   └── img/
  │   ├── manifest.json
  │   └── service-worker.js
  ├── views/
  │   ├── layouts/
  │   ├── auth/
  │   ├── dashboard/
  │   ├── projects/
  │   ├── tasks/
  │   ├── notifications/
  │   └── admin/
  ├── database/
  │   └── schema.sql
  ├── storage/
  │   ├── uploads/
  │   └── logs/
  ├── vendor/                 (autoload, собирается локально)
  └── .htaccess               (редирект всего в public/)
  ```
- [ ] 1.2. Создать `public/index.php` — точка входа, подключение autoload, роутер
- [ ] 1.3. Создать простой роутер (`app/Helpers/Router.php`) — маршрутизация URL → Controller
- [ ] 1.4. Создать базовый шаблонизатор (`app/Helpers/View.php`) — include views с передачей данных
- [ ] 1.5. Создать `config/database.php` — подключение к MySQL через PDO
- [ ] 1.6. Создать `config/app.php` — базовые настройки (APP_URL, APP_NAME, DEBUG)
- [ ] 1.7. Создать `.htaccess` для перенаправления запросов в `public/index.php`
- [ ] 1.8. Создать `public/.htaccess` — rewrite rules
- [ ] 1.9. Настроить автозагрузку классов (PSR-4 autoloader или simple autoload)
- [ ] 1.10. Первый коммит и push в репозиторий

## Этап 2. База данных

- [ ] 2.1. Написать `database/schema.sql` — полная структура всех таблиц:
  - `roles` (id, code, name)
  - `users` (id, name, email, login, password_hash, role_id, status, created_at, updated_at, last_login_at)
  - `auth_tokens` (id, user_id, token_hash, expires_at, created_at, last_used_at, user_agent, ip_address)
  - `project_statuses` (id, code, name, sort_order)
  - `task_statuses` (id, code, name, sort_order)
  - `projects` (id, title, description, deadline, status_id, created_by, created_at, updated_at, closed_at)
  - `project_users` (id, project_id, user_id, project_role, created_at)
  - `project_documents` (id, project_id, title, document_type, file_path, external_url, comment, uploaded_by, created_at)
  - `tasks` (id, project_id, parent_id, title, description, status_id, priority, deadline, created_by, assigned_to, created_at, updated_at, closed_at)
  - `task_participants` (id, task_id, user_id, role, created_at)
  - `task_comments` (id, task_id, user_id, comment_text, created_at, updated_at, parent_comment_id)
  - `task_files` (id, task_id, comment_id, uploaded_by, file_name, file_path, file_type, file_size, created_at)
  - `task_links` (id, task_id, comment_id, user_id, url, title, created_at)
  - `notifications` (id, user_id, type, title, message, project_id, task_id, comment_id, is_read, created_at, read_at)
  - `activity_log` (id, user_id, project_id, task_id, action_type, old_value, new_value, created_at)
- [ ] 2.2. Добавить индексы и внешние ключи
- [ ] 2.3. Написать `database/seed.sql` — начальные данные:
  - Роли: admin, manager, executor
  - Статусы проектов: Новый, В работе, На паузе, На проверке, Завершён, Отменён
  - Статусы задач: Сделать, В работе, Проверка, Правки, Закрыто, Отменено
  - Admin-пользователь (логин: admin, пароль: сгенерированный)
- [ ] 2.4. Импортировать SQL через phpMyAdmin (ispmanager) или SSH

## Этап 3. Ядро приложения

- [ ] 3.1. Класс `Database` — singleton PDO-подключение, подготовленные запросы
- [ ] 3.2. Базовый класс `Model` — CRUD-операции (find, findAll, create, update, delete, where)
- [ ] 3.3. Класс `Request` — обёртка над $_GET, $_POST, $_FILES, $_COOKIE, валидация
- [ ] 3.4. Класс `Response` — redirect, json, view
- [ ] 3.5. Класс `Session` — работа с сессиями
- [ ] 3.6. Класс `Auth` — текущий пользователь, проверка авторизации, проверка роли
- [ ] 3.7. Базовый класс `Controller` — общие методы (view, redirect, json, authorize)
- [ ] 3.8. Middleware-система — цепочка проверок перед контроллером (auth, role, access)
- [ ] 3.9. Класс `Validator` — проверка полей (required, email, min, max, in, file)
- [ ] 3.10. CSRF-защита — генерация и проверка токенов в формах

## Этап 4. Авторизация

- [ ] 4.1. Модель `User` — связь с ролью, методы аутентификации
- [ ] 4.2. Модель `AuthToken` — создание, проверка, удаление токена
- [ ] 4.3. `AuthService` — login(login, password), logout(), verifyToken(cookie), generateToken(user)
- [ ] 4.4. `AuthController` — showLogin(), login(), logout()
- [ ] 4.5. Middleware `AuthMiddleware` — проверка cookie auth_token при каждом запросе
- [ ] 4.6. View: `views/auth/login.php` — форма входа (логин, пароль, кнопка)
- [ ] 4.7. Логика cookie: HttpOnly, Secure, SameSite=Lax, Max-Age=7776000 (3 месяца)
- [ ] 4.8. Хэширование: password_hash() / password_verify()
- [ ] 4.9. Rate limiting: простой счётчик попыток по IP (таблица или файл)
- [ ] 4.10. Редирект на дашборд после успешного входа

## Этап 5. Middleware и роли

- [ ] 5.1. `RoleMiddleware` — проверка роли (admin, manager, executor)
- [ ] 5.2. `ProjectAccessMiddleware` — пользователь подключён к проекту
- [ ] 5.3. `TaskAccessMiddleware` — пользователь имеет доступ к задаче
- [ ] 5.4. Регистрация middleware в роутере (привязка к группам маршрутов)
- [ ] 5.5. Helper-функция `can($action, $resource)` — проверка прав в views

## Этап 6. Управление пользователями (Admin)

- [ ] 6.1. `UserController` — index, create, store, edit, update, toggleStatus, resetPassword
- [ ] 6.2. View: `views/admin/users/index.php` — таблица пользователей, фильтры, поиск
- [ ] 6.3. View: `views/admin/users/form.php` — форма создания/редактирования
- [ ] 6.4. Валидация: уникальность логина/email, обязательные поля
- [ ] 6.5. Генерация временного пароля при создании пользователя
- [ ] 6.6. Фильтры: по роли, статусу; поиск по имени/email/логину

## Этап 7. Проекты

- [ ] 7.1. Модель `Project` — связи (users, tasks, documents, status)
- [ ] 7.2. Модель `ProjectUser` — связь пользователей с проектом
- [ ] 7.3. Модель `ProjectDocument` — документы проекта
- [ ] 7.4. `ProjectController` — index, show, create, store, edit, update
- [ ] 7.5. View: `views/projects/index.php` — карточки проектов с фильтрами
- [ ] 7.6. View: `views/projects/show.php` — карточка проекта (инфо, участники, документы, задачи)
- [ ] 7.7. View: `views/projects/form.php` — форма создания/редактирования
- [ ] 7.8. Добавление/удаление участников (руководители, исполнители)
- [ ] 7.9. Загрузка документов (файлы + ссылки), выбор типа документа
- [ ] 7.10. Смена статуса проекта
- [ ] 7.11. Фильтры: статус, руководитель, исполнитель, срок
- [ ] 7.12. Проверка доступа: manager видит свои, executor видит свои, admin — все

## Этап 8. Задачи и дерево подзадач

- [ ] 8.1. Модель `Task` — связи (project, parent, children, status, assignee, creator, comments, files)
- [ ] 8.2. `TaskTreeService` — получение дерева (рекурсивный CTE или PHP-рекурсия), проверка закрытия
- [ ] 8.3. `TaskController` — index, show, create, store, edit, update, changeStatus, close, reassign
- [ ] 8.4. View: `views/tasks/index.php` — список задач с фильтрами
- [ ] 8.5. View: `views/tasks/show.php` — карточка задачи (описание, статус, комментарии, файлы, подзадачи, история)
- [ ] 8.6. View: `views/tasks/tree.php` — визуальное дерево подзадач
- [ ] 8.7. View: `views/tasks/form.php` — форма создания задачи/подзадачи
- [ ] 8.8. AJAX: смена статуса без перезагрузки (`fetch` → PHP endpoint)
- [ ] 8.9. Создание подзадач (передача parent_id)
- [ ] 8.10. Запрет закрытия задачи при открытых подзадачах
- [ ] 8.11. Переназначение задачи (выбор из исполнителей проекта)
- [ ] 8.12. Фильтры: статус, исполнитель, приоритет, срок, просрочено

## Этап 9. Комментарии

- [ ] 9.1. Модель `TaskComment`
- [ ] 9.2. `CommentController` — store, update, delete (AJAX-ответы в JSON)
- [ ] 9.3. Компонент комментариев в карточке задачи (views/components/comments.php)
- [ ] 9.4. AJAX: добавление комментария без перезагрузки
- [ ] 9.5. Ограничение видимости: исполнитель видит комментарии только в своих задачах
- [ ] 9.6. Отображение автора, даты, возможность редактирования своего комментария

## Этап 10. Файлы и ссылки

- [ ] 10.1. Модель `TaskFile`, модель `TaskLink`
- [ ] 10.2. `FileController` — upload, download, delete
- [ ] 10.3. Загрузка файлов: валидация типа и размера (макс. 50 МБ)
- [ ] 10.4. Хранение: `storage/uploads/projects/{project_id}/tasks/{task_id}/`
- [ ] 10.5. Безопасная отдача: скрипт download.php с проверкой прав
- [ ] 10.6. Защита папки storage через .htaccess (deny from all)
- [ ] 10.7. Добавление ссылок (URL + название)
- [ ] 10.8. Отображение файлов и ссылок в карточке задачи

## Этап 11. Уведомления

- [ ] 11.1. Модель `Notification`
- [ ] 11.2. `NotificationService` — create(user, type, data), markRead(id), getUnread(user), getCount(user)
- [ ] 11.3. Вызов NotificationService при событиях:
  - Назначение задачи → уведомление исполнителю
  - Новый комментарий → уведомление участникам
  - Смена статуса на «Проверка» → уведомление руководителю
  - Возврат на правки → уведомление исполнителю
  - Загрузка файла → уведомление руководителю
  - Переназначение → уведомление новому исполнителю
- [ ] 11.4. `NotificationController` — index, markRead, markAllRead
- [ ] 11.5. View: `views/notifications/index.php` — список уведомлений
- [ ] 11.6. Компонент «колокольчик» в шапке (badge с количеством)
- [ ] 11.7. AJAX: получение количества новых уведомлений каждые 30 сек
- [ ] 11.8. Клик по уведомлению → переход в задачу

## Этап 12. История действий

- [ ] 12.1. Модель `ActivityLog`
- [ ] 12.2. `ActivityLogService` — log(user, project, task, action_type, old_value, new_value)
- [ ] 12.3. Интеграция: запись при создании задачи, смене статуса, назначении, комментарии, файле, закрытии
- [ ] 12.4. Отображение истории в карточке задачи (таймлайн)
- [ ] 12.5. Отображение истории в карточке проекта
- [ ] 12.6. Страница журнала действий для admin

## Этап 13. Дашборд

- [ ] 13.1. `DashboardController` — данные по роли
- [ ] 13.2. View: `views/dashboard/admin.php` — все проекты, задачи, пользователи, активность
- [ ] 13.3. View: `views/dashboard/manager.php` — мои проекты, задачи на проверке, просроченные
- [ ] 13.4. View: `views/dashboard/executor.php` — мои задачи, новые правки, на проверке

## Этап 14. Адаптивная вёрстка и PWA

- [ ] 14.1. Базовый layout с Tailwind CSS (через CDN — без сборки!)
- [ ] 14.2. Адаптивная навигация: десктоп-меню + мобильный «бургер»
- [ ] 14.3. Адаптивные карточки проектов (grid → stack на мобильном)
- [ ] 14.4. Адаптивная карточка задачи
- [ ] 14.5. `public/manifest.json` — имя, иконки, theme_color, start_url, display: standalone
- [ ] 14.6. `public/service-worker.js` — кэширование статики, offline-fallback
- [ ] 14.7. Иконки PWA: 192x192, 512x512
- [ ] 14.8. Мета-теги в layout: theme-color, apple-touch-icon, viewport

## Этап 15. Поиск и фильтры

- [ ] 15.1. Фильтры проектов: статус, руководитель, исполнитель, срок
- [ ] 15.2. Фильтры задач: статус, исполнитель, автор, приоритет, просрочено
- [ ] 15.3. Поиск по названию (LIKE %query%)
- [ ] 15.4. Сохранение фильтров в URL (GET-параметры)

## Этап 16. Аналитика (базовая)

- [ ] 16.1. Виджеты для руководителя: всего задач, открыто, на проверке, просрочено
- [ ] 16.2. Виджеты для admin: активность, нагрузка по исполнителям
- [ ] 16.3. Виджеты для исполнителя: мои задачи по статусам

## Этап 17. Безопасность и тестирование

- [ ] 17.1. Все SQL-запросы через подготовленные выражения (PDO prepare)
- [ ] 17.2. Экранирование вывода: htmlspecialchars() везде в views
- [ ] 17.3. CSRF-токены во всех формах
- [ ] 17.4. Валидация загружаемых файлов (реальный MIME-тип, размер)
- [ ] 17.5. Rate limiting на странице входа
- [ ] 17.6. .htaccess: запрет доступа к app/, config/, storage/, database/, vendor/
- [ ] 17.7. Проверка прав на каждом действии (не только в middleware, но и в контроллере)
- [ ] 17.8. Ручное тестирование: вход, роли, проекты, задачи, комментарии, файлы, уведомления

## Этап 18. Развёртывание на Hostiman

- [ ] 18.1. Создать БД через phpMyAdmin в ispmanager
- [ ] 18.2. Импортировать `database/schema.sql` и `database/seed.sql`
- [ ] 18.3. Загрузить файлы по SSH (git clone) или FTP
- [ ] 18.4. В ispmanager: настроить document root домена на папку `public/`
- [ ] 18.5. Создать `config/database.php` с реальными данными БД на сервере
- [ ] 18.6. Проверить .htaccess и mod_rewrite
- [ ] 18.7. Установить права на папку `storage/` (chmod 755)
- [ ] 18.8. Проверить SSL (бесплатный Let's Encrypt в ispmanager)
- [ ] 18.9. Добавить Cron (если нужно): очистка старых токенов раз в сутки
- [ ] 18.10. Тестовый прогон на сервере

---

## Ключевые отличия от Laravel-версии

| Аспект | Было (Laravel) | Стало (чистый PHP) |
|--------|---------------|-------------------|
| Фреймворк | Laravel 11 | Свой микро-каркас |
| CSS | Tailwind через Vite (нужен Node.js) | Tailwind через CDN (ничего не собирать) |
| JS | Alpine.js через npm | Alpine.js через CDN |
| Шаблоны | Blade | Обычные PHP-файлы (include) |
| ORM | Eloquent | Свои модели + PDO |
| Миграции | php artisan migrate | SQL-файл через phpMyAdmin |
| Composer | На сервере | Локально (или вообще без него) |
| Деплой | CI/CD GitHub Actions | SSH: git pull или FTP-загрузка |
| Очереди | queue:work | Не нужны (sync) |
| Node.js на сервере | Нужен для сборки | Не нужен вообще |
