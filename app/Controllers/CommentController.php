<?php
/**
 * CommentController — Контроллер комментариев к задачам (AJAX)
 *
 * Функции: добавление, редактирование, удаление комментариев.
 * Все методы поддерживают как AJAX (JSON-ответ), так и обычные запросы (redirect).
 * Доступ проверяется через TaskAccessMiddleware.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Response;
use Helpers\Session;
use Middleware\TaskAccessMiddleware;
use Models\TaskComment;
use Models\Task;
use Services\NotificationService;
use Services\ActivityLogService;

class CommentController extends Controller
{
    private TaskComment $commentModel;
    private Task $taskModel;
    private NotificationService $notificationService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->commentModel = new TaskComment();
        $this->taskModel = new Task();
        $this->notificationService = new NotificationService();
        $this->activityLogService = new ActivityLogService();
    }

    /**
     * Добавить комментарий к задаче
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

        // Валидация: обязательное поле, макс. 5000 символов
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

        // Создаём комментарий
        $data = [
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment_text' => $commentText,
            'parent_comment_id' => !empty($_POST['parent_comment_id']) ? (int) $_POST['parent_comment_id'] : null,
        ];

        $commentId = $this->commentModel->create($data);

        // Логируем добавление комментария
        $this->activityLogService->log(
            Auth::id(),
            (int) $task['project_id'],
            $taskId,
            'comment_added',
            null,
            mb_substr($commentText, 0, 100)
        );

        // Уведомляем участников задачи
        $this->notificationService->notifyCommentAdded($taskId, Auth::id());

        // Получаем созданный комментарий с именем автора
        $user = Auth::user();
        $comment = [
            'id' => $commentId,
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment_text' => $commentText,
            'user_name' => $user['name'] ?? $user['login'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->isAjax()) {
            $this->json(['success' => true, 'comment' => $comment]);
        } else {
            Session::flash('success', 'Комментарий добавлен');
            $this->redirect("/tasks/{$taskId}");
        }
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

        // Обновляем комментарий
        $this->commentModel->update($commentId, ['comment_text' => $commentText]);

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
