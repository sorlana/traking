<?php
/**
 * TaskController — Контроллер управления задачами
 *
 * Функции: список задач, карточка, создание, редактирование,
 * смена статуса, закрытие, переназначение, дерево подзадач.
 *
 * Доступ: авторизованные пользователи с проверкой прав через TaskAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Response;
use Helpers\Session;
use Middleware\TaskAccessMiddleware;
use Middleware\ProjectAccessMiddleware;
use Models\Task;
use Models\Project;
use Services\TaskTreeService;
use Services\NotificationService;
use Services\ActivityLogService;

class TaskController extends Controller
{
    private Task $taskModel;
    private Project $projectModel;
    private TaskTreeService $treeService;
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->treeService = new TaskTreeService();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * Список задач пользователя (или проекта если ?project_id=X)
     * GET /tasks
     */
    public function index(): void
    {
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $db = Database::getInstance();

        // Параметры фильтрации
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'deadline' => $_GET['deadline'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'overdue' => $_GET['overdue'] ?? '',
        ];

        $projectId = !empty($filters['project_id']) ? (int) $filters['project_id'] : null;

        // Если указан проект — показываем задачи проекта
        if ($projectId) {
            if (!ProjectAccessMiddleware::check($projectId)) {
                Response::forbidden('Нет доступа к проекту');
                return;
            }
            $tasks = $this->taskModel->getByProject($projectId, $filters);
            $project = $this->projectModel->find($projectId);
        } else {
            // Для executor — только его задачи, для admin/manager — все доступные
            if ($roleId === 3) {
                $tasks = $this->taskModel->getUserTasks((int) $user['id'], $filters);
            } else {
                // Admin/Manager видят все задачи из своих проектов
                $sql = "SELECT t.*, ts.name as status_name, ts.code as status_code,
                               u.name as assigned_name, p.title as project_title,
                               creator.name as creator_name
                        FROM tasks t
                        JOIN task_statuses ts ON t.status_id = ts.id
                        LEFT JOIN users u ON t.assigned_to = u.id
                        LEFT JOIN users creator ON t.created_by = creator.id
                        JOIN projects p ON t.project_id = p.id";

                $params = [];

                // Для manager — только проекты, к которым подключён
                if ($roleId === 2) {
                    $sql .= " JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?";
                    $params[] = (int) $user['id'];
                }

                $sql .= " WHERE 1=1";

                // Применяем фильтры
                if (!empty($filters['status'])) {
                    $sql .= " AND ts.code = ?";
                    $params[] = $filters['status'];
                }
                if (!empty($filters['priority'])) {
                    $sql .= " AND t.priority = ?";
                    $params[] = $filters['priority'];
                }
                if (!empty($filters['assigned_to'])) {
                    $sql .= " AND t.assigned_to = ?";
                    $params[] = (int) $filters['assigned_to'];
                }
                if (!empty($filters['deadline'])) {
                    if ($filters['deadline'] === 'overdue') {
                        $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
                    } elseif ($filters['deadline'] === 'week') {
                        $sql .= " AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    } elseif ($filters['deadline'] === 'today') {
                        $sql .= " AND t.deadline = CURDATE()";
                    }
                }
                if (!empty($filters['overdue'])) {
                    $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done')";
                }

                $sql .= " ORDER BY t.created_at DESC";
                $tasks = $db->fetchAll($sql, $params);
            }
            $project = null;
        }

        // Данные для фильтров
        $statuses = $db->fetchAll("SELECT * FROM task_statuses ORDER BY sort_order");
        $projects = [];
        if ($roleId === 1) {
            $projects = $db->fetchAll("SELECT id, title FROM projects ORDER BY title");
        } elseif ($roleId === 2) {
            $projects = $db->fetchAll(
                "SELECT p.id, p.title FROM projects p
                 JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                 ORDER BY p.title",
                [(int) $user['id']]
            );
        }

        // Исполнители для фильтра
        $executors = $db->fetchAll("SELECT id, name FROM users WHERE status = 'active' AND role_id != 1 ORDER BY name");

        $this->view('tasks/index', [
            'title' => 'Задачи — Traking',
            'tasks' => $tasks,
            'project' => $project,
            'statuses' => $statuses,
            'projects' => $projects,
            'executors' => $executors,
            'filters' => $filters,
        ]);
    }

    /**
     * Карточка задачи: описание, статус, комментарии, файлы, подзадачи
     * GET /tasks/{id}
     */
    public function show(string $id): void
    {
        $taskId = (int) $id;
        $db = Database::getInstance();

        // Сохраняем последнюю просмотренную задачу в сессии
        \Helpers\Session::set('last_task_id', $taskId);

        // Получаем задачу с расширенными данными
        $task = $db->fetch(
            "SELECT t.*, ts.name as status_name, ts.code as status_code,
                    u.name as assigned_name, creator.name as creator_name,
                    p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             LEFT JOIN users creator ON t.created_by = creator.id
             JOIN projects p ON t.project_id = p.id
             WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        // Проверка доступа
        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        // Получаем связанные данные
        $children = $this->taskModel->getChildren($taskId);
        $comments = $this->taskModel->getComments($taskId);
        $files = $this->taskModel->getFiles($taskId);
        $links = $this->taskModel->getLinks($taskId);

        // Подтягиваем файлы и ссылки к каждому комментарию (для чат-интерфейса)
        foreach ($comments as &$comment) {
            $comment['files'] = $db->fetchAll(
                "SELECT id, file_name, file_size, file_type FROM task_files WHERE comment_id = ?",
                [(int) $comment['id']]
            );
            $comment['links'] = $db->fetchAll(
                "SELECT id, url, title FROM task_links WHERE comment_id = ?",
                [(int) $comment['id']]
            );
        }
        unset($comment);

        // Получаем список прочитанных сообщений другими пользователями (для галочек)
        $readByOthers = [];
        try {
            $readRows = $db->fetchAll(
                "SELECT DISTINCT comment_id FROM message_reads 
                 WHERE comment_id IN (SELECT id FROM task_comments WHERE task_id = ?) AND user_id != ?",
                [$taskId, Auth::id()]
            );
            $readByOthers = array_map(fn($r) => (int) $r['comment_id'], $readRows);
        } catch (\Throwable $e) {}

        // Добавляем read_by_others к каждому комментарию
        foreach ($comments as &$comment) {
            $comment['read_by_others'] = in_array((int) $comment['id'], $readByOthers);
        }
        unset($comment);
        $parent = $this->taskModel->getParent($taskId);

        // История действий
        $activityLogModel = new \Models\ActivityLog();
        $activityLog = $activityLogModel->getByTask($taskId);

        // Статусы для выпадающего списка смены статуса
        $statuses = $db->fetchAll("SELECT * FROM task_statuses ORDER BY sort_order");

        // Участники проекта (для переназначения)
        $projectUsers = $this->projectModel->getUsers((int) $task['project_id']);

        // Проверка: можно ли закрыть
        $canClose = $this->treeService->canClose($taskId);

        $this->view('tasks/show', [
            'title' => e($task['title']) . ' — Traking',
            'task' => $task,
            'children' => $children,
            'comments' => $comments,
            'files' => $files,
            'links' => $links,
            'parent' => $parent,
            'statuses' => $statuses,
            'projectUsers' => $projectUsers,
            'canClose' => $canClose,
            'activityLog' => $activityLog,
        ]);
    }

    /**
     * Форма создания задачи
     * GET /tasks/create?project_id=X&parent_id=Y
     */
    public function create(): void
    {
        $db = Database::getInstance();
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);

        $projectId = (int) ($_GET['project_id'] ?? 0);
        $parentId = (int) ($_GET['parent_id'] ?? 0);

        // Если project_id не указан — нужно выбрать проект
        $project = null;
        $parentTask = null;

        if ($projectId > 0) {
            $project = $this->projectModel->find($projectId);
            if (!$project || !ProjectAccessMiddleware::check($projectId)) {
                Response::forbidden('Нет доступа к проекту');
                return;
            }
        }

        // Если parent_id указан — получаем родительскую задачу
        if ($parentId > 0) {
            $parentTask = $this->taskModel->find($parentId);
            if ($parentTask) {
                $projectId = (int) $parentTask['project_id'];
                $project = $this->projectModel->find($projectId);
            }
        }

        // Проверяем права на создание задач
        $this->authorize(can('create_task', $projectId ?: null), 'Недостаточно прав для создания задачи');

        // Получаем участников проекта для назначения исполнителя
        $projectUsers = $projectId > 0 ? $this->projectModel->getUsers($projectId) : [];

        // Статусы задач
        $statuses = $db->fetchAll("SELECT * FROM task_statuses ORDER BY sort_order");

        // Список проектов (если project_id не указан)
        $projects = [];
        if ($projectId === 0) {
            if ($roleId === 1) {
                $projects = $db->fetchAll("SELECT id, title FROM projects ORDER BY title");
            } else {
                $projects = $db->fetchAll(
                    "SELECT p.id, p.title FROM projects p
                     JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                     ORDER BY p.title",
                    [(int) $user['id']]
                );
            }
        }

        $this->view('tasks/form', [
            'title' => 'Создание задачи — Traking',
            'task' => null,
            'project' => $project,
            'parentTask' => $parentTask,
            'projectUsers' => $projectUsers,
            'statuses' => $statuses,
            'projects' => $projects,
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Сохранение новой задачи (POST)
     * POST /tasks/create
     */
    public function store(): void
    {
        $data = [
            'project_id' => (int) ($_POST['project_id'] ?? 0),
            'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status_id' => (int) ($_POST['status_id'] ?? 0),
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => $_POST['deadline'] ?? null,
            'assigned_to' => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null,
        ];

        // Проверяем доступ к проекту
        if ($data['project_id'] <= 0 || !ProjectAccessMiddleware::check($data['project_id'])) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('create_task', $data['project_id']), 'Недостаточно прав для создания задачи');

        // Валидация
        $errors = $this->validateTaskData($data);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $redirectUrl = '/tasks/create?project_id=' . $data['project_id'];
            if ($data['parent_id']) {
                $redirectUrl .= '&parent_id=' . $data['parent_id'];
            }
            $this->redirect($redirectUrl);
            return;
        }

        // Устанавливаем создателя
        $data['created_by'] = Auth::id();

        // Если status_id не указан — ставим «В работе» автоматически
        if (empty($data['status_id'])) {
            $db = Database::getInstance();
            $inProgressStatus = $db->fetch("SELECT id FROM task_statuses WHERE code = 'in_progress' LIMIT 1");
            $data['status_id'] = $inProgressStatus ? (int) $inProgressStatus['id'] : 1;
        }

        // Пустые значения → null
        if (empty($data['deadline'])) {
            $data['deadline'] = null;
        }
        if ($data['description'] === '') {
            $data['description'] = null;
        }

        // Создаём задачу
        $taskId = $this->taskModel->create($data);

        // Логируем создание задачи в историю
        $this->activityLogService->log(
            Auth::id(),
            $data['project_id'],
            (int) $taskId,
            'task_created',
            null,
            $data['title']
        );

        // Уведомляем назначенного исполнителя
        if ($data['assigned_to']) {
            $this->notificationService->notifyTaskAssigned((int) $taskId, $data['assigned_to']);
        }

        Session::flash('success', 'Задача успешно создана');

        // Если создали подзадачу — вернуть на родительскую задачу
        if ($data['parent_id']) {
            $this->redirect('/tasks/' . $data['parent_id']);
        } else {
            $this->redirect('/tasks/' . $taskId);
        }
    }

    /**
     * Форма редактирования задачи
     * GET /tasks/{id}/edit
     */
    public function edit(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        $this->authorize(can('create_task', (int) $task['project_id']), 'Недостаточно прав для редактирования');

        $db = Database::getInstance();
        $project = $this->projectModel->find((int) $task['project_id']);
        $projectUsers = $this->projectModel->getUsers((int) $task['project_id']);
        $statuses = $db->fetchAll("SELECT * FROM task_statuses ORDER BY sort_order");
        $parentTask = $task['parent_id'] ? $this->taskModel->find((int) $task['parent_id']) : null;

        $this->view('tasks/form', [
            'title' => 'Редактирование задачи — Traking',
            'task' => $task,
            'project' => $project,
            'parentTask' => $parentTask,
            'projectUsers' => $projectUsers,
            'statuses' => $statuses,
            'projects' => [],
            'errors' => Session::getFlash('errors', []),
            'old' => Session::getFlash('old', []),
        ]);
    }

    /**
     * Обновление задачи (POST)
     * POST /tasks/{id}/edit
     */
    public function update(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        $this->authorize(can('create_task', (int) $task['project_id']), 'Недостаточно прав для редактирования');

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status_id' => (int) ($_POST['status_id'] ?? 0),
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => $_POST['deadline'] ?? null,
            'assigned_to' => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null,
        ];

        // Валидация
        $errors = $this->validateTaskData(array_merge($data, ['project_id' => $task['project_id']]));

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect("/tasks/{$taskId}/edit");
            return;
        }

        // Пустые значения → null
        if (empty($data['deadline'])) {
            $data['deadline'] = null;
        }
        if ($data['description'] === '') {
            $data['description'] = null;
        }

        $this->taskModel->update($taskId, $data);

        Session::flash('success', 'Задача обновлена');
        $this->redirect('/tasks/' . $taskId);
    }

    /**
     * Смена статуса задачи (POST, AJAX)
     * POST /tasks/{id}/status
     */
    public function changeStatus(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Задача не найдена'], 404);
            } else {
                Response::notFound('Задача не найдена');
            }
            return;
        }

        if (!TaskAccessMiddleware::check($taskId)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Нет доступа'], 403);
            } else {
                Response::forbidden('Нет доступа к задаче');
            }
            return;
        }

        $statusId = (int) ($_POST['status_id'] ?? 0);
        if ($statusId <= 0) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Не указан статус'], 400);
            } else {
                Session::flash('error', 'Выберите статус');
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        // Проверяем что статус существует
        $db = Database::getInstance();
        $status = $db->fetch("SELECT * FROM task_statuses WHERE id = ?", [$statusId]);

        if (!$status) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Статус не найден'], 400);
            } else {
                Session::flash('error', 'Статус не найден');
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        // Если статус «done» или «closed» — проверяем подзадачи
        if (in_array($status['code'], ['done', 'closed'])) {
            $canClose = $this->treeService->canClose($taskId);
            if (!$canClose['can']) {
                if ($this->isAjax()) {
                    $this->json([
                        'error' => 'Нельзя завершить: есть незавершённые подзадачи',
                        'blocking' => $canClose['blocking'],
                    ], 400);
                } else {
                    Session::flash('error', 'Нельзя завершить задачу: есть незавершённые подзадачи');
                    $this->redirect("/tasks/{$taskId}");
                }
                return;
            }
        }

        $updateData = ['status_id' => $statusId];
        if (in_array($status['code'], ['done', 'closed'])) {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
        } else {
            $updateData['closed_at'] = null;
        }

        $this->taskModel->update($taskId, $updateData);

        // Логируем смену статуса
        $db2 = Database::getInstance();
        $oldStatus = $db2->fetch("SELECT name FROM task_statuses WHERE id = ?", [(int) $task['status_id']]);
        $this->activityLogService->log(
            Auth::id(),
            (int) $task['project_id'],
            $taskId,
            'status_changed',
            $oldStatus['name'] ?? null,
            $status['name']
        );

        // Уведомляем участников
        $this->notificationService->notifyStatusChanged($taskId, $status['name'], Auth::id());

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'status' => $status,
            ]);
        } else {
            Session::flash('success', 'Статус задачи изменён на «' . $status['name'] . '»');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Закрытие задачи (завершение — статус «Готово»)
     * POST /tasks/{id}/close
     */
    public function close(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        // Проверяем возможность закрытия через TaskTreeService
        $canClose = $this->treeService->canClose($taskId);

        if (!$canClose['can']) {
            $blockingTitles = array_map(fn($t) => $t['title'], $canClose['blocking']);
            Session::flash('error', 'Нельзя завершить задачу. Незавершённые подзадачи: ' . implode(', ', $blockingTitles));
            $this->redirect("/tasks/{$taskId}");
            return;
        }

        // Получаем ID статуса «closed» (Сделано / архив)
        $db = Database::getInstance();
        $closedStatus = $db->fetch("SELECT id FROM task_statuses WHERE code = 'closed' LIMIT 1");

        if (!$closedStatus) {
            Session::flash('error', 'Статус «Сделано» не найден в системе');
            $this->redirect("/tasks/{$taskId}");
            return;
        }

        $this->taskModel->update($taskId, [
            'status_id' => (int) $closedStatus['id'],
            'closed_at' => date('Y-m-d H:i:s'),
        ]);

        // Уведомляем участников
        $this->notificationService->notifyStatusChanged($taskId, 'Сделано', Auth::id());

        Session::flash('success', 'Задача закрыта и перемещена в архив');
        $this->redirect("/tasks/{$taskId}");
    }

    /**
     * Переназначение исполнителя задачи
     * POST /tasks/{id}/reassign
     */
    public function reassign(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        // Только manager/admin может переназначать
        $this->authorize(can('create_task', (int) $task['project_id']), 'Недостаточно прав для переназначения');

        $assignedTo = !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null;

        // Проверяем что исполнитель является участником проекта
        if ($assignedTo !== null) {
            if (!$this->projectModel->hasUser((int) $task['project_id'], $assignedTo)) {
                Session::flash('error', 'Выбранный пользователь не является участником проекта');
                $this->redirect("/tasks/{$taskId}");
                return;
            }
        }

        $this->taskModel->update($taskId, ['assigned_to' => $assignedTo]);

        // Уведомляем нового исполнителя
        if ($assignedTo !== null && $assignedTo !== (int) ($task['assigned_to'] ?? 0)) {
            $this->notificationService->notifyTaskAssigned($taskId, $assignedTo);
        }

        Session::flash('success', 'Исполнитель задачи изменён');
        $this->redirect("/tasks/{$taskId}");
    }

    /**
     * AJAX: получить дерево задач
     * GET /ajax/tasks/{id}/tree
     */
    public function ajaxTree(string $id): void
    {
        $taskId = (int) $id;

        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['error' => 'Нет доступа'], 403);
            return;
        }

        $tree = $this->treeService->getTree($taskId);
        $this->json($tree);
    }

    // ========================================================================
    // Приватные методы
    // ========================================================================

    /**
     * Проверка: является ли запрос AJAX
     *
     * @return bool
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Удаление задачи (только создатель или admin)
     * POST /tasks/{id}/delete
     */
    public function delete(string $id): void
    {
        $taskId = (int) $id;
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            Response::notFound('Задача не найдена');
            return;
        }

        // Удалять может только создатель задачи или admin
        $canDelete = Auth::isAdmin() || (int) $task['created_by'] === Auth::id();

        if (!$canDelete) {
            Response::forbidden('Удалить задачу может только её создатель или администратор');
            return;
        }

        $projectId = (int) $task['project_id'];

        // Удаляем задачу (CASCADE удалит подзадачи, комментарии, файлы)
        $this->taskModel->delete($taskId);

        Session::flash('success', 'Задача «' . $task['title'] . '» удалена');
        $this->redirect('/projects/' . $projectId);
    }

    /**
     * Валидация данных задачи
     *
     * @param array $data Данные формы
     * @return array Массив ошибок
     */
    private function validateTaskData(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'][] = 'Поле «Название» обязательно для заполнения';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors['title'][] = 'Название не должно превышать 255 символов';
        }

        if (!empty($data['priority']) && !in_array($data['priority'], ['low', 'medium', 'high', 'urgent'])) {
            $errors['priority'][] = 'Недопустимый приоритет';
        }

        if (!empty($data['deadline']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['deadline'])) {
            $errors['deadline'][] = 'Некорректный формат даты';
        }

        return $errors;
    }
}
