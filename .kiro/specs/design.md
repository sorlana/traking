# Дизайн-документ: Система Traking

## 1. Обзор архитектуры

### 1.1. Общая схема

```
┌─────────────────────────────────────────────────────┐
│                    Клиент (Браузер / PWA)            │
│  ┌───────────┐  ┌──────────┐  ┌─────────────────┐  │
│  │ HTML/CSS  │  │ Alpine.js│  │ Service Worker  │  │
│  │ (Blade)   │  │ (AJAX)   │  │ (PWA)           │  │
│  └───────────┘  └──────────┘  └─────────────────┘  │
└─────────────────────────┬───────────────────────────┘
                          │ HTTP/HTTPS
┌─────────────────────────┴───────────────────────────┐
│                   Веб-сервер (Nginx/Apache)          │
└─────────────────────────┬───────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────┐
│                  Laravel Application                  │
│  ┌────────────┐ ┌────────────┐ ┌────────────────┐  │
│  │ Routes     │ │ Middleware │ │ Controllers    │  │
│  └────────────┘ └────────────┘ └────────────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────────┐  │
│  │ Models     │ │ Services   │ │ Policies       │  │
│  └────────────┘ └────────────┘ └────────────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────────┐  │
│  │ Events     │ │ Listeners  │ │ Notifications  │  │
│  └────────────┘ └────────────┘ └────────────────┘  │
└─────────────────────────┬───────────────────────────┘
                          │
┌─────────────────────────┴───────────────────────────┐
│                  MySQL 8+ Database                    │
└──────────────────────────────────────────────────────┘
```

### 1.2. Выбор технологий

| Компонент | Технология | Обоснование |
|-----------|-----------|-------------|
| Backend | Чистый PHP 8.2+ | Работает на любом shared-хостинге, минимум зависимостей |
| Архитектура | Свой микро-каркас (MVC) | Контроль, простота, без лишних абстракций |
| База данных | MySQL 5.7+ / 8+ | Есть на любом хостинге, CTE для деревьев |
| Шаблоны | PHP-файлы (include) | Нативно, быстро, без зависимостей |
| JS-интерактивность | Alpine.js (CDN) | Лёгкий, без сборки |
| AJAX | Fetch API | Обновление комментариев, статусов, уведомлений |
| CSS | Tailwind CSS (CDN) | Без Node.js, без сборки на сервере |
| PWA | Ручной service-worker.js | Простой, без Workbox |
| Файловое хранилище | Папка storage/uploads/ | Простота, защита через .htaccess |

> **Принцип:** Ни Node.js, ни Composer на сервере не требуются. Всё работает из коробки на стандартном хостинге с PHP + MySQL.

## 2. Структура проекта (чистый PHP)

```
traking/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── UserController.php
│   │   ├── ProjectController.php
│   │   ├── TaskController.php
│   │   ├── CommentController.php
│   │   ├── FileController.php
│   │   └── NotificationController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── AuthToken.php
│   │   ├── Project.php
│   │   ├── ProjectUser.php
│   │   ├── ProjectDocument.php
│   │   ├── Task.php
│   │   ├── TaskParticipant.php
│   │   ├── TaskComment.php
│   │   ├── TaskFile.php
│   │   ├── TaskLink.php
│   │   ├── Notification.php
│   │   └── ActivityLog.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── TaskTreeService.php
│   │   ├── NotificationService.php
│   │   └── ActivityLogService.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   ├── ProjectAccessMiddleware.php
│   │   └── CsrfMiddleware.php
│   └── Helpers/
│       ├── Router.php
│       ├── View.php
│       ├── Database.php
│       ├── Request.php
│       ├── Response.php
│       ├── Session.php
│       ├── Auth.php
│       └── Validator.php
├── config/
│   ├── app.php
│   ├── database.php
│   └── routes.php
├── public/
│   ├── index.php              (единая точка входа)
│   ├── .htaccess
│   ├── manifest.json
│   ├── service-worker.js
│   ├── icons/
│   │   ├── icon-192x192.png
│   │   └── icon-512x512.png
│   └── assets/
│       ├── css/
│       │   └── app.css        (свои стили поверх Tailwind CDN)
│       ├── js/
│       │   └── app.js         (AJAX, уведомления, интерактивность)
│       └── img/
├── views/
│   ├── layouts/
│   │   └── app.php            (главный layout: head, nav, footer)
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   ├── admin.php
│   │   ├── manager.php
│   │   └── executor.php
│   ├── projects/
│   │   ├── index.php
│   │   ├── show.php
│   │   └── form.php
│   ├── tasks/
│   │   ├── index.php
│   │   ├── show.php
│   │   ├── tree.php
│   │   └── form.php
│   ├── notifications/
│   │   └── index.php
│   ├── admin/
│   │   └── users/
│   │       ├── index.php
│   │       └── form.php
│   └── components/
│       ├── comments.php
│       ├── file-upload.php
│       ├── notification-bell.php
│       └── task-tree-item.php
├── database/
│   ├── schema.sql
│   └── seed.sql
├── storage/
│   ├── uploads/
│   │   └── projects/
│   └── logs/
├── .htaccess                   (редирект в public/)
└── .gitignore
```

## 3. Модели данных и связи

### 3.1. ER-диаграмма (текстовое описание)

```
User (1) ──── (M) ProjectUser (M) ──── (1) Project
User (1) ──── (M) TaskParticipant (M) ──── (1) Task
User (1) ──── (M) TaskComment
User (1) ──── (M) Notification
User (1) ──── (M) ActivityLog

Project (1) ──── (M) Task
Project (1) ──── (M) ProjectDocument
Project (1) ──── (1) ProjectStatus

Task (1) ──── (M) Task (self-referencing via parent_id)
Task (1) ──── (M) TaskComment
Task (1) ──── (M) TaskFile
Task (1) ──── (M) TaskLink
Task (1) ──── (1) TaskStatus
```

### 3.2. Ключевые модели

#### User
```php
// Класс app/Models/User.php
// Методы:
findByLogin($login): ?User
findById($id): ?User
getRole(): Role
getProjects(): array
verifyPassword($password): bool
isAdmin(): bool
isManager(): bool
isExecutor(): bool
```

#### Project
```php
// Класс app/Models/Project.php
// Методы:
getUsers($role = null): array
getTasks($parentId = null): array
getDocuments(): array
getStatus(): ProjectStatus
hasUser($userId): bool
```

#### Task
```php
// Класс app/Models/Task.php
// Методы:
getParent(): ?Task
getChildren(): array
getComments(): array
getFiles(): array
getLinks(): array
getAssignee(): ?User
canBeClosed(): bool           // проверка открытых подзадач
getTree(): array              // рекурсивное дерево
isOverdue(): bool
```

## 4. Авторизация и безопасность

### 4.1. Схема аутентификации

```
1. POST /login (login, password)
2. Сервер: password_verify(password, user.password_hash)
3. Генерация: $token = bin2hex(random_bytes(32))
4. БД: auth_tokens.token_hash = hash('sha256', $token)
5. Cookie: Set-Cookie: auth_token=$token; HttpOnly; Secure; SameSite=Lax; Max-Age=7776000
6. Последующие запросы: Middleware проверяет cookie → ищет hash в auth_tokens → авторизует
```

### 4.2. Middleware

```
Все запросы через public/index.php
├── Router определяет маршрут
├── AuthMiddleware — проверка cookie → auth_tokens → авторизация
├── RoleMiddleware — проверка роли (admin, manager, executor)
├── ProjectAccessMiddleware — пользователь подключён к проекту
├── CsrfMiddleware — проверка CSRF-токена в POST-запросах
└── Controller обрабатывает запрос
```

### 4.3. Проверка прав (в контроллерах)

```php
// Пример проверки в контроллере:
public function show($id) {
    $task = Task::findById($id);
    $user = Auth::user();
    
    // Admin видит всё
    if ($user->isAdmin()) return $this->view('tasks/show', compact('task'));
    
    // Manager видит задачи своих проектов
    if ($user->isManager() && $task->getProject()->hasUser($user->id)) {
        return $this->view('tasks/show', compact('task'));
    }
    
    // Executor видит только свои задачи
    if ($user->isExecutor() && $task->assigned_to === $user->id) {
        return $this->view('tasks/show', compact('task'));
    }
    
    return Response::forbidden();
}
```

## 5. AJAX-эндпоинты

Для интерактивного обновления без перезагрузки страницы (обрабатываются тем же роутером, возвращают JSON):

```
POST   /ajax/tasks/{id}/status          — смена статуса
POST   /ajax/tasks/{id}/comments        — добавление комментария
POST   /ajax/tasks/{id}/files           — загрузка файла
GET    /ajax/notifications              — список уведомлений (JSON)
POST   /ajax/notifications/{id}/read    — отметить прочитанным
GET    /ajax/notifications/count        — количество непрочитанных
GET    /ajax/tasks/{id}/tree            — дерево подзадач (JSON)
POST   /ajax/tasks/{id}/reassign        — переназначение
```

Все AJAX-запросы проходят через те же middleware (auth, csrf, access).

## 6. Система уведомлений

### 6.1. Архитектура

```
Действие в контроллере (смена статуса, комментарий, назначение)
  → Вызов NotificationService::create(userId, type, data)
    → INSERT в таблицу notifications
    → При следующем AJAX-запросе пользователь получает уведомление
```

Без очередей, без фоновых процессов — уведомление создаётся синхронно в момент действия.

### 6.2. Типы уведомлений

| Тип | Получатель | Событие |
|-----|-----------|---------|
| task_assigned | Исполнитель | Назначение на задачу |
| subtask_created | Исполнитель | Новая подзадача |
| comment_added | Исполнитель/Руководитель | Новый комментарий |
| status_changed | Руководитель | Смена статуса на «Проверка» |
| task_returned | Исполнитель | Задача вернулась на правки |
| deadline_changed | Исполнитель | Изменился срок |
| deadline_approaching | Исполнитель/Руководитель | Срок задачи скоро истечёт |
| file_uploaded | Руководитель | Загружен файл |
| task_reassigned | Новый исполнитель | Переназначение |

### 6.3. Отображение

- Иконка колокольчика в шапке с badge-счётчиком
- AJAX-polling каждые 30 секунд (или WebSocket в будущем)
- Dropdown со списком последних уведомлений
- Страница /notifications — полный список с фильтрами

## 7. Дерево задач

### 7.1. Хранение

Adjacency List (parent_id) — простая и понятная модель.

### 7.2. Получение дерева

```sql
-- Рекурсивный CTE (MySQL 8+)
WITH RECURSIVE task_tree AS (
  SELECT *, 0 as depth FROM tasks WHERE id = :root_task_id
  UNION ALL
  SELECT t.*, tt.depth + 1
  FROM tasks t
  JOIN task_tree tt ON t.parent_id = tt.id
)
SELECT * FROM task_tree ORDER BY depth, created_at;
```

### 7.3. Закрытие задачи

```php
// TaskService::close(Task $task)
if ($task->children()->whereNot('status_id', closedStatusId)->exists()) {
    throw new CannotCloseTaskException('Есть незакрытые подзадачи');
}
$task->update(['status_id' => closedStatusId, 'closed_at' => now()]);
```

## 8. Загрузка файлов

### 8.1. Структура хранения
```
storage/app/uploads/projects/{project_id}/tasks/{task_id}/{filename}
```

### 8.2. Ограничения
- Максимальный размер: 50 MB
- Разрешённые типы: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, zip, rar, mp4, mov
- Максимум файлов на задачу: 50

### 8.3. Безопасность
- Файлы хранятся вне public/
- Отдача через контроллер с проверкой прав
- Генерация уникального имени (uuid + расширение)

## 9. Адаптивная вёрстка и PWA

### 9.1. Breakpoints (Tailwind)
- `sm`: 640px (телефон)
- `md`: 768px (планшет)
- `lg`: 1024px (десктоп)
- `xl`: 1280px (широкий десктоп)

### 9.2. PWA-конфигурация

```json
// manifest.json
{
  "name": "Traking — Управление проектами",
  "short_name": "Traking",
  "start_url": "/",
  "display": "standalone",
  "theme_color": "#1e40af",
  "background_color": "#ffffff",
  "icons": [...]
}
```

### 9.3. Service Worker
- Кэширование статических ресурсов (CSS, JS, иконки)
- Network-first для API-запросов
- Offline-fallback страница

## 10. Безопасность

| Угроза | Защита |
|--------|--------|
| SQL Injection | PDO prepared statements, параметризованные запросы |
| XSS | htmlspecialchars() при выводе, CSP-заголовки |
| CSRF | Токен в скрытом поле каждой формы + проверка в middleware |
| Brute Force | Счётчик попыток по IP (5 попыток/минуту), блокировка на 15 мин |
| File Upload | Проверка MIME-типа, размера, хранение вне public/ |
| Directory Traversal | .htaccess deny на app/, config/, storage/, database/ |
| Session Hijacking | HttpOnly + Secure cookies, привязка токена к IP/UA |
| Unauthorized Access | Проверка прав в каждом контроллере + middleware |

## 11. Развёртывание (Hostiman shared-хостинг)

### 11.1. Минимальные требования
- PHP 8.x (выбирается в ispmanager)
- MySQL (создаётся через ispmanager)
- mod_rewrite включён
- SSL — бесплатный Let's Encrypt через ispmanager

### 11.2. Этапы деплоя
1. Создать БД и пользователя в ispmanager → phpMyAdmin
2. Импортировать `database/schema.sql` + `database/seed.sql`
3. Загрузить файлы: SSH (`git clone`) или FTP
4. В ispmanager: настроить document root домена → `public/`
5. Отредактировать `config/database.php` с реальными данными
6. Проверить `.htaccess` (mod_rewrite)
7. Установить права: `chmod 755 storage/` и `chmod 755 storage/uploads/`
8. Включить SSL в ispmanager
9. Проверить работу приложения
10. Добавить Cron (опционально): очистка истёкших токенов раз в сутки

### 11.3. Обновление кода
```bash
# Через SSH:
cd /путь/к/сайту
git pull origin main
```
Или через FTP — загрузить изменённые файлы.
