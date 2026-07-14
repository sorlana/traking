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
        <div x-data="{ pushActive: <?= $settings['push_enabled'] ? 'true' : 'false' ?>, subscribing: false, pushStatus: '' }">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Push-уведомления</h3>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="push_enabled" value="1"
                       x-model="pushActive"
                       @change="if(pushActive) { subscribing=true; pushStatus='Подписка...'; activatePush().then(ok => { subscribing=false; pushStatus=ok?'✓ Подключено':'✗ Ошибка'; }); } else { pushStatus=''; }"
                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Получать push-уведомления на устройство</span>
            </label>
            <span x-show="pushStatus" x-text="pushStatus" class="text-xs ml-7 mt-1 block" :class="pushStatus.includes('✓') ? 'text-green-600' : pushStatus.includes('✗') ? 'text-red-600' : 'text-gray-500'"></span>
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
        </div>
    </form>
</div>

<script>
async function activatePush() {
    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;

        // Запрашиваем разрешение если ещё не дано
        if (Notification.permission === 'default') {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') return false;
        } else if (Notification.permission === 'denied') {
            alert('Уведомления заблокированы в настройках браузера. Разрешите их вручную.');
            return false;
        }

        // Регистрируем SW и не ждём ready — используем registration напрямую
        const registration = await navigator.serviceWorker.register(BASE_URL + '/service-worker.js', { scope: BASE_URL + '/' });
        
        // Ждём пока SW станет active (до 15 сек)
        let attempts = 0;
        while (!registration.active && attempts < 30) {
            await new Promise(r => setTimeout(r, 500));
            attempts++;
        }
        if (!registration.active) {
            console.error('[Push] SW did not activate after 15s');
            return false;
        }

        // Получаем VAPID key
        const resp = await fetch(BASE_URL + '/push/vapid-key', { headers: {'X-Requested-With':'XMLHttpRequest'} });
        const { publicKey } = await resp.json();
        if (!publicKey) return false;

        // Подписываемся
        const applicationServerKey = urlBase64ToUint8Array(publicKey);
        const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey });
        const sub = subscription.toJSON();

        // Отправляем на сервер
        const formData = new FormData();
        formData.append('endpoint', sub.endpoint);
        formData.append('p256dh', sub.keys.p256dh);
        formData.append('auth', sub.keys.auth);
        const saveResp = await fetch(BASE_URL + '/push/subscribe', { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest'}, body: formData });

        return saveResp.ok;
    } catch(e) {
        console.error('[Push] activatePush error:', e);
        alert('Ошибка подписки: ' + e.message);
        return false;
    }
}
</script>
