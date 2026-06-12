<?php
/**
 * NotificationController — Контроллер уведомлений
 *
 * Функции: список уведомлений, пометка прочитанными, AJAX-эндпоинты
 * для колокольчика (количество непрочитанных + список последних).
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Response;
use Helpers\Session;
use Models\Notification;

class NotificationController extends Controller
{
    private Notification $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    /**
     * Страница всех уведомлений
     * GET /notifications
     */
    public function index(): void
    {
        $userId = Auth::id();
        $onlyUnread = isset($_GET['filter']) && $_GET['filter'] === 'unread';

        $notifications = $this->notificationModel->getByUser($userId, $onlyUnread, 100);
        $unreadCount = $this->notificationModel->countUnread($userId);

        $this->view('notifications/index', [
            'title' => 'Уведомления — Traking',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $onlyUnread ? 'unread' : 'all',
        ]);
    }

    /**
     * Пометить уведомление как прочитанное
     * POST /notifications/{id}/read
     *
     * @param string $id ID уведомления
     */
    public function markRead(string $id): void
    {
        $notificationId = (int) $id;
        $userId = Auth::id();

        // Проверяем что уведомление принадлежит текущему пользователю
        $notification = $this->notificationModel->find($notificationId);
        if (!$notification || (int) $notification['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Уведомление не найдено'], 404);
            } else {
                Response::notFound('Уведомление не найдено');
            }
            return;
        }

        $this->notificationModel->markRead($notificationId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $this->redirect('/notifications');
        }
    }

    /**
     * Пометить все уведомления как прочитанные
     * POST /notifications/read-all
     */
    public function markAllRead(): void
    {
        $userId = Auth::id();
        $this->notificationModel->markAllRead($userId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            Session::flash('success', 'Все уведомления отмечены как прочитанные');
            $this->redirect('/notifications');
        }
    }

    /**
     * AJAX: количество непрочитанных уведомлений
     * GET /ajax/notifications/count
     */
    public function ajaxCount(): void
    {
        $userId = Auth::id();
        $count = $this->notificationModel->countUnread($userId);
        $this->json(['count' => $count]);
    }

    /**
     * AJAX: список последних уведомлений
     * GET /ajax/notifications/list
     */
    public function ajaxList(): void
    {
        $userId = Auth::id();
        $notifications = $this->notificationModel->getByUser($userId, false, 10);

        $items = array_map(function ($n) {
            return [
                'id' => (int) $n['id'],
                'type' => $n['type'],
                'title' => $n['title'],
                'message' => $n['message'],
                'task_id' => $n['task_id'] ? (int) $n['task_id'] : null,
                'is_read' => (bool) $n['is_read'],
                'created_at' => $n['created_at'],
            ];
        }, $notifications);

        $this->json(['items' => $items]);
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
