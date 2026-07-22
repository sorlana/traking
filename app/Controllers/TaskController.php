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
use Models\TaskComment;
use Models\TaskLink;
use Services\TaskTreeService;
use Services\TimeTrackingService;
use Services\NotificationService;
use Services\PushService;
use Services\ActivityLogService;
use Services\CommentPinService;

class TaskController extends Controller
{
    private Task $taskModel;
    private Project $projectModel;
    private TaskTreeService $treeService;
    private TimeTrackingService $timeTrackingService;
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;
    private CommentPinService $commentPinService;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->treeService = new TaskTreeService();
        $this->timeTrackingService = new TimeTrackingService();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
        $this->commentPinService = new CommentPinService();
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
                        JOIN projects p ON t.project_id = p.id
                        JOIN users project_creator ON project_creator.id = p.created_by";

                $params = [];

                // Для manager — только проекты, к которым подключён
                if ($roleId === 2) {
                    $sql .= " JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?";
                    $params[] = (int) $user['id'];
                }

                $sql .= " WHERE 1=1";

                if ($roleId === Auth::ROLE_MANAGER) {
                    $sql .= " AND project_creator.role_id <> ?";
                    $params[] = Auth::ROLE_EXECUTOR;
                }

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
                        $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done', 'closed')";
                    } elseif ($filters['deadline'] === 'week') {
                        $sql .= " AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    } elseif ($filters['deadline'] === 'today') {
                        $sql .= " AND t.deadline = CURDATE()";
                    }
                }
                if (!empty($filters['overdue'])) {
                    $sql .= " AND t.deadline < CURDATE() AND ts.code NOT IN ('done', 'closed')";
                }

                $sql .= " ORDER BY p.title ASC, t.sort_order ASC, t.created_at DESC";
                $tasks = $db->fetchAll($sql, $params);
            }
            $project = null;
        }

        // Общая страница отображает задачи деревом. Порядок корневых задач
        // совпадает с сохранённым порядком на странице проекта.
        $tasks = $this->buildTaskRows($tasks);

        // Данные для фильтров
        $statuses = $db->fetchAll("SELECT * FROM task_statuses ORDER BY sort_order");
        $projects = [];
        if ($roleId === 1) {
            $projects = $db->fetchAll("SELECT id, title FROM projects ORDER BY title");
        } elseif ($roleId === 2) {
            $projects = $db->fetchAll(
                "SELECT p.id, p.title FROM projects p
                 JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                 JOIN users creator ON creator.id = p.created_by
                 WHERE creator.role_id <> ?
                 ORDER BY p.title",
                [(int) $user['id'], Auth::ROLE_EXECUTOR]
            );
        } elseif ($roleId === Auth::ROLE_EXECUTOR) {
            $projects = $db->fetchAll(
                "SELECT p.id, p.title FROM projects p
                 JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                 JOIN users creator ON creator.id = p.created_by
                 WHERE creator.role_id <> ? OR p.created_by = ?
                 ORDER BY p.title",
                [(int) $user['id'], Auth::ROLE_EXECUTOR, (int) $user['id']]
            );
        }

        $taskCreationProjects = $projects;
        if ($roleId === Auth::ROLE_EXECUTOR) {
            $taskCreationProjects = $db->fetchAll(
                "SELECT p.id, p.title FROM projects p
                 JOIN users creator ON creator.id = p.created_by
                 WHERE p.created_by = ? AND creator.role_id = ?
                 ORDER BY p.title",
                [(int) $user['id'], Auth::ROLE_EXECUTOR]
            );
        }

        // Исполнитель в собственном проекте может назначать задачи только себе.
        $executors = $roleId === Auth::ROLE_EXECUTOR
            ? $db->fetchAll(
                "SELECT id, name FROM users WHERE id = ? AND status = 'active'",
                [(int) $user['id']]
            )
            : $db->fetchAll(
                "SELECT id, name FROM users WHERE status = 'active' AND role_id != 1 ORDER BY name"
            );

        $this->view('tasks/index', [
            'title' => 'Задачи — Traking',
            'tasks' => $tasks,
            'project' => $project,
            'statuses' => $statuses,
            'projects' => $projects,
            'taskCreationProjects' => $taskCreationProjects,
            'executors' => $executors,
            'filters' => $filters,
        ]);
    }

    /**
     * Подготовить плоский список строк для древовидной таблицы.
     * Дочерние строки располагаются сразу после родителя, сохраняя порядок выборки.
     */
    private function buildTaskRows(array $tasks): array
    {
        $tasksById = [];
        $childrenByParent = [];
        $rootIds = [];

        foreach ($tasks as $task) {
            $tasksById[(int) $task['id']] = $task;
        }

        foreach ($tasks as $task) {
            $taskId = (int) $task['id'];
            $parentId = !empty($task['parent_id']) ? (int) $task['parent_id'] : null;

            if ($parentId !== null && $parentId !== $taskId && isset($tasksById[$parentId])) {
                $childrenByParent[$parentId][] = $taskId;
            } elseif ($parentId === null || $parentId === $taskId) {
                $rootIds[] = $taskId;
            }
        }

        $rows = [];
        $visited = [];
        $appendBranch = function (int $taskId, int $depth, array $ancestorIds) use (
            &$appendBranch,
            &$rows,
            &$visited,
            $tasksById,
            $childrenByParent
        ): void {
            if (isset($visited[$taskId]) || !isset($tasksById[$taskId])) {
                return;
            }

            $visited[$taskId] = true;
            $childIds = $childrenByParent[$taskId] ?? [];
            $row = $tasksById[$taskId];
            $row['tree_depth'] = $depth;
            $row['tree_ancestor_ids'] = $ancestorIds;
            $row['tree_has_children'] = !empty($childIds);
            $rows[] = $row;

            $childAncestors = [...$ancestorIds, $taskId];
            foreach ($childIds as $childId) {
                $appendBranch($childId, $depth + 1, $childAncestors);
            }
        };

        foreach ($rootIds as $rootId) {
            $appendBranch($rootId, 0, []);
        }

        return $rows;
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

        // Поддерживаем правило и для уже существующих деревьев: при первом
        // открытии задача сразу получает актуальный статус.
        $this->syncRevisionStatusUpTree($taskId, $db);
        $syncedStatus = $db->fetch(
            "SELECT t.status_id, t.closed_at, ts.name AS status_name, ts.code AS status_code
             FROM tasks t
             JOIN task_statuses ts ON ts.id = t.status_id
             WHERE t.id = ?",
            [$taskId]
        );
        if ($syncedStatus) {
            $task = array_merge($task, $syncedStatus);
        }

        // Получаем связанные данные
        $children = $this->taskModel->getChildren($taskId);
        $childrenTree = $this->treeService->getChildrenTree($taskId);
        $comments = $this->taskModel->getComments($taskId);
        $this->commentPinService->annotateMessages($comments, Auth::id());
        $files = $this->taskModel->getFiles($taskId);
        $links = $this->taskModel->getLinks($taskId);

        // Группируем уже загруженные файлы и ссылки в памяти. Раньше здесь
        // выполнялось по два SQL-запроса на каждое сообщение (N+1), из-за чего
        // открытие задач с длинным чатом заметно замедлялось.
        $filesByComment = [];
        foreach ($files as $file) {
            $commentId = (int) ($file['comment_id'] ?? 0);
            if ($commentId > 0) {
                $filesByComment[$commentId][] = $file;
            }
        }

        $linksByComment = [];
        foreach ($links as $link) {
            $commentId = (int) ($link['comment_id'] ?? 0);
            if ($commentId > 0) {
                $linksByComment[$commentId][] = $link;
            }
        }

        foreach ($comments as &$comment) {
            $commentId = (int) $comment['id'];
            $comment['files'] = $filesByComment[$commentId] ?? [];
            $comment['links'] = $linksByComment[$commentId] ?? [];
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
            $readByOthers = array_fill_keys(
                array_map(fn($r) => (int) $r['comment_id'], $readRows),
                true
            );
        } catch (\Throwable $e) {}

        // Добавляем read_by_others к каждому комментарию
        foreach ($comments as &$comment) {
            $comment['read_by_others'] = isset($readByOthers[(int) $comment['id']]);
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

        // Данные о затраченном времени
        $timeSpent = $task['time_spent'] ?? null;
        $totalTime = $this->timeTrackingService->getTotalTime($taskId);
        $canEditTime = $this->timeTrackingService->canEditTime($task, Auth::id())['allowed'];

        // Время руководителя
        $managerTimeSpent = $task['manager_time_spent'] ?? null;
        $managerTotalTime = $this->timeTrackingService->getManagerTotalTime($taskId);
        $canManagerEditTime = false;
        $user = Auth::user();
        if ((int) ($user['role_id'] ?? 0) === 2) {
            $canManagerEditTime = $this->timeTrackingService->canManagerEditTime($task, Auth::id())['allowed'];
        }

        $this->view('tasks/show', [
            'title' => e($task['title']) . ' — Traking',
            'task' => $task,
            'children' => $children,
            'childrenTree' => $childrenTree,
            'comments' => $comments,
            'files' => $files,
            'links' => $links,
            'parent' => $parent,
            'statuses' => $statuses,
            'projectUsers' => $projectUsers,
            'canClose' => $canClose,
            'activityLog' => $activityLog,
            'time_spent' => $timeSpent,
            'total_time' => $totalTime,
            'canEditTime' => $canEditTime,
            'manager_time_spent' => $managerTimeSpent,
            'manager_total_time' => $managerTotalTime,
            'canManagerEditTime' => $canManagerEditTime,
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
            if ($roleId === Auth::ROLE_ADMIN) {
                $projects = $db->fetchAll("SELECT id, title FROM projects ORDER BY title");
            } elseif ($roleId === Auth::ROLE_MANAGER) {
                $projects = $db->fetchAll(
                    "SELECT p.id, p.title FROM projects p
                     JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
                     JOIN users creator ON creator.id = p.created_by
                     WHERE creator.role_id <> ?
                     ORDER BY p.title",
                    [(int) $user['id'], Auth::ROLE_EXECUTOR]
                );
            } else {
                $projects = $db->fetchAll(
                    "SELECT p.id, p.title FROM projects p
                     JOIN users creator ON creator.id = p.created_by
                     WHERE p.created_by = ? AND creator.role_id = ?
                     ORDER BY p.title",
                    [(int) $user['id'], Auth::ROLE_EXECUTOR]
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
        $db = Database::getInstance();
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
        $sourceImageId = !empty($_POST['source_image_id']) ? (int) $_POST['source_image_id'] : null;
        $sourceImage = null;

        // Проверяем доступ к проекту
        if ($data['project_id'] <= 0 || !ProjectAccessMiddleware::check($data['project_id'])) {
            Response::forbidden('Нет доступа к проекту');
            return;
        }

        $this->authorize(can('create_task', $data['project_id']), 'Недостаточно прав для создания задачи');

        $data['assigned_to'] = $this->privateProjectAssignee(
            $data['project_id'],
            $data['assigned_to']
        );

        // Валидация
        $errors = $this->validateTaskData($data);

        // Родитель и выбранное изображение должны относиться к этой же задаче/проекту.
        if ($data['parent_id']) {
            $parentTask = $this->taskModel->find((int) $data['parent_id']);
            if (!$parentTask || (int) $parentTask['project_id'] !== $data['project_id']) {
                $errors['parent_id'][] = 'Некорректная родительская задача';
            } else {
                // Исполнитель доработки всегда наследуется от родительской задачи.
                // Не доверяем значению assigned_to из формы.
                $data['assigned_to'] = !empty($parentTask['assigned_to'])
                    ? (int) $parentTask['assigned_to']
                    : null;

                if ($sourceImageId) {
                    $sourceImage = $db->fetch(
                        "SELECT id, file_name, file_type
                         FROM task_files
                         WHERE id = ? AND task_id = ? AND comment_id IS NOT NULL
                           AND LOWER(file_type) IN ('jpg', 'jpeg', 'png', 'gif', 'webp')",
                        [$sourceImageId, (int) $data['parent_id']]
                    );
                    if (!$sourceImage) {
                        $errors['source_image_id'][] = 'Выбранное изображение не найдено в чате родительской задачи';
                    }
                }
            }
        } elseif ($sourceImageId) {
            $errors['source_image_id'][] = 'Ссылка на изображение доступна только для доработки';
        }

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', array_merge($data, ['source_image_id' => $sourceImageId]));
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

        // Создание доработки и ссылки выполняем атомарно.
        $pdo = $db->getConnection();
        $pdo->beginTransaction();
        try {
            $taskId = $this->taskModel->create($data);

            // В первой записи чата доработки сохраняем ссылку на выбранное изображение
            // из чата родительской задачи. Сам файл не копируется.
            if ($sourceImage) {
                $commentModel = new TaskComment();
                $commentId = $commentModel->create([
                    'task_id' => (int) $taskId,
                    'user_id' => Auth::id(),
                    'comment_text' => 'Изображение из чата родительской задачи:',
                    'parent_comment_id' => null,
                ]);

                $linkModel = new TaskLink();
                $linkModel->create([
                    'task_id' => (int) $taskId,
                    'comment_id' => (int) $commentId,
                    'user_id' => Auth::id(),
                    'url' => url('/tasks/' . (int) $taskId . '/referenced-files/' . (int) $sourceImage['id'] . '/download'),
                    'title' => mb_substr('Изображение: ' . $sourceImage['file_name'], 0, 255),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

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

        // Активная доработка автоматически переводит всю цепочку родителей
        // в статус «Доработки».
        $this->syncRevisionStatusUpTree((int) $taskId, $db);

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
        $data['assigned_to'] = $this->privateProjectAssignee(
            (int) $task['project_id'],
            $data['assigned_to']
        );

        // Валидация
        $errors = $this->validateTaskData(array_merge($data, ['project_id' => $task['project_id']]));

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect(isset($_POST['redirect_to']) ? $this->tasksReturnUrl() : "/tasks/{$taskId}/edit");
            return;
        }

        // Пустые значения → null
        if (empty($data['deadline'])) {
            $data['deadline'] = null;
        }
        if ($data['description'] === '') {
            $data['description'] = null;
        }

        $statusChanged = (int) $task['status_id'] !== (int) $data['status_id'];
        $this->taskModel->update($taskId, $data);

        if ($statusChanged) {
            $db = Database::getInstance();
            $newStatus = $db->fetch("SELECT name FROM task_statuses WHERE id = ?", [$data['status_id']]);
            if ($newStatus) {
                $this->notificationService->notifyStatusChanged($taskId, $newStatus['name'], Auth::id());
                (new PushService())->sendTaskStatusChanged($taskId, Auth::id(), $newStatus['name']);
            }
        }

        $this->syncRevisionStatusUpTree($taskId, Database::getInstance());

        Session::flash('success', 'Задача обновлена');
        $this->redirect(isset($_POST['redirect_to']) ? $this->tasksReturnUrl() : '/tasks/' . $taskId);
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

        // Статус «closed» (Закрыто) может ставить только руководитель/admin
        if ($status['code'] === 'closed' && !can('create_task', (int) $task['project_id'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Только руководитель может закрывать задачу'], 403);
            } else {
                Session::flash('error', 'Только руководитель может закрывать задачу');
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

        // Если статус «Готово» — фиксируем затраченное время рядом со статусом
        $newValue = $status['name'];
        if ($status['code'] === 'done') {
            $timeSpent = $this->timeTrackingService->getTotalTime($taskId);
            if ($timeSpent > 0) {
                $newValue = $status['name'] . ' (' . $timeSpent . ' ч)';
            }
        }

        $this->activityLogService->log(
            Auth::id(),
            (int) $task['project_id'],
            $taskId,
            'status_changed',
            $oldStatus['name'] ?? null,
            $newValue
        );

        // Уведомляем участников
        $this->notificationService->notifyStatusChanged($taskId, $status['name'], Auth::id());
        if ((int) $task['status_id'] !== $statusId) {
            (new PushService())->sendTaskStatusChanged($taskId, Auth::id(), $status['name']);
        }

        $this->syncRevisionStatusUpTree($taskId, $db);

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
     * Закрытие задачи (принятие руководителем)
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

        // Только руководитель/admin может закрыть задачу
        $this->authorize(can('create_task', (int) $task['project_id']), 'Только руководитель может закрыть задачу');

        // Проверяем возможность закрытия через TaskTreeService
        $canClose = $this->treeService->canClose($taskId);

        if (!$canClose['can']) {
            $blockingTitles = array_map(fn($t) => $t['title'], $canClose['blocking']);
            Session::flash('error', 'Нельзя закрыть задачу. Незавершённые подзадачи: ' . implode(', ', $blockingTitles));
            $this->redirect("/tasks/{$taskId}");
            return;
        }

        // Получаем ID статуса «closed» (Закрыто)
        $db = Database::getInstance();
        $closedStatus = $db->fetch("SELECT id FROM task_statuses WHERE code = 'closed' LIMIT 1");

        if (!$closedStatus) {
            Session::flash('error', 'Статус «Закрыто» не найден в системе');
            $this->redirect("/tasks/{$taskId}");
            return;
        }

        $this->taskModel->update($taskId, [
            'status_id' => (int) $closedStatus['id'],
            'closed_at' => date('Y-m-d H:i:s'),
        ]);

        // Уведомляем участников
        $this->notificationService->notifyStatusChanged($taskId, 'Закрыто', Auth::id());
        if ((int) $task['status_id'] !== (int) $closedStatus['id']) {
            (new PushService())->sendTaskStatusChanged($taskId, Auth::id(), 'Закрыто');
        }

        Session::flash('success', 'Задача закрыта и принята');
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

        $assignedTo = $this->privateProjectAssignee(
            (int) $task['project_id'],
            !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null
        );

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

    /**
     * Создание одной или нескольких доработок из многострочного поля.
     * Каждая непустая строка становится отдельной задачей.
     */
    public function createSubtasks(string $parentId): void
    {
        $parentId = (int) $parentId;
        if (!$this->canManageSubtasks($parentId)) {
            Response::forbidden('Недостаточно прав для создания доработок');
            return;
        }

        $parent = $this->taskModel->find($parentId);
        $titles = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) ($_POST['titles'] ?? '')) ?: []),
            static fn(string $title): bool => $title !== ''
        ));

        if (empty($titles)) {
            Session::flash('error', 'Введите хотя бы одну доработку');
            $this->redirect('/tasks/' . $parentId);
            return;
        }
        if (count($titles) > 50) {
            Session::flash('error', 'За один раз можно добавить не более 50 доработок');
            $this->redirect('/tasks/' . $parentId);
            return;
        }
        foreach ($titles as $title) {
            if (mb_strlen($title) > 255) {
                Session::flash('error', 'Каждая строка должна быть не длиннее 255 символов');
                $this->redirect('/tasks/' . $parentId);
                return;
            }
        }

        $db = Database::getInstance();
        $inProgressStatus = $db->fetch("SELECT id FROM task_statuses WHERE code = 'in_progress' LIMIT 1");
        $statusId = $inProgressStatus ? (int) $inProgressStatus['id'] : 1;
        $assignedTo = !empty($parent['assigned_to']) ? (int) $parent['assigned_to'] : null;
        $createdTaskIds = [];

        $pdo = $db->getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($titles as $title) {
                $createdTaskIds[] = (int) $this->taskModel->create([
                    'project_id' => (int) $parent['project_id'],
                    'parent_id' => $parentId,
                    'title' => $title,
                    'description' => null,
                    'status_id' => $statusId,
                    'priority' => 'medium',
                    'deadline' => null,
                    'created_by' => Auth::id(),
                    'assigned_to' => $assignedTo,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        foreach ($createdTaskIds as $index => $taskId) {
            $this->activityLogService->log(
                Auth::id(),
                (int) $parent['project_id'],
                $taskId,
                'task_created',
                null,
                $titles[$index]
            );
            if ($assignedTo) {
                $this->notificationService->notifyTaskAssigned($taskId, $assignedTo);
            }
        }

        if (!empty($createdTaskIds)) {
            $this->syncRevisionStatusUpTree((int) $createdTaskIds[0], $db);
        }

        $count = count($createdTaskIds);
        Session::flash('success', $count === 1 ? 'Доработка добавлена' : "Добавлено доработок: {$count}");
        $this->redirect('/tasks/' . $parentId);
    }

    /**
     * Редактирование названия доработки из дерева родительской задачи.
     */
    public function editSubtask(string $parentId, string $id): void
    {
        $parentId = (int) $parentId;
        $taskId = (int) $id;
        $db = Database::getInstance();

        if (!$this->canManageSubtasks($parentId)) {
            Response::forbidden('Недостаточно прав для редактирования доработок');
            return;
        }

        if (!$this->isDescendantOf($taskId, $parentId, $db)) {
            Response::notFound('Доработка не найдена в этом дереве');
            return;
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Введите название доработки');
            $this->redirect('/tasks/' . $parentId);
            return;
        }
        if (mb_strlen($title) > 255) {
            Session::flash('error', 'Название не должно превышать 255 символов');
            $this->redirect('/tasks/' . $parentId);
            return;
        }

        $task = $this->taskModel->find($taskId);
        $this->taskModel->update($taskId, ['title' => $title]);
        $this->activityLogService->log(
            Auth::id(),
            (int) $task['project_id'],
            $taskId,
            'task_updated',
            $task['title'],
            $title
        );

        Session::flash('success', 'Название доработки обновлено');
        $this->redirect('/tasks/' . $parentId);
    }

    /**
     * Удаление одной доработки из дерева родительской задачи.
     */
    public function deleteSubtask(string $parentId, string $id): void
    {
        $parentId = (int) $parentId;
        $taskId = (int) $id;
        $db = Database::getInstance();

        if (!$this->canManageSubtasks($parentId)) {
            Response::forbidden('Недостаточно прав для удаления доработок');
            return;
        }

        if (!$this->isDescendantOf($taskId, $parentId, $db)) {
            Response::notFound('Доработка не найдена в этом дереве');
            return;
        }

        $deletedCount = $this->deleteTaskRoots([$taskId], $db);
        Session::flash('success', $deletedCount === 1 ? 'Доработка удалена' : "Удалено элементов: {$deletedCount}");
        $this->redirect('/tasks/' . $parentId);
    }

    /**
     * Массовое удаление выбранных доработок из одного дерева.
     */
    public function deleteSubtasks(string $parentId): void
    {
        $parentId = (int) $parentId;
        $db = Database::getInstance();

        if (!$this->canManageSubtasks($parentId)) {
            Response::forbidden('Недостаточно прав для удаления доработок');
            return;
        }

        $taskIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['task_ids'] ?? [])),
            static fn(int $id): bool => $id > 0
        )));

        if (empty($taskIds)) {
            Session::flash('error', 'Выберите хотя бы одну доработку');
            $this->redirect('/tasks/' . $parentId);
            return;
        }

        if (count($taskIds) > 200) {
            Session::flash('error', 'За один раз можно удалить не более 200 доработок');
            $this->redirect('/tasks/' . $parentId);
            return;
        }

        foreach ($taskIds as $taskId) {
            if (!$this->isDescendantOf($taskId, $parentId, $db)) {
                Response::forbidden('Одна из выбранных задач не относится к этому дереву');
                return;
            }
        }

        // Если выбраны родитель и его ребёнок, достаточно удалить только родителя.
        $selectedLookup = array_fill_keys($taskIds, true);
        $rootIds = array_values(array_filter($taskIds, function (int $taskId) use ($selectedLookup, $parentId, $db): bool {
            $current = $db->fetch('SELECT parent_id FROM tasks WHERE id = ?', [$taskId]);
            $depth = 0;
            while ($current && !empty($current['parent_id']) && $depth++ < 50) {
                $ancestorId = (int) $current['parent_id'];
                if ($ancestorId === $parentId) {
                    break;
                }
                if (isset($selectedLookup[$ancestorId])) {
                    return false;
                }
                $current = $db->fetch('SELECT parent_id FROM tasks WHERE id = ?', [$ancestorId]);
            }
            return true;
        }));

        $deletedCount = $this->deleteTaskRoots($rootIds, $db);
        Session::flash('success', "Удалено элементов: {$deletedCount}");
        $this->redirect('/tasks/' . $parentId);
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

    private function canManageSubtasks(int $parentId): bool
    {
        $parent = $this->taskModel->find($parentId);
        if (!$parent || !TaskAccessMiddleware::check($parentId)) {
            return false;
        }

        return can('create_task', (int) $parent['project_id']);
    }

    private function isDescendantOf(int $taskId, int $parentId, Database $db): bool
    {
        if ($taskId <= 0 || $taskId === $parentId) {
            return false;
        }

        $currentId = $taskId;
        $depth = 0;
        while ($depth++ < 50) {
            $task = $db->fetch('SELECT parent_id FROM tasks WHERE id = ?', [$currentId]);
            if (!$task || empty($task['parent_id'])) {
                return false;
            }
            $currentId = (int) $task['parent_id'];
            if ($currentId === $parentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Удаляет корневые элементы выбранных веток и возвращает общее число
     * удалённых задач с учётом вложенных доработок.
     */
    private function deleteTaskRoots(array $rootIds, Database $db): int
    {
        $allTaskIds = [];
        foreach ($rootIds as $rootId) {
            $allTaskIds[] = (int) $rootId;
            $allTaskIds = array_merge($allTaskIds, $this->collectChildIds((int) $rootId, $db));
        }
        $allTaskIds = array_values(array_unique($allTaskIds));

        if (empty($allTaskIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($allTaskIds), '?'));
        $taskFiles = $db->fetchAll(
            "SELECT DISTINCT file_path FROM task_files WHERE task_id IN ({$placeholders})",
            $allTaskIds
        );

        $pdo = $db->getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($rootIds as $rootId) {
                $this->taskModel->delete((int) $rootId);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        foreach ($taskFiles as $file) {
            $fullPath = BASE_PATH . '/storage/uploads/' . $file['file_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        return count($allTaskIds);
    }

    /**
     * Массовое удаление задач из общей таблицы.
     */
    public function deleteTasks(): void
    {
        $db = Database::getInstance();
        $taskIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['task_ids'] ?? [])),
            static fn(int $id): bool => $id > 0
        )));

        if (empty($taskIds)) {
            Session::flash('error', 'Выберите хотя бы одну задачу');
            $this->redirect($this->tasksReturnUrl());
            return;
        }
        if (count($taskIds) > 200) {
            Session::flash('error', 'За один раз можно удалить не более 200 задач');
            $this->redirect($this->tasksReturnUrl());
            return;
        }

        foreach ($taskIds as $taskId) {
            $task = $this->taskModel->find($taskId);
            $canManagePrivateProject = $task
                && $this->isPrivateProjectOwner((int) $task['project_id']);
            $canDelete = $task
                && TaskAccessMiddleware::check($taskId)
                && (
                    Auth::isAdmin()
                    || (int) $task['created_by'] === Auth::id()
                    || $canManagePrivateProject
                );
            if (!$canDelete) {
                Response::forbidden('Недостаточно прав для удаления одной из выбранных задач');
                return;
            }
        }

        // Если выбраны родитель и его потомок, удаляем только корень ветки.
        $selectedLookup = array_fill_keys($taskIds, true);
        $rootIds = array_values(array_filter($taskIds, function (int $taskId) use ($selectedLookup, $db): bool {
            $current = $db->fetch('SELECT parent_id FROM tasks WHERE id = ?', [$taskId]);
            $depth = 0;
            while ($current && !empty($current['parent_id']) && $depth++ < 50) {
                $ancestorId = (int) $current['parent_id'];
                if (isset($selectedLookup[$ancestorId])) {
                    return false;
                }
                $current = $db->fetch('SELECT parent_id FROM tasks WHERE id = ?', [$ancestorId]);
            }
            return true;
        }));

        $deletedCount = $this->deleteTaskRoots($rootIds, $db);
        Session::flash('success', "Удалено задач: {$deletedCount}");
        $this->redirect($this->tasksReturnUrl());
    }

    /**
     * Если у задачи есть непосредственная доработка в статусе «В работе»
     * или «Доработки», переводит задачу в «Доработки». Проверка поднимается
     * до корня, поэтому правило работает и для глубоко вложенного дерева.
     */
    private function syncRevisionStatusUpTree(int $taskId, Database $db): void
    {
        $revisionStatus = $db->fetch(
            "SELECT id, name FROM task_statuses WHERE code = 'revision' LIMIT 1"
        );
        if (!$revisionStatus) {
            return;
        }

        $revisionStatusId = (int) $revisionStatus['id'];
        $currentId = $taskId;
        $visited = [];
        $depth = 0;

        while ($currentId > 0 && $depth++ < 50 && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $current = $db->fetch(
                "SELECT t.id, t.parent_id, t.project_id, t.status_id, ts.name AS status_name
                 FROM tasks t
                 JOIN task_statuses ts ON ts.id = t.status_id
                 WHERE t.id = ?",
                [$currentId]
            );
            if (!$current) {
                break;
            }

            $activeChild = $db->fetch(
                "SELECT child.id
                 FROM tasks child
                 JOIN task_statuses child_status ON child_status.id = child.status_id
                 WHERE child.parent_id = ?
                   AND child_status.code IN ('in_progress', 'revision')
                 LIMIT 1",
                [$currentId]
            );

            if ($activeChild && (int) $current['status_id'] !== $revisionStatusId) {
                $this->taskModel->update($currentId, [
                    'status_id' => $revisionStatusId,
                    'closed_at' => null,
                ]);
                $this->activityLogService->log(
                    Auth::id(),
                    (int) $current['project_id'],
                    $currentId,
                    'status_changed',
                    $current['status_name'] ?? null,
                    $revisionStatus['name']
                );
                $this->notificationService->notifyStatusChanged(
                    $currentId,
                    $revisionStatus['name'],
                    Auth::id()
                );
                (new PushService())->sendTaskStatusChanged(
                    $currentId,
                    Auth::id(),
                    $revisionStatus['name']
                );
            }

            $currentId = !empty($current['parent_id']) ? (int) $current['parent_id'] : 0;
        }
    }

    /**
     * Разрешить возврат только на общую страницу задач с её GET-фильтрами.
     */
    private function tasksReturnUrl(): string
    {
        $returnUrl = (string) ($_POST['redirect_to'] ?? '/tasks');
        return $returnUrl === '/tasks' || str_starts_with($returnUrl, '/tasks?')
            ? $returnUrl
            : '/tasks';
    }

    private function isPrivateProjectOwner(int $projectId): bool
    {
        return Auth::isExecutor()
            && ProjectAccessMiddleware::isExecutorOwnedProject($projectId)
            && ProjectAccessMiddleware::canManage($projectId);
    }

    private function privateProjectAssignee(int $projectId, ?int $assignedTo): ?int
    {
        return ProjectAccessMiddleware::executorOwnerId($projectId) ?? $assignedTo;
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

        if (!TaskAccessMiddleware::check($taskId)) {
            Response::forbidden('Нет доступа к задаче');
            return;
        }

        // В приватном проекте владелец управляет всеми задачами проекта.
        $canDelete = Auth::isAdmin()
            || (int) $task['created_by'] === Auth::id()
            || $this->isPrivateProjectOwner((int) $task['project_id']);

        if (!$canDelete) {
            Response::forbidden('Удалить задачу может только её создатель или администратор');
            return;
        }

        $projectId = (int) $task['project_id'];
        $db = Database::getInstance();

        // Собираем ID всех подзадач рекурсивно (они тоже удалятся каскадом)
        $allTaskIds = $this->collectChildIds($taskId, $db);
        $allTaskIds[] = $taskId;

        // Удаляем физические файлы с диска
        $placeholders = implode(',', array_fill(0, count($allTaskIds), '?'));
        $taskFiles = $db->fetchAll(
            "SELECT file_path FROM task_files WHERE task_id IN ({$placeholders})",
            $allTaskIds
        );

        foreach ($taskFiles as $file) {
            $fullPath = BASE_PATH . '/storage/uploads/' . $file['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Удаляем задачу (CASCADE удалит подзадачи, комментарии, записи файлов)
        $this->taskModel->delete($taskId);

        Session::flash('success', 'Задача «' . $task['title'] . '» удалена');
        $returnUrl = $this->tasksReturnUrl();
        $this->redirect($returnUrl !== '/tasks' || isset($_POST['redirect_to']) ? $returnUrl : '/projects/' . $projectId);
    }

    /**
     * Рекурсивный сбор ID всех подзадач
     *
     * @param int $parentId ID родительской задачи
     * @param Database $db Экземпляр БД
     * @return array Массив ID подзадач
     */
    private function collectChildIds(int $parentId, Database $db): array
    {
        $children = $db->fetchAll("SELECT id FROM tasks WHERE parent_id = ?", [$parentId]);
        $ids = [];

        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $ids[] = $childId;
            $ids = array_merge($ids, $this->collectChildIds($childId, $db));
        }

        return $ids;
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
