<?php
/**
 * UserController — CRUD-контроллер управления пользователями
 *
 * Доступен только для администраторов (middleware: auth + admin).
 * Функции: список с фильтрами, создание, редактирование,
 * активация/деактивация, сброс пароля.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Request;
use Helpers\Response;
use Helpers\Session;
use Helpers\Validator;
use Models\User;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Список пользователей с фильтрами и поиском
     * GET /admin/users?role=&status=&search=
     */
    public function index(): void
    {
        $db = Database::getInstance();

        // Получаем параметры фильтрации
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $search = trim($_GET['search'] ?? '');

        // Строим запрос с фильтрами
        $sql = "SELECT u.*, r.name as role_name, r.code as role_code 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE 1=1";
        $params = [];

        if ($role !== '') {
            $sql .= " AND r.code = ?";
            $params[] = $role;
        }

        if ($status !== '') {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.login LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $sql .= " ORDER BY u.created_at DESC";

        $users = $db->fetchAll($sql, $params);

        // Получаем список ролей для фильтра
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");

        $this->view('admin/users/index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'role' => $role,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Форма создания нового пользователя
     * GET /admin/users/create
     */
    public function create(): void
    {
        $db = Database::getInstance();
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");

        $this->view('admin/users/form', [
            'user' => null,
            'roles' => $roles,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Создание нового пользователя (POST)
     * Генерирует случайный пароль, хэширует и создаёт запись
     * POST /admin/users/create
     */
    public function store(): void
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'login' => trim($_POST['login'] ?? ''),
            'role_id' => $_POST['role_id'] ?? '',
            'status' => $_POST['status'] ?? 'active',
        ];

        // Валидация
        $errors = $this->validateUserData($data);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/admin/users/create');
            return;
        }

        // Генерация случайного пароля (8 символов)
        $password = $this->generatePassword(8);
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        // Создаём пользователя
        $this->userModel->create($data);

        // Flash-сообщение с паролем для отображения администратору
        Session::flash('success', "Пользователь «{$data['login']}» создан. Пароль: {$password}");
        $this->redirect('/admin/users');
    }

    /**
     * Форма редактирования пользователя
     * GET /admin/users/{id}/edit
     */
    public function edit(string $id): void
    {
        $user = $this->userModel->find((int) $id);

        if (!$user) {
            Response::notFound('Пользователь не найден');
            return;
        }

        $db = Database::getInstance();
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id");

        $this->view('admin/users/form', [
            'user' => $user,
            'roles' => $roles,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Обновление данных пользователя (POST)
     * POST /admin/users/{id}/edit
     */
    public function update(string $id): void
    {
        $user = $this->userModel->find((int) $id);

        if (!$user) {
            Response::notFound('Пользователь не найден');
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'login' => trim($_POST['login'] ?? ''),
            'role_id' => $_POST['role_id'] ?? '',
            'status' => $_POST['status'] ?? 'active',
        ];

        // Валидация (с учётом текущего пользователя для уникальности)
        $errors = $this->validateUserData($data, (int) $id);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect("/admin/users/{$id}/edit");
            return;
        }

        // Обновляем данные (без пароля)
        $this->userModel->update((int) $id, $data);

        Session::flash('success', "Пользователь «{$data['login']}» обновлён");
        $this->redirect('/admin/users');
    }

    /**
     * Переключение статуса пользователя (active ↔ inactive)
     * POST /admin/users/{id}/toggle-status
     */
    public function toggleStatus(string $id): void
    {
        $user = $this->userModel->find((int) $id);

        if (!$user) {
            Response::notFound('Пользователь не найден');
            return;
        }

        // Запрет деактивации самого себя
        if ((int) $id === Auth::id()) {
            Session::flash('error', 'Нельзя деактивировать свою учётную запись');
            $this->redirect('/admin/users');
            return;
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $this->userModel->update((int) $id, ['status' => $newStatus]);

        $statusLabel = $newStatus === 'active' ? 'активирован' : 'деактивирован';
        Session::flash('success', "Пользователь «{$user['login']}» {$statusLabel}");
        $this->redirect('/admin/users');
    }

    /**
     * Сброс пароля пользователя — генерация нового случайного пароля
     * POST /admin/users/{id}/reset-password
     */
    public function resetPassword(string $id): void
    {
        $user = $this->userModel->find((int) $id);

        if (!$user) {
            Response::notFound('Пользователь не найден');
            return;
        }

        // Генерация нового пароля
        $password = $this->generatePassword(8);
        $this->userModel->update((int) $id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        Session::flash('success', "Пароль пользователя «{$user['login']}» сброшен. Новый пароль: {$password}");
        $this->redirect('/admin/users');
    }

    // ========================================================================
    // Приватные методы
    // ========================================================================

    /**
     * Валидация данных пользователя
     *
     * @param array $data Данные формы
     * @param int|null $excludeId ID пользователя для исключения при проверке уникальности
     * @return array Массив ошибок
     */
    private function validateUserData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $db = Database::getInstance();

        // Обязательные поля
        if ($data['name'] === '') {
            $errors['name'][] = 'Поле «Имя» обязательно для заполнения';
        }
        if ($data['email'] === '') {
            $errors['email'][] = 'Поле «Email» обязательно для заполнения';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Введите корректный email-адрес';
        }
        if ($data['login'] === '') {
            $errors['login'][] = 'Поле «Логин» обязательно для заполнения';
        } elseif (mb_strlen($data['login']) < 3) {
            $errors['login'][] = 'Логин должен содержать не менее 3 символов';
        }
        if ($data['role_id'] === '') {
            $errors['role_id'][] = 'Выберите роль';
        }

        // Уникальность email
        if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$data['email']];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $existing = $db->fetch($sql, $params);
            if ($existing) {
                $errors['email'][] = 'Пользователь с таким email уже существует';
            }
        }

        // Уникальность login
        if ($data['login'] !== '') {
            $sql = "SELECT id FROM users WHERE login = ?";
            $params = [$data['login']];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $existing = $db->fetch($sql, $params);
            if ($existing) {
                $errors['login'][] = 'Пользователь с таким логином уже существует';
            }
        }

        return $errors;
    }

    /**
     * Генерация случайного пароля
     *
     * @param int $length Длина пароля
     * @return string Сгенерированный пароль
     */
    private function generatePassword(int $length = 8): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';
        $password = '';
        $max = mb_strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }
}
