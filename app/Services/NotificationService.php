<?php
/**
 * NotificationService — Сервис уведомлений
 *
 * Создаёт уведомления при ключевых событиях:
 * назначение задачи, новый комментарий, смена статуса, загрузка файла.
 */

namespace Services;

use Helpers\Database;
use Models\Notification;

class NotificationService
{
    private Notification $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    /**
     * Создать уведомление
     *
     * @param int $userId Кому отправить уведомление
     * @param string $type Тип уведомления (task_assigned, comment_added, status_changed, file_uploaded)
     * @param string $title Заголовок
     * @param string|null $message Сообщение (опционально)
     * @param int|null $projectId ID проекта
     * @param int|null $taskId ID задачи
     * @param int|null $commentId ID комментария
     * @return void
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?int $projectId = null,
        ?int $taskId = null,
        ?int $commentId = null
    ): void {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'project_id' => $projectId,
            'task_id' => $taskId,
            'comment_id' => $commentId,
            'is_read' => 0,
        ]);
    }

    /**
     * Уведомить исполнителя о назначении на задачу
     *
     * @param int $taskId ID задачи
     * @param int $assigneeId ID назначенного исполнителя
     * @return void
     */
    public function notifyTaskAssigned(int $taskId, int $assigneeId): void
    {
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.title, t.project_id FROM tasks t WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return;
        }

        $this->create(
            $assigneeId,
            'task_assigned',
            'Вам назначена задача: ' . $task['title'],
            null,
            (int) $task['project_id'],
            $taskId
        );
    }

    /**
     * Уведомить участников задачи о новом комментарии
     *
     * @param int $taskId ID задачи
     * @param int $commentAuthorId ID автора комментария (ему не отправляем)
     * @return void
     */
    public function notifyCommentAdded(int $taskId, int $commentAuthorId): void
    {
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.title, t.project_id, t.assigned_to, t.created_by FROM tasks t WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return;
        }

        // Собираем получателей: исполнитель + создатель задачи (без автора комментария)
        $recipients = [];
        if ($task['assigned_to'] && (int) $task['assigned_to'] !== $commentAuthorId) {
            $recipients[] = (int) $task['assigned_to'];
        }
        if ($task['created_by'] && (int) $task['created_by'] !== $commentAuthorId) {
            $recipients[] = (int) $task['created_by'];
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            $this->create(
                $userId,
                'comment_added',
                'Новый комментарий в задаче: ' . $task['title'],
                null,
                (int) $task['project_id'],
                $taskId
            );
        }
    }

    /**
     * Уведомить участников задачи о смене статуса
     *
     * @param int $taskId ID задачи
     * @param string $newStatus Название нового статуса
     * @param int $changedBy ID пользователя, сменившего статус
     * @return void
     */
    public function notifyStatusChanged(int $taskId, string $newStatus, int $changedBy): void
    {
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.title, t.project_id, t.assigned_to, t.created_by FROM tasks t WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return;
        }

        // Уведомляем исполнителя и создателя (кроме того, кто сменил)
        $recipients = [];
        if ($task['assigned_to'] && (int) $task['assigned_to'] !== $changedBy) {
            $recipients[] = (int) $task['assigned_to'];
        }
        if ($task['created_by'] && (int) $task['created_by'] !== $changedBy) {
            $recipients[] = (int) $task['created_by'];
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            $this->create(
                $userId,
                'status_changed',
                'Статус задачи изменён: ' . $task['title'] . ' → ' . $newStatus,
                null,
                (int) $task['project_id'],
                $taskId
            );
        }
    }

    /**
     * Уведомить участников задачи о загрузке файла
     *
     * @param int $taskId ID задачи
     * @param int $uploadedBy ID пользователя, загрузившего файл
     * @return void
     */
    public function notifyFileUploaded(int $taskId, int $uploadedBy): void
    {
        $db = Database::getInstance();
        $task = $db->fetch(
            "SELECT t.title, t.project_id, t.assigned_to, t.created_by FROM tasks t WHERE t.id = ?",
            [$taskId]
        );

        if (!$task) {
            return;
        }

        // Уведомляем исполнителя и создателя (кроме загрузившего)
        $recipients = [];
        if ($task['assigned_to'] && (int) $task['assigned_to'] !== $uploadedBy) {
            $recipients[] = (int) $task['assigned_to'];
        }
        if ($task['created_by'] && (int) $task['created_by'] !== $uploadedBy) {
            $recipients[] = (int) $task['created_by'];
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            $this->create(
                $userId,
                'file_uploaded',
                'Новый файл в задаче: ' . $task['title'],
                null,
                (int) $task['project_id'],
                $taskId
            );
        }
    }
}
