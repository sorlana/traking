<?php
/**
 * FileController — Контроллер файлов и ссылок
 *
 * Функции: загрузка файлов к задачам, скачивание, удаление,
 * добавление/удаление ссылок.
 * Доступ проверяется через TaskAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Response;
use Helpers\Session;
use Middleware\TaskAccessMiddleware;
use Models\Task;
use Models\TaskFile;
use Models\TaskLink;
use Services\NotificationService;
use Services\ActivityLogService;

class FileController extends Controller
{
    private TaskFile $fileModel;
    private TaskLink $linkModel;
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
        $this->fileModel = new TaskFile();
        $this->linkModel = new TaskLink();
        $this->taskModel = new Task();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * Загрузка файла к задаче
     * POST /tasks/{id}/files
     *
     * @param string $taskId ID задачи
     * @return void
     */
    public function upload(string $taskId): void
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

        // Проверяем доступ
        if (!TaskAccessMiddleware::check($taskId)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Нет доступа к задаче'], 403);
            } else {
                Response::forbidden('Нет доступа к задаче');
            }
            return;
        }

        // Проверяем наличие файла
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = $this->getUploadErrorMessage($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($this->isAjax()) {
                $this->json(['error' => $errorMsg], 422);
            } else {
                Session::flash('error', $errorMsg);
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        $file = $_FILES['file'];

        // Валидация расширения
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $error = 'Недопустимый тип файла. Разрешены: ' . implode(', ', self::ALLOWED_EXTENSIONS);
            if ($this->isAjax()) {
                $this->json(['error' => $error], 422);
            } else {
                Session::flash('error', $error);
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        // Валидация размера
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $error = 'Размер файла превышает 50 МБ';
            if ($this->isAjax()) {
                $this->json(['error' => $error], 422);
            } else {
                Session::flash('error', $error);
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        // Формируем путь хранения: storage/uploads/projects/{project_id}/tasks/{task_id}/
        $projectId = (int) $task['project_id'];
        $uploadDir = BASE_PATH . "/storage/uploads/projects/{$projectId}/tasks/{$taskId}";

        // Создаём директорию если не существует
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерируем уникальное имя файла
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;
        $filePath = $uploadDir . '/' . $uniqueName;

        // Перемещаем загруженный файл
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $error = 'Ошибка при сохранении файла';
            if ($this->isAjax()) {
                $this->json(['error' => $error], 500);
            } else {
                Session::flash('error', $error);
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        // Относительный путь для хранения в БД
        $relativePath = "projects/{$projectId}/tasks/{$taskId}/{$uniqueName}";

        // Сохраняем запись в БД
        $fileId = $this->fileModel->create([
            'task_id' => $taskId,
            'comment_id' => null,
            'uploaded_by' => Auth::id(),
            'file_name' => $originalName,
            'file_path' => $relativePath,
            'file_type' => $extension,
            'file_size' => $file['size'],
        ]);

        // Логируем загрузку файла
        $this->activityLogService->log(
            Auth::id(),
            (int) $task['project_id'],
            $taskId,
            'file_uploaded',
            null,
            $originalName
        );

        // Уведомляем участников задачи
        $this->notificationService->notifyFileUploaded($taskId, Auth::id());

        if ($this->isAjax()) {
            $user = Auth::user();
            $this->json([
                'success' => true,
                'file' => [
                    'id' => $fileId,
                    'file_name' => $originalName,
                    'file_size' => $file['size'],
                    'file_type' => $extension,
                    'uploader_name' => $user['name'] ?? $user['login'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ]);
        } else {
            Session::flash('success', 'Файл успешно загружен');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Скачивание файла (с проверкой прав)
     * GET /files/{id}/download
     *
     * @param string $id ID файла
     * @return void
     */
    public function download(string $id): void
    {
        $fileId = (int) $id;
        $file = $this->fileModel->find($fileId);

        if (!$file) {
            Response::notFound('Файл не найден');
            return;
        }

        // Проверяем доступ к задаче
        if (!TaskAccessMiddleware::check((int) $file['task_id'])) {
            Response::forbidden('Нет доступа к файлу');
            return;
        }

        // Полный путь к файлу
        $fullPath = BASE_PATH . '/storage/uploads/' . $file['file_path'];

        if (!file_exists($fullPath)) {
            Response::notFound('Файл не найден на диске');
            return;
        }

        // Отдаём файл с правильными заголовками
        $mimeType = $this->getMimeType($file['file_type']);
        $fileName = $file['file_name'];

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($fullPath);
        exit;
    }

    /**
     * Удаление файла (автор или admin/manager)
     * POST /files/{id}/delete
     *
     * @param string $id ID файла
     * @return void
     */
    public function delete(string $id): void
    {
        $fileId = (int) $id;
        $file = $this->fileModel->find($fileId);

        if (!$file) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Файл не найден'], 404);
            } else {
                Response::notFound('Файл не найден');
            }
            return;
        }

        // Проверяем права: автор файла, или admin, или manager проекта
        $userId = Auth::id();
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);

        $canDelete = (int) $file['uploaded_by'] === $userId || $roleId === 1 || $roleId === 2;

        if (!$canDelete) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Недостаточно прав для удаления файла'], 403);
            } else {
                Response::forbidden('Недостаточно прав для удаления файла');
            }
            return;
        }

        $taskId = (int) $file['task_id'];

        // Удаляем файл с диска
        $fullPath = BASE_PATH . '/storage/uploads/' . $file['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Удаляем запись из БД
        $this->fileModel->delete($fileId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            Session::flash('success', 'Файл удалён');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Добавление ссылки к задаче
     * POST /tasks/{id}/links
     *
     * @param string $taskId ID задачи
     * @return void
     */
    public function addLink(string $taskId): void
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

        // Проверяем доступ
        if (!TaskAccessMiddleware::check($taskId)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Нет доступа к задаче'], 403);
            } else {
                Response::forbidden('Нет доступа к задаче');
            }
            return;
        }

        // Валидация URL
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');

        if ($url === '') {
            if ($this->isAjax()) {
                $this->json(['error' => 'URL обязателен'], 422);
            } else {
                Session::flash('error', 'URL обязателен');
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Некорректный URL'], 422);
            } else {
                Session::flash('error', 'Некорректный URL');
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        if (mb_strlen($url) > 2048) {
            if ($this->isAjax()) {
                $this->json(['error' => 'URL слишком длинный (макс. 2048 символов)'], 422);
            } else {
                Session::flash('error', 'URL слишком длинный');
                $this->redirect("/tasks/{$taskId}");
            }
            return;
        }

        if (mb_strlen($title) > 255) {
            $title = mb_substr($title, 0, 255);
        }

        // Если title не указан — используем URL
        if ($title === '') {
            $title = $url;
        }

        // Создаём запись ссылки
        $linkId = $this->linkModel->create([
            'task_id' => $taskId,
            'comment_id' => null,
            'user_id' => Auth::id(),
            'url' => $url,
            'title' => $title,
        ]);

        if ($this->isAjax()) {
            $user = Auth::user();
            $this->json([
                'success' => true,
                'link' => [
                    'id' => $linkId,
                    'url' => $url,
                    'title' => $title,
                    'user_name' => $user['name'] ?? $user['login'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ]);
        } else {
            Session::flash('success', 'Ссылка добавлена');
            $this->redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Удаление ссылки
     * POST /links/{id}/delete
     *
     * @param string $id ID ссылки
     * @return void
     */
    public function deleteLink(string $id): void
    {
        $linkId = (int) $id;
        $link = $this->linkModel->find($linkId);

        if (!$link) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Ссылка не найдена'], 404);
            } else {
                Response::notFound('Ссылка не найдена');
            }
            return;
        }

        // Проверяем права: автор ссылки, или admin, или manager
        $userId = Auth::id();
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);

        $canDelete = (int) $link['user_id'] === $userId || $roleId === 1 || $roleId === 2;

        if (!$canDelete) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Недостаточно прав для удаления ссылки'], 403);
            } else {
                Response::forbidden('Недостаточно прав для удаления ссылки');
            }
            return;
        }

        $taskId = (int) $link['task_id'];
        $this->linkModel->delete($linkId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            Session::flash('success', 'Ссылка удалена');
            $this->redirect("/tasks/{$taskId}");
        }
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
     * Получить MIME-тип по расширению файла
     *
     * @param string $extension Расширение файла
     * @return string MIME-тип
     */
    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Получить человекопонятное сообщение об ошибке загрузки
     *
     * @param int $errorCode Код ошибки $_FILES['file']['error']
     * @return string Текст ошибки
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимально допустимый размер (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимально допустимый размер формы',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не выбран',
            UPLOAD_ERR_NO_TMP_DIR => 'Ошибка сервера: нет временной директории',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка сервера: не удалось записать файл',
            UPLOAD_ERR_EXTENSION => 'Загрузка заблокирована расширением PHP',
            default => 'Неизвестная ошибка при загрузке файла',
        };
    }
}
