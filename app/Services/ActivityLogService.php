<?php
/**
 * ActivityLogService — Сервис журнала действий
 *
 * Централизованное логирование действий пользователей:
 * создание задач, смена статусов, назначение, комментарии, файлы.
 */

namespace Services;

use Models\ActivityLog;

class ActivityLogService
{
    private ActivityLog $activityLog;

    public function __construct()
    {
        $this->activityLog = new ActivityLog();
    }

    /**
     * Записать действие в журнал
     *
     * @param int $userId ID пользователя, совершившего действие
     * @param int|null $projectId ID проекта (если связано)
     * @param int|null $taskId ID задачи (если связано)
     * @param string $actionType Тип действия (task_created, status_changed и т.д.)
     * @param string|null $oldValue Старое значение (опционально)
     * @param string|null $newValue Новое значение (опционально)
     * @return void
     */
    public function log(
        int $userId,
        ?int $projectId,
        ?int $taskId,
        string $actionType,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        $this->activityLog->create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'task_id' => $taskId,
            'action_type' => $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
