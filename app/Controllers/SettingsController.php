<?php
/**
 * SettingsController — Управление настройками пользователя
 *
 * Раздел «Настройки»: push-уведомления, расписание, режим "не беспокоить".
 */

namespace Controllers;

use Helpers\Auth;
use Helpers\Database;
use Helpers\Session;

class SettingsController extends Controller
{
    /**
     * Страница настроек
     */
    public function index(): void
    {
        $settings = $this->getUserSettings(Auth::id());
        $this->view('settings/index', [
            'title' => 'Настройки — Traking',
            'settings' => $settings,
        ]);
    }

    /**
     * Сохранение настроек (POST)
     */
    public function update(): void
    {
        $userId = Auth::id();
        $db = Database::getInstance();

        $pushEnabled = isset($_POST['push_enabled']) ? 1 : 0;
        $soundEnabled = isset($_POST['sound_enabled']) ? 1 : 0;
        $scheduleEnabled = isset($_POST['schedule_enabled']) ? 1 : 0;
        $scheduleStart = $_POST['schedule_start'] ?? '09:00';
        $scheduleEnd = $_POST['schedule_end'] ?? '18:00';
        $scheduleDays = implode(',', $_POST['schedule_days'] ?? [1, 2, 3, 4, 5]);

        // Upsert настроек
        $existing = $db->fetch("SELECT id FROM user_settings WHERE user_id = ?", [$userId]);

        if ($existing) {
            $db->update('user_settings', [
                'push_enabled' => $pushEnabled,
                'sound_enabled' => $soundEnabled,
                'schedule_enabled' => $scheduleEnabled,
                'schedule_start' => $scheduleStart . ':00',
                'schedule_end' => $scheduleEnd . ':00',
                'schedule_days' => $scheduleDays,
            ], 'user_id = ?', [$userId]);
        } else {
            $db->insert('user_settings', [
                'user_id' => $userId,
                'push_enabled' => $pushEnabled,
                'sound_enabled' => $soundEnabled,
                'schedule_enabled' => $scheduleEnabled,
                'schedule_start' => $scheduleStart . ':00',
                'schedule_end' => $scheduleEnd . ':00',
                'schedule_days' => $scheduleDays,
            ]);
        }

        Session::flash('success', 'Настройки сохранены');
        $this->redirect('/settings');
    }

    /**
     * Переключить режим DND (AJAX)
     */
    public function toggleDnd(): void
    {
        $userId = Auth::id();
        $db = Database::getInstance();

        $settings = $this->getUserSettings($userId);
        $newDnd = $settings['dnd_enabled'] ? 0 : 1;

        $existing = $db->fetch("SELECT id FROM user_settings WHERE user_id = ?", [$userId]);
        if ($existing) {
            $db->update('user_settings', ['dnd_enabled' => $newDnd], 'user_id = ?', [$userId]);
        } else {
            $db->insert('user_settings', ['user_id' => $userId, 'dnd_enabled' => $newDnd]);
        }

        $this->json(['success' => true, 'dnd_enabled' => $newDnd]);
    }

    /**
     * Получить настройки пользователя (или значения по умолчанию)
     */
    private function getUserSettings(int $userId): array
    {
        $db = Database::getInstance();
        $settings = $db->fetch("SELECT * FROM user_settings WHERE user_id = ?", [$userId]);

        return $settings ?: [
            'push_enabled' => 1,
            'sound_enabled' => 0,
            'dnd_enabled' => 0,
            'schedule_enabled' => 0,
            'schedule_start' => '09:00:00',
            'schedule_end' => '18:00:00',
            'schedule_days' => '1,2,3,4,5',
        ];
    }
}
