<?php
/**
 * Форма создания/редактирования пользователя (admin)
 *
 * Переменные:
 * - $user: array|null — данные пользователя (null при создании)
 * - $roles: array — список ролей из БД
 * - $errors: array — ошибки валидации
 * - $old: array — старые значения полей (после неудачной отправки)
 */
$layout = 'layouts/app';
$isEdit = $user !== null;
$title = ($isEdit ? 'Редактирование пользователя' : 'Создание пользователя') . ' — Traking';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Заголовок -->
    <div class="flex items-center gap-4">
        <a href="<?= url('/admin/users') ?>" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">
            <?= $isEdit ? 'Редактирование пользователя' : 'Создание пользователя' ?>
        </h1>
    </div>

    <!-- Форма -->
    <form method="POST"
          action="<?= $isEdit ? url('/admin/users/' . (int)$user['id'] . '/edit') : url('/admin/users/create') ?>"
          class="bg-white rounded-lg shadow-sm border p-6 space-y-5">
        <?= csrf_field() ?>

        <!-- Имя -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                Имя <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name"
                   value="<?= e($old['name'] ?? $user['name'] ?? '') ?>"
                   required
                   placeholder="ФИО пользователя"
                   class="w-full px-4 py-2.5 border <?= !empty($errors['name']) ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' ?> rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <?php if (!empty($errors['name'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= e($errors['name'][0]) ?></p>
            <?php endif; ?>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email <span class="text-red-500">*</span>
            </label>
            <input type="email" id="email" name="email"
                   value="<?= e($old['email'] ?? $user['email'] ?? '') ?>"
                   required
                   placeholder="user@example.com"
                   class="w-full px-4 py-2.5 border <?= !empty($errors['email']) ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' ?> rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <?php if (!empty($errors['email'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= e($errors['email'][0]) ?></p>
            <?php endif; ?>
        </div>

        <!-- Логин -->
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                Логин <span class="text-red-500">*</span>
            </label>
            <input type="text" id="login" name="login"
                   value="<?= e($old['login'] ?? $user['login'] ?? '') ?>"
                   required
                   placeholder="username"
                   class="w-full px-4 py-2.5 border <?= !empty($errors['login']) ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' ?> rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <?php if (!empty($errors['login'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= e($errors['login'][0]) ?></p>
            <?php endif; ?>
        </div>

        <!-- Роль -->
        <div>
            <label for="role_id" class="block text-sm font-medium text-gray-700 mb-1">
                Роль <span class="text-red-500">*</span>
            </label>
            <select id="role_id" name="role_id" required
                    class="w-full px-4 py-2.5 border <?= !empty($errors['role_id']) ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' ?> rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="">— Выберите роль —</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id'] ?>"
                        <?= ((int)($old['role_id'] ?? $user['role_id'] ?? 0)) === (int)$role['id'] ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['role_id'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= e($errors['role_id'][0]) ?></p>
            <?php endif; ?>
        </div>

        <!-- Статус -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                Статус
            </label>
            <select id="status" name="status"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="active" <?= ($old['status'] ?? $user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                    Активен
                </option>
                <option value="inactive" <?= ($old['status'] ?? $user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                    Неактивен
                </option>
            </select>
        </div>

        <?php if (!$isEdit): ?>
            <!-- Подсказка о пароле при создании -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-700">
                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Пароль будет сгенерирован автоматически и показан после создания.
                </p>
            </div>
        <?php endif; ?>

        <!-- Кнопки действий -->
        <div class="flex items-center justify-between pt-4 border-t">
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Сохранить
                </button>
                <a href="<?= url('/admin/users') ?>"
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    Отмена
                </a>
            </div>

            <?php if ($isEdit): ?>
                <!-- Кнопка сброса пароля при редактировании -->
                <form method="POST" action="<?= url('/admin/users/' . (int)$user['id'] . '/reset-password') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="px-4 py-2.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-sm font-medium rounded-lg border border-yellow-200 transition"
                            onclick="return confirm('Сбросить пароль? Новый пароль будет показан в уведомлении.')">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Сбросить пароль
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</div>
