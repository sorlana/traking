<?php
/**
 * CommentController — Контроллер комментариев к задачам (AJAX)
 *
 * Функции: добавление, редактирование, удаление комментариев.
 * При добавлении поддерживает прикрепление файла (чат-интерфейс).
 * Все методы поддерживают как AJAX (JSON-ответ), так и обычные запросы (redirect).
 * Доступ проверяется через TaskAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Response;
use Helpers\Session;
use Middleware\TaskAccessMiddleware;
use Models\TaskComment;
use Models\TaskFile;
use Models\Task;
use Services\NotificationService;
use Services\PushService;
use Services\ActivityLogService;

class CommentController extends Controller
{
    private TaskComment $commentModel;
    private TaskFile $fileModel;
    private Task $taskModel;
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    /** @var array Допустимые расширения файлов */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'zip', 'rar',
        'mp4', 'mov',
    ];

    /** @var int Максимальный размер файла (50 МБ) */
    private const MAX_FILE_SIZE = 50 * 1024 * 1024;

    public function __construct()
    {
        $this->commentModel = new TaskComment();
        $this->fileModel = new TaskFile();
        $this->taskModel = new Task();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * Добавить комментарий к задаче (с опциональным файлом)
     * POST /tasks/{id}/comments
     *
     * @param string $taskId ID задачи
     * @return void
     */
    public function store(string $taskId): void
    {
        $taskId = (int) $taskId;

        // Проверяем существование задачи
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Задача не найдена'], 404);
            } else {
                Response::notFound('Задача не найдена');
            }
            return;
        }

        // Проверяем доступ через TaskAccessMiddleware
        if (!TaskAccessMiddleware::check($taskId)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Нет доступа к задаче'], 403);
            } else {
                Response::forbidden('Нет доступа к задаче');
            }
            return;
        }

        // Получаем текст комментария
        $commentText = trim($_POST['comment_text'] ?? '');
        $hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

        // Валидация: текст обязателен, если нет файла
        if ($commentText === '' && !$hasFile) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Текст комментария обязателен'], 422);
            } else {
                Session::flash('error', 'Текст комментария обязателен');
                Response::back();
            }
            return;
        }

        if (mb_strlen($commentText) > 5000) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Комментарий не должен превышать 5000 символов'], 422);
            } else {
                Session::flash('error', 'Комментарий не должен превышать 5000 символов');
                Response::back();
            }
            return;
        }

        // Если текст пустой но есть файл — ставим заглушку
        if ($commentText === '' && $hasFile) {
            $commentText = '📎 Файл';
        }

        // Создаём комментарий
        $data = [
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment_text' => $commentText,
            'parent_comment_id' => !empty($_POST['parent_comment_id']) ? (int) $_POST['parent_comment_id'] : null,
        ];

        $commentId = $this->commentModel->create($data);

        // Обрабатываем прикреплённый файл (если есть)
        $uploadedFile = null;
        if ($hasFile) {
            $uploadedFile = $this->handleFileUpload($taskId, (int) $commentId, $task);
        }

        // Уведомления о комментариях отключены — сообщения приходят через polling в чате
        // $this->notificationService->notifyCommentAdded($taskId, Auth::id());

        // Отправляем push-уведомление участникам задачи
        $pushService = new PushService();
        $user = Auth::user();
        $senderName = $user['name'] ?? $user['login'] ?? 'Кто-то';
        $pushBody = mb_strlen($commentText) > 100 ? mb_substr($commentText, 0, 100) . '...' : $commentText;
        if ($commentText === '' || $commentText === '📎 Файл') {
            $pushBody = '📎 Отправлен файл';
        }
        $pushUrl = url('/tasks/' . $taskId);
        $pushService->sendToTaskParticipants(
            $taskId,
            Auth::id(),
            $senderName . ' — ' . ($task['title'] ?? 'Задача'),
            $pushBody,
            $pushUrl
        );

        // Получаем созданный комментарий с именем автора
        $user = Auth::user();
        $comment = [
            'id' => $commentId,
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment_text' => $commentText,
            'user_name' => $user['name'] ?? $user['login'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'files' => $uploadedFile ? [$uploadedFile] : [],
            'links' => [],
        ];

        if ($this->isAjax()) {
            $this->json(['success' => true, 'comment' => $comment]);
        } else {
            Session::flash('success', 'Комментарий добавлен');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Загрузка файла, привязанного к комментарию
     *
     * @param int $taskId ID задачи
     * @param int $commentId ID комментария
     * @param array $task Данные задачи
     * @return array|null Данные загруженного файла или null при ошибке
     */
    private function handleFileUpload(int $taskId, int $commentId, array $task): ?array
    {
        $file = $_FILES['file'];

        // Валидация расширения
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return null;
        }

        // Валидация размера
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return null;
        }

        // Формируем путь хранения
        $projectId = (int) $task['project_id'];
        $uploadDir = BASE_PATH . "/storage/uploads/projects/{$projectId}/tasks/{$taskId}";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерируем уникальное имя файла
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;
        $filePath = $uploadDir . '/' . $uniqueName;

        // Перемещаем загруженный файл
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return null;
        }

        // Относительный путь для БД
        $relativePath = "projects/{$projectId}/tasks/{$taskId}/{$uniqueName}";

        // Сохраняем запись в БД с привязкой к комментарию
        $fileId = $this->fileModel->create([
            'task_id' => $taskId,
            'comment_id' => $commentId,
            'uploaded_by' => Auth::id(),
            'file_name' => $originalName,
            'file_path' => $relativePath,
            'file_type' => $extension,
            'file_size' => $file['size'],
        ]);

        return [
            'id' => $fileId,
            'file_name' => $originalName,
            'file_size' => (int) $file['size'],
            'file_type' => $extension,
        ];
    }

    /**
     * AJAX: получить новые сообщения (polling)
     * GET /ajax/tasks/{id}/messages?after=LAST_ID
     *
     * @param string $taskId ID задачи
     * @return void
     */
    public function pollMessages(string $taskId): void
    {
        $taskId = (int) $taskId;

        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['messages' => []], 403);
            return;
        }

        $afterId = (int) ($_GET['after'] ?? 0);
        $db = Database::getInstance();

        $messages = $db->fetchAll(
            "SELECT tc.*, u.name as user_name, u.login as user_login
             FROM task_comments tc
             JOIN users u ON tc.user_id = u.id
             WHERE tc.task_id = ? AND tc.id > ?
             ORDER BY tc.created_at ASC",
            [$taskId, $afterId]
        );

        // Подтягиваем файлы и ссылки к каждому сообщению
        foreach ($messages as &$msg) {
            $msg['files'] = $db->fetchAll(
                "SELECT id, file_name, file_size, file_type FROM task_files WHERE comment_id = ?",
                [(int) $msg['id']]
            );
            $msg['links'] = $db->fetchAll(
                "SELECT id, url, title FROM task_links WHERE comment_id = ?",
                [(int) $msg['id']]
            );
            // Приводим к нужному формату
            $msg['id'] = (int) $msg['id'];
            $msg['user_id'] = (int) $msg['user_id'];
            $msg['task_id'] = (int) $msg['task_id'];
            $msg['parent_comment_id'] = $msg['parent_comment_id'] ? (int) $msg['parent_comment_id'] : null;
            $msg['is_pinned'] = (int) ($msg['is_pinned'] ?? 0);
        }
        unset($msg);

        // Определяем удалённые сообщения (если клиент передал список своих ID)
        $deleted = [];
        $clientIds = trim($_GET['ids'] ?? '');
        if ($clientIds !== '') {
            $idArray = array_map('intval', explode(',', $clientIds));
            if (!empty($idArray)) {
                // Проверяем какие из переданных ID больше не существуют в БД
                $placeholders = implode(',', array_fill(0, count($idArray), '?'));
                $existing = $db->fetchAll(
                    "SELECT id FROM task_comments WHERE task_id = ? AND id IN ({$placeholders})",
                    array_merge([$taskId], $idArray)
                );
                $existingIds = array_column($existing, 'id');
                $existingIds = array_map('intval', $existingIds);
                $deleted = array_values(array_diff($idArray, $existingIds));
            }
        }

        // Определяем обновлённые сообщения (отредактированные за последние 10 сек)
        $updated = [];
        if ($clientIds !== '') {
            $updatedRows = $db->fetchAll(
                "SELECT id, comment_text FROM task_comments 
                 WHERE task_id = ? AND updated_at IS NOT NULL AND updated_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)",
                [$taskId]
            );
            foreach ($updatedRows as $row) {
                $updated[] = ['id' => (int) $row['id'], 'comment_text' => $row['comment_text']];
            }
        }

        $this->json([
            'messages' => $messages,
            'deleted' => $deleted,
            'updated' => $updated,
        ]);
    }

    /**
     * Редактировать свой комментарий
     * POST /comments/{id}/edit
     *
     * @param string $id ID комментария
     * @return void
     */
    public function update(string $id): void
    {
        $commentId = (int) $id;
        $comment = $this->commentModel->find($commentId);

        if (!$comment) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Комментарий не найден'], 404);
            } else {
                Response::notFound('Комментарий не найден');
            }
            return;
        }

        // Проверяем: только автор может редактировать свой комментарий
        $userId = Auth::id();
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ((int) $comment['user_id'] !== $userId && $roleId !== 1) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Можно редактировать только свои комментарии'], 403);
            } else {
                Response::forbidden('Можно редактировать только свои комментарии');
            }
            return;
        }

        // Валидация текста
        $commentText = trim($_POST['comment_text'] ?? '');

        if ($commentText === '') {
            if ($this->isAjax()) {
                $this->json(['error' => 'Текст комментария обязателен'], 422);
            } else {
                Session::flash('error', 'Текст комментария обязателен');
                Response::back();
            }
            return;
        }

        if (mb_strlen($commentText) > 5000) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Комментарий не должен превышать 5000 символов'], 422);
            } else {
                Session::flash('error', 'Комментарий не должен превышать 5000 символов');
                Response::back();
            }
            return;
        }

        // Обновляем комментарий (с updated_at для синхронизации через polling)
        $this->commentModel->update($commentId, [
            'comment_text' => $commentText,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'comment_text' => $commentText]);
        } else {
            Session::flash('success', 'Комментарий обновлён');
            $this->redirect("/tasks/{$comment['task_id']}");
        }
    }

    /**
     * Удалить свой комментарий
     * POST /comments/{id}/delete
     *
     * @param string $id ID комментария
     * @return void
     */
    public function delete(string $id): void
    {
        $commentId = (int) $id;
        $comment = $this->commentModel->find($commentId);

        if (!$comment) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Комментарий не найден'], 404);
            } else {
                Response::notFound('Комментарий не найден');
            }
            return;
        }

        // Проверяем: автор или админ может удалить
        $userId = Auth::id();
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ((int) $comment['user_id'] !== $userId && $roleId !== 1) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Можно удалять только свои комментарии'], 403);
            } else {
                Response::forbidden('Можно удалять только свои комментарии');
            }
            return;
        }

        $taskId = (int) $comment['task_id'];
        $this->commentModel->delete($commentId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            Session::flash('success', 'Комментарий удалён');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Закрепить/открепить сообщение
     * POST /comments/{id}/pin
     *
     * @param string $id ID комментария
     * @return void
     */
    public function togglePin(string $id): void
    {
        $commentId = (int) $id;
        $comment = $this->commentModel->find($commentId);

        if (!$comment) {
            $this->json(['error' => 'Сообщение не найдено'], 404);
            return;
        }

        // Переключаем is_pinned
        $newValue = ((int)($comment['is_pinned'] ?? 0)) === 1 ? 0 : 1;

        $db = Database::getInstance();
        $db->update('task_comments', ['is_pinned' => $newValue], 'id = ?', [$commentId]);

        $this->json(['success' => true, 'is_pinned' => $newValue]);
    }

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
}
