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
        <div x-data="pushSettings(<?= $settings['push_enabled'] ? 'true' : 'false' ?>)" x-init="init()">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Push-уведомления</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="push_enabled" value="1"
                       x-model="accountEnabled"
                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Разрешить push-уведомления для аккаунта</span>
            </label>

            <div class="ml-7 mt-3 flex items-center gap-3 flex-wrap">
                <button type="button"
                        @click="toggleDevice()"
                        :disabled="subscribing || !supported"
                        x-text="deviceSubscribed ? 'Отключить это устройство' : 'Подключить это устройство'"
                        class="ui-btn ui-btn-secondary text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                </button>
            </div>
            <span x-show="pushStatus" x-text="pushStatus" class="text-xs ml-7 mt-2 block"
                  :class="pushState === 'connected' ? 'text-green-600' : pushState === 'error' ? 'text-red-600' : 'text-gray-500'"></span>
            <p class="text-xs text-gray-400 ml-7 mt-1">
                Общий переключатель действует на все устройства. Кнопка выше управляет только текущим устройством.
            </p>
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
                           class="ui-control">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">До</label>
                    <input type="time" name="schedule_end"
                           value="<?= e(substr($settings['schedule_end'] ?? '18:00:00', 0, 5)) ?>"
                           class="ui-control">
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
                    class="ui-btn ui-btn-primary">
                Сохранить
            </button>
            <a href="<?= url('/push/diag') ?>" class="ml-4 text-xs text-gray-400 hover:text-blue-600">Диагностика push</a>
        </div>
    </form>
</div>

<script>
function pushSettings(serverEnabled) {
    return {
        accountEnabled: serverEnabled,
        deviceSubscribed: false,
        subscribing: true,
        supported: true,
        pushStatus: 'Проверка подписки...',
        pushState: 'checking',

        async init() {
            this.supported = window.PushNotifications?.isSupported() ?? false;
            if (!this.supported) {
                this.subscribing = false;
                this.pushStatus = 'Push-уведомления не поддерживаются этим браузером';
                this.pushState = 'error';
                return;
            }

            try {
                let state = await window.PushNotifications.getState();

                // Разрешение браузера сохранилось, но endpoint мог исчезнуть
                // после переустановки PWA. В этом случае восстанавливаем его
                // без повторного системного запроса.
                if (serverEnabled && state.subscribed) {
                    // Локальная подписка могла сохраниться, а серверная запись —
                    // исчезнуть после временного 404/410 от push-провайдера.
                    await window.PushNotifications.saveSubscription(state.subscription);
                } else if (serverEnabled && state.permission === 'granted') {
                    await window.PushNotifications.ensureSubscription();
                    state = await window.PushNotifications.getState();
                }

                this.deviceSubscribed = state.subscribed;

                if (this.deviceSubscribed && this.accountEnabled) {
                    this.pushStatus = '✓ Подключено на этом устройстве';
                    this.pushState = 'connected';
                } else if (this.deviceSubscribed) {
                    this.pushStatus = 'Устройство подключено, но push выключен для аккаунта';
                    this.pushState = 'needs-action';
                } else if (state.permission === 'denied') {
                    this.pushStatus = '✗ Уведомления заблокированы в настройках браузера';
                    this.pushState = 'error';
                } else if (this.accountEnabled) {
                    // Настройка сохранилась на сервере, но после переустановки
                    // этому устройству требуется новая браузерная подписка.
                    this.pushStatus = 'Требуется повторное подключение на этом устройстве';
                    this.pushState = 'needs-action';
                } else {
                    this.pushStatus = '';
                    this.pushState = 'idle';
                }
            } catch (error) {
                console.error('[Push] State check failed:', error);
                this.pushStatus = '✗ Не удалось проверить подписку';
                this.pushState = 'error';
            } finally {
                this.subscribing = false;
            }
        },

        async toggleDevice() {
            this.subscribing = true;

            try {
                if (this.deviceSubscribed) {
                    this.pushStatus = 'Отключение...';
                    this.pushState = 'checking';
                    await window.PushNotifications.unsubscribe();
                    this.deviceSubscribed = false;
                    this.pushStatus = 'Отключено на этом устройстве';
                    this.pushState = 'idle';
                } else {
                    this.pushStatus = 'Подключение...';
                    this.pushState = 'checking';
                    await window.PushNotifications.ensureSubscription({ requestPermission: true });
                    this.deviceSubscribed = true;

                    if (!this.accountEnabled) {
                        this.accountEnabled = true;
                        this.pushStatus = '✓ Устройство подключено. Нажмите «Сохранить»';
                    } else {
                        this.pushStatus = '✓ Подключено на этом устройстве';
                    }
                    this.pushState = 'connected';
                }
            } catch (error) {
                console.error('[Push] Device toggle failed:', error);
                this.pushStatus = error.message || '✗ Ошибка подключения';
                this.pushState = 'error';
            } finally {
                this.subscribing = false;
            }
        },
    };
}
</script>
