# Traking — Система управления проектами и задачами

Веб-приложение (PWA) для управления проектами, задачами, правками и коммуникацией между руководителями и исполнителями.

## Возможности

- Управление проектами: создание, участники, документы, статусы
- Иерархия задач: дерево подзадач, приоритеты, дедлайны
- Комментарии и файлы: обсуждение в задачах, загрузка документов
- Уведомления: в реальном времени (polling), колокольчик в шапке
- Ролевая система: Админ, Руководитель, Исполнитель
- PWA: работа офлайн, установка на устройство
- Адаптивная вёрстка: мобильные устройства, планшеты, десктоп

## Требования

- PHP 8.0+
- MySQL 5.7+ (рекомендуется 8.0)
- Apache с mod_rewrite
- SSL-сертификат (для PWA)

## Установка

### 1. Загрузка файлов

```bash
git clone https://github.com/sorlana/traking.git
```

Или загрузите по FTP.

### 2. Настройка базы данных

1. Создайте базу данных MySQL (кодировка `utf8mb4_unicode_ci`)
2. Импортируйте схему: `database/schema.sql`
3. Импортируйте начальные данные: `database/seed.sql`

### 3. Конфигурация

Создайте файл `config/database.php`:

```php
<?php
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'ваша_база',
    'username' => 'ваш_пользователь',
    'password' => 'ваш_пароль',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

### 4. Document root

В ispmanager настройте document root домена на папку `public/`.

### 5. Права доступа

```bash
chmod 755 storage/
chmod 755 storage/uploads/
chmod 755 storage/logs/
```

### 6. SSL

Включите SSL (Let's Encrypt) в ispmanager для корректной работы PWA.

### 7. Первый вход

- Логин: `admin`
- Пароль: `password`
- **Сразу смените пароль!**

## Структура проекта

```
traking/
├── app/                    # Логика приложения
│   ├── Controllers/        # Контроллеры (MVC)
│   ├── Models/             # Модели данных (PDO)
│   ├── Services/           # Сервисы (бизнес-логика)
│   ├── Middleware/         # Middleware (auth, CSRF, роли)
│   └── Helpers/            # Хелперы (Router, View, Auth, DB)
├── config/                 # Конфигурация
│   ├── app.php             # Настройки приложения
│   ├── database.php        # Подключение к БД
│   └── routes.php          # Маршруты
├── database/               # SQL-файлы
│   ├── schema.sql          # Структура таблиц
│   └── seed.sql            # Начальные данные
├── public/                 # Document root (точка входа)
│   ├── index.php           # Единая точка входа
│   ├── .htaccess           # Rewrite rules
│   ├── assets/             # CSS, JS, изображения
│   ├── manifest.json       # PWA-манифест
│   ├── service-worker.js   # Service Worker
│   └── offline.html        # Страница офлайн-режима
├── views/                  # Шаблоны (PHP)
│   ├── layouts/            # Базовые layout-ы
│   ├── auth/               # Авторизация
│   ├── dashboard/          # Дашборд (по ролям)
│   ├── projects/           # Проекты
│   ├── tasks/              # Задачи
│   ├── notifications/      # Уведомления
│   └── admin/              # Админ-панель
└── storage/                # Загрузки и логи (закрыт .htaccess)
    ├── uploads/            # Файлы пользователей
    └── logs/               # Логи ошибок
```

## Обновление

```bash
cd /путь/к/сайту
git pull origin main
```

При изменении структуры БД — применить новые SQL-миграции вручную через phpMyAdmin.

## Безопасность

- Все SQL-запросы через подготовленные выражения (PDO prepare)
- Экранирование вывода: `htmlspecialchars()` через хелпер `e()`
- CSRF-токены во всех POST-формах
- Валидация загружаемых файлов (MIME-тип, размер до 50 МБ)
- Rate limiting на странице входа (защита от брутфорса)
- `.htaccess`: запрет прямого доступа к `app/`, `config/`, `storage/`, `database/`
- Cookie авторизации: HttpOnly, Secure, SameSite=Lax
- Пароли: bcrypt (`password_hash` / `password_verify`)

## Стек технологий

- PHP 8.x (без фреймворка, свой MVC-каркас)
- MySQL 5.7+
- Tailwind CSS (CDN)
- Alpine.js (CDN)
- PWA (Service Worker + manifest.json)

## Лицензия

Proprietary. Все права защищены.
