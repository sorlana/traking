<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Traking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    blue: { 50: '#f7f7f8', 100: '#f1f2f3', 200: '#e2e4e7', 300: '#4f6bed', 400: '#4f6bed', 500: '#4f6bed', 600: '#4f6bed', 700: '#4f6bed', 800: '#4f6bed', 900: '#4f6bed' },
                    indigo: { 50: '#f7f7f8', 100: '#f1f2f3', 200: '#e2e4e7', 300: '#4f6bed', 400: '#4f6bed', 500: '#4f6bed', 600: '#4f6bed', 700: '#4f6bed', 800: '#4f6bed', 900: '#4f6bed' }
                }
            }
        }
    }
    </script>
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>?v=2">
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <!-- Логотип / название -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Traking</h1>
            <p class="text-gray-500 mt-1">Управление проектами и задачами</p>
        </div>

        <!-- Карточка формы -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">Вход в систему</h2>

            <?php
            // Отображение ошибок
            $error = \Helpers\Session::getFlash('error');
            $success = \Helpers\Session::getFlash('success');
            $oldLogin = \Helpers\Session::getFlash('old_login', '');
            ?>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/login') ?>" autocomplete="on">
                <?= csrf_field() ?>

                <!-- Поле логин -->
                <div class="mb-4">
                    <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                        Логин или Email
                    </label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        value="<?= e($oldLogin) ?>"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Введите логин или email"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               transition duration-150"
                    >
                </div>

                <!-- Поле пароль -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Пароль
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Введите пароль"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               transition duration-150"
                    >
                </div>

                <!-- Кнопка входа -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4
                           rounded-lg transition duration-150 focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:ring-offset-2"
                >
                    Войти
                </button>
            </form>
        </div>

        <!-- Подвал -->
        <p class="text-center text-gray-400 text-sm mt-6">
            &copy; <?= date('Y') ?> Traking. Система управления проектами.
        </p>
    </div>

</body>
</html>
