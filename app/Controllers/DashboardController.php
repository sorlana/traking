<?php
/**
 * DashboardController — Контроллер дашборда
 *
 * Отображает разные дашборды в зависимости от роли пользователя:
 * Admin, Manager, Executor — каждый со своими виджетами и данными.
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Models\ActivityLog;

class DashboardController extends Controller
{
    /**
     * Главная страница дашборда
     * Определяет роль и рендерит соответствующий шаблон
     *
     * GET /dashboard
     */
    public function index(): void
    {
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $db = Database::getInstance();
        $activityLog = new ActivityLog();

        switch ($roleId) {
            case 1: // Администратор
                $this->renderAdmin($db, $activityLog);
                break;
            case 2: // Руководитель
                $this->renderUnified($user);
                break;
            case 3: // Исполнитель
                $this->renderUnified($user);
                break;
            default:
                $this->redirect('/login');
        }
    }

    /**
     * Дашборд администратора
     */
    private function renderAdmin(Database $db, ActivityLog $activityLog): void
    {
        // Статистика
        $totalProjects = $db->fetch("SELECT COUNT(*) as cnt FROM projects")['cnt'] ?? 0;
        $totalTasks = $db->fetch("SELECT COUNT(*) as cnt FROM tasks")['cnt'] ?? 0;
        $totalUsers = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE status = 'active'")['cnt'] ?? 0;
        $todayActivity = $db->fetch(
            "SELECT COUNT(*) as cnt FROM activity_log WHERE DATE(created_at) = CURDATE()"
        )['cnt'] ?? 0;

        // Последние действия
        $recentActivity = $activityLog->getAll(15);

        // Просроченные задачи
        $overdueTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, ts.code as status_code,
                    u.name as assigned_name, p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             JOIN projects p ON t.project_id = p.id
             WHERE t.deadline < CURDATE()
               AND ts.code NOT IN ('done')
             ORDER BY t.deadline ASC
             LIMIT 10"
        );

        $this->view('dashboard/admin', [
            'title' => 'Дашборд — Traking',
            'totalProjects' => (int) $totalProjects,
            'totalTasks' => (int) $totalTasks,
            'totalUsers' => (int) $totalUsers,
            'todayActivity' => (int) $todayActivity,
            'recentActivity' => $recentActivity,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    /**
     * Единый дашборд для Manager и Executor
     * Использует DashboardService для формирования данных канбан-доски
     */
    private function renderUnified(array $user): void
    {
        $userId = (int) $user['id'];
        $roleId = (int) $user['role_id'];

        $dashboardService = new \Services\DashboardService();
        $data = $dashboardService->getBoardData($userId, $roleId);

        $this->view('dashboard/unified', [
            'title'     => 'Дашборд — Traking',
            'projects'  => $data['projects'],
            'boardData' => $data['boardData'],
            'timeData'  => $data['timeData'],
            'roleId'    => $roleId,
        ]);
    }

    /**
     * Дашборд руководителя
     * @deprecated Заменён на renderUnified(), оставлен для возможного отката
     */
    private function renderManager(Database $db, ActivityLog $activityLog, array $user): void
    {
        $userId = (int) $user['id'];

        // Мои проекты
        $myProjects = $db->fetchAll(
            "SELECT p.*, ps.name as status_name,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count
             FROM projects p
             JOIN project_statuses ps ON p.status_id = ps.id
             JOIN project_users pu ON pu.project_id = p.id AND pu.user_id = ?
             ORDER BY p.updated_at DESC
             LIMIT 5",
            [$userId]
        );

        // Задачи со статусом «Готово» (руководитель проверяет)
        $reviewTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, u.name as assigned_name, p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             JOIN projects p ON t.project_id = p.id
             JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
             WHERE ts.code = 'done'
             ORDER BY t.updated_at DESC
             LIMIT 10",
            [$userId]
        );

        // Просроченные задачи
        $overdueTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, u.name as assigned_name, p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             LEFT JOIN users u ON t.assigned_to = u.id
             JOIN projects p ON t.project_id = p.id
             JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
             WHERE t.deadline < CURDATE()
               AND ts.code NOT IN ('done')
             ORDER BY t.deadline ASC
             LIMIT 10",
            [$userId]
        );

        // Новые комментарии (в задачах моих проектов, за последние 3 дня)
        $recentComments = $db->fetchAll(
            "SELECT tc.*, u.name as user_name, t.title as task_title, t.id as task_id
             FROM task_comments tc
             JOIN users u ON tc.user_id = u.id
             JOIN tasks t ON tc.task_id = t.id
             JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
             WHERE tc.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
             ORDER BY tc.created_at DESC
             LIMIT 10",
            [$userId]
        );

        $this->view('dashboard/manager', [
            'title' => 'Дашборд — Traking',
            'myProjects' => $myProjects,
            'reviewTasks' => $reviewTasks,
            'overdueTasks' => $overdueTasks,
            'recentComments' => $recentComments,
        ]);
    }

    /**
     * Дашборд исполнителя
     * @deprecated Заменён на renderUnified(), оставлен для возможного отката
     */
    private function renderExecutor(Database $db, array $user): void
    {
        $userId = (int) $user['id'];

        // Мои задачи по статусам (активные: в работе и доработки)
        $myTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, ts.code as status_code,
                    p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             JOIN projects p ON t.project_id = p.id
             WHERE t.assigned_to = ?
               AND ts.code NOT IN ('done')
             ORDER BY FIELD(ts.code, 'in_progress', 'revision'), t.deadline ASC",
            [$userId]
        );

        // Задачи со статусом «Готово» (мои завершённые)
        $reviewTasks = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             JOIN projects p ON t.project_id = p.id
             WHERE t.assigned_to = ? AND ts.code = 'done'
             ORDER BY t.updated_at DESC",
            [$userId]
        );

        // Задачи на доработке (мои)
        $newAssigned = $db->fetchAll(
            "SELECT t.*, ts.name as status_name, p.title as project_title
             FROM tasks t
             JOIN task_statuses ts ON t.status_id = ts.id
             JOIN projects p ON t.project_id = p.id
             WHERE t.assigned_to = ?
               AND ts.code = 'revision'
             ORDER BY t.updated_at DESC
             LIMIT 10",
            [$userId]
        );

        $this->view('dashboard/executor', [
            'title' => 'Дашборд — Traking',
            'myTasks' => $myTasks,
            'reviewTasks' => $reviewTasks,
            'newAssigned' => $newAssigned,
        ]);
    }
}
