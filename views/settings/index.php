<?php
/**
 * Страница настроек пользователя
 * Push-уведомления, расписание, режим "не беспокоить"
 */
$layout = 'layouts/app';
$days = explode(',', $settings['schedule_days'] ?? '1,2,3,4,5');
$dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Настройки</h1>

    <form method="POST" action="<?= url('/settings') ?>" class="bg-white rounded-lg shadow-sm border p-6 space-y-6">
        <?= csrf_field() ?>

        <!-- Push-уведомления -->
        <div>
            <h3 class="text-sm font-medium text-gray-700 mb-3">Push-уведомления</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="push_enabled" value="1"
                       <?= $settings['push_enabled'] ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Получать push-уведомления на устройство</span>
            </label>
        </div>

        <!-- Звуковые уведомления -->
        <div class="border-t pt-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Звук</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="sound_enabled" value="1"
                       <?= ($settings['sound_enabled'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Включить звук при новых сообщениях и смене статуса</span>
            </label>
        </div>

        <!-- Расписание уведомлений -->
        <div class="border-t pt-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Расписание уведомлений</h3>
            <label class="flex items-center gap-3 cursor-pointer mb-4">
                <input type="checkbox" name="schedule_enabled" value="1"
                       <?= $settings['schedule_enabled'] ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Получать уведомления только в рабочее время</span>
            </label>

            <div class="grid grid-cols-2 gap-4 ml-7">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">С</label>
                    <input type="time" name="schedule_start"
                           value="<?= e(substr($settings['schedule_start'] ?? '09:00:00', 0, 5)) ?>"
                           class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">До</label>
                    <input type="time" name="schedule_end"
                           value="<?= e(substr($settings['schedule_end'] ?? '18:00:00', 0, 5)) ?>"
                           class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-4 ml-7">
                <label class="text-xs text-gray-500 block mb-2">Дни недели</label>
                <div class="flex gap-2 flex-wrap">
                    <?php foreach ($dayNames as $num => $name): ?>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="schedule_days[]" value="<?= $num ?>"
                                   <?= in_array((string)$num, $days) ? 'checked' : '' ?>
                                   class="w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <span class="text-xs text-gray-600"><?= $name ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t">
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                Сохранить
            </button>
        </div>
    </form>
</div>
