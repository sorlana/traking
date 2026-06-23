<?php
/**
 * Список пользователей (admin)
 * Фильтры: роль, статус, поиск
 */
$layout = 'layouts/app';
$title = 'Пользователи — Traking';
?>

<div class="space-y-6">
    <!-- Заголовок и кнопка создания -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Пользователи</h1>
        <a href="<?= url('/admin/users/create') ?>"
           class="ui-btn ui-btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать пользователя
        </a>
    </div>

    <!-- Фильтры -->
    <form method="GET" action="<?= url('/admin/users') ?>" class="bg-white rounded-lg shadow-sm border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Фильтр по роли -->
            <div>
                <label for="filter-role" class="block text-xs font-medium text-gray-500 mb-1">Роль</label>
                <select id="filter-role" name="role"
                        class="ui-control">
                    <option value="">Все роли</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role['code']) ?>"
                            <?= ($filters['role'] ?? '') === $role['code'] ? 'selected' : '' ?>>
                            <?= e($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Фильтр по статусу -->
            <div>
                <label for="filter-status" class="block text-xs font-medium text-gray-500 mb-1">Статус</label>
                <select id="filter-status" name="status"
                        class="ui-control">
                    <option value="">Все</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Неактивные</option>
                </select>
            </div>

            <!-- Поиск -->
            <div>
                <label for="filter-search" class="block text-xs font-medium text-gray-500 mb-1">Поиск</label>
                <input type="text" id="filter-search" name="search"
                       value="<?= e($filters['search'] ?? '') ?>"
                       placeholder="Имя, email или логин"
                       class="ui-control">
            </div>

            <!-- Кнопки -->
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="ui-btn ui-btn-dark">
                    Фильтровать
                </button>
                <a href="<?= url('/admin/users') ?>"
                   class="ui-btn ui-btn-secondary">
                    Сбросить
                </a>
            </div>
        </div>
    </form>

    <!-- Таблица пользователей -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <?php if (empty($users)): ?>
            <div class="p-8 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p>Пользователи не найдены</p>
            </div>
        <?php else: ?>
            <!-- Десктоп-таблица -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Имя</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Логин</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Роль</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Статус</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Создан</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Последний вход</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900"><?= e($u['name']) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= e($u['email']) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= e($u['login']) ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $roleBadgeColors = [
                                        'admin' => 'bg-purple-100 text-purple-700',
                                        'manager' => 'bg-blue-100 text-blue-700',
                                        'executor' => 'bg-gray-100 text-gray-700',
                                    ];
                                    $badgeColor = $roleBadgeColors[$u['role_code'] ?? ''] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded <?= $badgeColor ?>">
                                        <?= e($u['role_name']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            Активен
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                            Неактивен
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    <?= date('d.m.Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    <?= $u['last_login_at'] ? date('d.m.Y H:i', strtotime($u['last_login_at'])) : '—' ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Редактировать -->
                                        <a href="<?= url('/admin/users/' . (int)$u['id'] . '/edit') ?>"
                                           class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded"
                                           title="Редактировать">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>

                                        <!-- Активировать/Деактивировать -->
                                        <form method="POST" action="<?= url('/admin/users/' . (int)$u['id'] . '/toggle-status') ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit"
                                                    class="p-1.5 rounded <?= $u['status'] === 'active' ? 'text-gray-500 hover:text-orange-600 hover:bg-orange-50' : 'text-gray-500 hover:text-green-600 hover:bg-green-50' ?>"
                                                    title="<?= $u['status'] === 'active' ? 'Деактивировать' : 'Активировать' ?>"
                                                    onclick="return confirm('<?= $u['status'] === 'active' ? 'Деактивировать пользователя?' : 'Активировать пользователя?' ?>')">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <!-- Сбросить пароль -->
                                        <form method="POST" action="<?= url('/admin/users/' . (int)$u['id'] . '/reset-password') ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit"
                                                    class="p-1.5 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded"
                                                    title="Сбросить пароль"
                                                    onclick="return confirm('Сбросить пароль пользователя «<?= e($u['login']) ?>»?')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Мобильные карточки -->
            <div class="lg:hidden divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900"><?= e($u['name']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($u['login']) ?> · <?= e($u['email']) ?></div>
                            </div>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                    Активен
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                                    <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                    Неактивен
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php
                            $badgeColor = $roleBadgeColors[$u['role_code'] ?? ''] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded <?= $badgeColor ?>">
                                <?= e($u['role_name']) ?>
                            </span>
                            <span class="text-xs text-gray-400">
                                Создан: <?= date('d.m.Y', strtotime($u['created_at'])) ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <a href="<?= url('/admin/users/' . (int)$u['id'] . '/edit') ?>"
                               class="text-xs text-blue-600 hover:text-blue-700 font-medium">Изменить</a>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="<?= url('/admin/users/' . (int)$u['id'] . '/toggle-status') ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs font-medium <?= $u['status'] === 'active' ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700' ?>"
                                        onclick="return confirm('<?= $u['status'] === 'active' ? 'Деактивировать?' : 'Активировать?' ?>')">
                                    <?= $u['status'] === 'active' ? 'Деактивировать' : 'Активировать' ?>
                                </button>
                            </form>
                            <span class="text-gray-300">|</span>
                            <form method="POST" action="<?= url('/admin/users/' . (int)$u['id'] . '/reset-password') ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs text-yellow-600 hover:text-yellow-700 font-medium"
                                        onclick="return confirm('Сбросить пароль?')">
                                    Сбросить пароль
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Количество -->
    <div class="text-sm text-gray-500">
        Всего: <?= count($users) ?> <?= count($users) === 1 ? 'пользователь' : 'пользователей' ?>
    </div>
</div>
