<?php
/**
 * ProjectController — Контроллер управления проектами
 *
 * Функции: список проектов, карточка, создание, редактирование,
 * управление участниками, документами, смена статуса.
 *
 * Доступ: авторизованные пользователи с проверкой прав через ProjectAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Response;
use Helpers\Session;
use Middleware\ProjectAccessMiddleware;
use Models\Project;
use Models\ProjectUser;
use Models\ProjectDocument;

class ProjectController extends Controller
{
    private Project $projectModel;
    private ProjectUser $projectUserModel;
    private ProjectDocument $documentModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->projectUserModel = new ProjectUser();
        $this->documentModel = new ProjectDocument();
    }

    /**
     * Список проектов
     * Для admin — все проекты, для manager/executor — только свои
     * GET /projects
     */
    public function index(): void
    {
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $db = Database::getInstance();

        // Параметры фильтрации
        $statusFilter = $_GET['status'] ?? '';
        $managerFilter = $_GET['manager'] ?? '';
        $executorFilter = $_GET['executor'] ?? '';
        $deadlineFilter = $_GET['deadline'] ?? '';

        // Базовый запрос
        if ($roleId === 1) {
            // Admin видит все проекты
            $sql = "SELECT p.*, ps.name as status_name, ps.code as status_code,
                           u.name as creator_name
                    FROM projects p
                    JOIN project_statuses ps ON p.status_id = ps.id
                    JOIN users u ON p.created_by = u.id
                    WHERE 1=1";
            $params = [];
        } else {
            // Manager/Executor видят только свои проекты
            $sql = "SELECT p.*, ps.name as status_name, ps.code as status_code,
                           u.name as creator_name, pu.project_role
                    FROM projects p
                    JOIN project_statuses ps ON p.status_id = ps.id
                    JOIN users u ON p.created_by = u.id
                    JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                    WHERE 1=1";
            $params = [(int) $user['id']];
        }

        // Фильтр по статусу
        if ($statusFilter !== '') {
            $sql .= " AND ps.code = ?";
            $params[] = $statusFilter;
        }

        // Фильтр по руководителю
        if ($managerFilter !== '') {
            $sql .= " AND p.id IN (SELECT project_id FROM project_users WHERE user_id = ? AND project_role = 'manager')";
            $params[] = (int) $managerFilter;
        }

        // Фильтр по исполнителю
        if ($executorFilter !== '') {
            $sql .= " AND p.id IN (SELECT project_id FROM project_users WHERE user_id = ? AND project_role = 'executor')";
            $params[] = (int) $executorFilter;
        }

        // Фильтр по сроку (просроченные)
        if ($deadlineFilter === 'overdue') {
            $sql .= " AND p.deadline < CURDATE() AND ps.code != 'closed'";
        } elseif ($deadlineFilter === 'week') {
            $sql .= " AND p.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }

        $sql .= " ORDER BY p.created_at DESC";

        $projects = $db->fetchAll($sql, $params);

        // Для каждого проекта получаем статистику задач
        foreach ($projects as &$project) {
            $stats = $this->projectModel->getTaskStats((int) $project['id']);
            $project['task_total'] = $stats['total'];
            $project['task_open'] = $stats['open'];

            // Получаем руководителей проекта
            $managers = $this->projectModel->getUsers((int) $project['id'], 'manager');
            $project['managers'] = $managers;
        }
        unset($project);

        // Данные для фильтров
        $statuses = $db->fetchAll("SELECT * FROM project_statuses ORDER BY sort_order");
        $managers = $db->fetchAll("SELECT u.id, u.name FROM users u WHERE u.role_id IN (1, 2) AND u.status = 'active' ORDER BY u.name");
        $executors = $db->fetchAll("SELECT u.id, u.name FROM users u WHERE u.role_id = 3 AND u.status = 'active' ORDER BY u.name");

        $this->view('projects/index', [
            'title' => 'Проекты — Traking',
            'projects' => $projects,
            'statuses' => $statuses,
            'managers' => $managers,
            'executors' => $executors,
            'filters' => [
                'status' => $statusFilter,
                'manager' => $managerFilter,
                'executor' => $executorFilter,
                'deadline' => $deadlineFilter,
            ],
        ]);
    }

    /**
     * Карточка проекта — полная информация
     * GET /projects/{id}
     */
    public function show(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        // Проверка доступа
        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        // Получаем связанные данные
        $status = $this->projectModel->getStatus((int) $id);
        $users = $this->projectModel->getUsers((int) $id);
        $documents = $this->projectModel->getDocuments((int) $id);
        $tasks = $this->projectModel->getTasks((int) $id, null); // Корневые задачи
        $taskStats = $this->projectModel->getTaskStats((int) $id);

        // Данные для формы добавления участника
        $db = Database::getInstance();
        $allUsers = $db->fetchAll("SELECT id, name, login, role_id FROM users WHERE status = 'active' ORDER BY name");
        $statuses = $db->fetchAll("SELECT * FROM project_statuses ORDER BY sort_order");

        // Получаем создателя проекта
        $creator = $db->fetch("SELECT name FROM users WHERE id = ?", [$project['created_by']]);

        $this->view('projects/show', [
            'title' => e($project['title']) . ' — Traking',
            'project' => $project,
            'status' => $status,
            'users' => $users,
            'documents' => $documents,
            'tasks' => $tasks,
            'taskStats' => $taskStats,
            'allUsers' => $allUsers,
            'statuses' => $statuses,
            'creator' => $creator,
        ]);
    }

    /**
     * Форма создания проекта
     * GET /projects/create
     */
    public function create(): void
    {
        // Только admin и manager могут создавать проекты
        $this->authorize(can('create_project'), 'Недостаточно прав для создания проекта');

        $db = Database::getInstance();
        $statuses = $db->fetchAll("SELECT * FROM project_statuses ORDER BY sort_order");

        $this->view('projects/form', [
            'title' => 'Создание проекта — Traking',
            'project' => null,
            'statuses' => $statuses,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Сохранение нового проекта (POST)
     * POST /projects/create
     */
    public function store(): void
    {
        $this->authorize(can('create_project'), 'Недостаточно прав для создания проекта');

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'deadline' => $_POST['deadline'] ?? null,
            'status_id' => $_POST['status_id'] ?? '',
        ];

        // Валидация
        $errors = $this->validateProjectData($data);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/projects/create');
            return;
        }

        // Устанавливаем создателя
        $data['created_by'] = Auth::id();

        // Пустой deadline -> null
        if (empty($data['deadline'])) {
            $data['deadline'] = null;
        }

        // Пустое описание -> null
        if ($data['description'] === '') {
            $data['description'] = null;
        }

        // Создаём проект
        $projectId = $this->projectModel->create($data);

        // Автоматически добавляем создателя в project_users
        $userRole = Auth::isAdmin() ? 'manager' : (Auth::isManager() ? 'manager' : 'executor');
        $this->projectUserModel->addUser((int) $projectId, Auth::id(), $userRole);

        Session::flash('success', 'Проект успешно создан');
        $this->redirect('/projects/' . $projectId);
    }

    /**
     * Форма редактирования проекта
     * GET /projects/{id}/edit
     */
    public function edit(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        // Проверка доступа
        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('edit_project', (int) $id), 'Недостаточно прав для редактирования проекта');

        $db = Database::getInstance();
        $statuses = $db->fetchAll("SELECT * FROM project_statuses ORDER BY sort_order");

        $this->view('projects/form', [
            'title' => 'Редактирование проекта — Traking',
            'project' => $project,
            'statuses' => $statuses,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Обновление проекта (POST)
     * POST /projects/{id}/edit
     */
    public function update(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('edit_project', (int) $id), 'Недостаточно прав для редактирования проекта');

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'deadline' => $_POST['deadline'] ?? null,
            'status_id' => $_POST['status_id'] ?? '',
        ];

        // Валидация
        $errors = $this->validateProjectData($data);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect("/projects/{$id}/edit");
            return;
        }

        // Пустой deadline -> null
        if (empty($data['deadline'])) {
            $data['deadline'] = null;
        }

        // Пустое описание -> null
        if ($data['description'] === '') {
            $data['description'] = null;
        }

        $this->projectModel->update((int) $id, $data);

        Session::flash('success', 'Проект обновлён');
        $this->redirect('/projects/' . $id);
    }

    /**
     * Добавление участника в проект
     * POST /projects/{id}/add-user
     */
    public function addUser(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('edit_project', (int) $id), 'Недостаточно прав');

        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['project_role'] ?? 'executor';

        if ($userId <= 0) {
            Session::flash('error', 'Выберите пользователя');
            $this->redirect("/projects/{$id}");
            return;
        }

        // Проверяем, что пользователь ещё не в проекте
        if ($this->projectModel->hasUser((int) $id, $userId)) {
            Session::flash('error', 'Пользователь уже является участником проекта');
            $this->redirect("/projects/{$id}");
            return;
        }

        // Валидация роли
        if (!in_array($role, ['manager', 'executor'])) {
            $role = 'executor';
        }

        $this->projectUserModel->addUser((int) $id, $userId, $role);

        Session::flash('success', 'Участник добавлен в проект');
        $this->redirect("/projects/{$id}");
    }

    /**
     * Удаление участника из проекта
     * POST /projects/{id}/remove-user
     */
    public function removeUser(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('edit_project', (int) $id), 'Недостаточно прав');

        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            Session::flash('error', 'Не указан пользователь');
            $this->redirect("/projects/{$id}");
            return;
        }

        // Нельзя удалить создателя проекта
        if ($userId === (int) $project['created_by']) {
            Session::flash('error', 'Нельзя удалить создателя проекта из участников');
            $this->redirect("/projects/{$id}");
            return;
        }

        $this->projectUserModel->removeUser((int) $id, $userId);

        Session::flash('success', 'Участник удалён из проекта');
        $this->redirect("/projects/{$id}");
    }

    /**
     * Загрузка документа/ссылки к проекту
     * POST /projects/{id}/add-document
     */
    public function addDocument(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $title = trim($_POST['doc_title'] ?? '');
        $type = $_POST['document_type'] ?? 'other';
        $externalUrl = trim($_POST['external_url'] ?? '');
        $comment = trim($_POST['doc_comment'] ?? '');

        // Валидация
        if ($title === '') {
            Session::flash('error', 'Укажите название документа');
            $this->redirect("/projects/{$id}");
            return;
        }

        $allowedTypes = ['kp', 'brief', 'tz', 'contract', 'estimate', 'presentation', 'figma_link', 'cloud_link', 'other'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'other';
        }

        $filePath = null;

        // Обработка загрузки файла
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_file'];
            $maxSize = 50 * 1024 * 1024; // 50 MB

            if ($file['size'] > $maxSize) {
                Session::flash('error', 'Файл слишком большой (максимум 50 МБ)');
                $this->redirect("/projects/{$id}");
                return;
            }

            // Разрешённые расширения
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'mp4', 'mov'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt)) {
                Session::flash('error', 'Недопустимый тип файла');
                $this->redirect("/projects/{$id}");
                return;
            }

            // Генерируем уникальное имя
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/projects/' . $id;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $filePath = 'projects/' . $id . '/' . $filename;
            } else {
                Session::flash('error', 'Ошибка загрузки файла');
                $this->redirect("/projects/{$id}");
                return;
            }
        }

        // Создаём документ
        $this->documentModel->create([
            'project_id' => (int) $id,
            'title' => $title,
            'document_type' => $type,
            'file_path' => $filePath,
            'external_url' => $externalUrl ?: null,
            'comment' => $comment ?: null,
            'uploaded_by' => Auth::id(),
        ]);

        Session::flash('success', 'Документ добавлен');
        $this->redirect("/projects/{$id}");
    }

    /**
     * Смена статуса проекта
     * POST /projects/{id}/status
     */
    public function changeStatus(string $id): void
    {
        $project = $this->projectModel->find((int) $id);

        if (!$project) {
            Response::notFound('Проект не найден');
            return;
        }

        if (!ProjectAccessMiddleware::check((int) $id)) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('edit_project', (int) $id), 'Недостаточно прав');

        $statusId = (int) ($_POST['status_id'] ?? 0);

        if ($statusId <= 0) {
            Session::flash('error', 'Выберите статус');
            $this->redirect("/projects/{$id}");
            return;
        }

        // Проверяем что статус существует
        $db = Database::getInstance();
        $status = $db->fetch("SELECT * FROM project_statuses WHERE id = ?", [$statusId]);

        if (!$status) {
            Session::flash('error', 'Статус не найден');
            $this->redirect("/projects/{$id}");
            return;
        }

        $updateData = ['status_id' => $statusId];

        // Если статус «closed» — ставим дату закрытия
        if ($status['code'] === 'closed') {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
        } else {
            $updateData['closed_at'] = null;
        }

        $this->projectModel->update((int) $id, $updateData);

        Session::flash('success', 'Статус проекта изменён на «' . $status['name'] . '»');
        $this->redirect("/projects/{$id}");
    }

    // ========================================================================
    // Приватные методы
    // ========================================================================

    /**
     * Валидация данных проекта
     *
     * @param array $data Данные формы
     * @return array Массив ошибок
     */
    private function validateProjectData(array $data): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'][] = 'Поле «Название» обязательно для заполнения';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors['title'][] = 'Название не должно превышать 255 символов';
        }

        if ($data['status_id'] === '' || $data['status_id'] === '0') {
            $errors['status_id'][] = 'Выберите статус проекта';
        }

        if (!empty($data['deadline']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['deadline'])) {
            $errors['deadline'][] = 'Некорректный формат даты';
        }

        return $errors;
    }
}
