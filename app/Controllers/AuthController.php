<?php
/**
 * AuthController — Контроллер авторизации
 *
 * Обрабатывает:
 * - GET /login — показ формы входа
 * - POST /login — обработка входа
 * - GET /logout — выход из системы
 *
 * Пример использования (в routes.php):
 *   $router->get('/login', [Controllers\AuthController::class, 'showLogin']);
 *   $router->post('/login', [Controllers\AuthController::class, 'login']);
 *   $router->get('/logout', [Controllers\AuthController::class, 'logout']);
 */

namespace Controllers;

use Services\AuthService;
use Services\RateLimiter;
use Helpers\Auth;
use Helpers\Session;
use Helpers\Response;

class AuthController extends Controller
{
    /** @var AuthService Сервис авторизации */
    private AuthService $authService;

    /** @var RateLimiter Rate limiter для защиты от brute force */
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Показать форму входа
     *
     * Если пользователь уже авторизован — редирект на /dashboard.
     *
     * @return void
     */
    public function showLogin(): void
    {
        // Если уже авторизован — на дашборд
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth/login');
    }

    /**
     * Обработка POST-формы входа
     *
     * 1. Валидация входных данных
     * 2. Проверка rate limiting
     * 3. Авторизация через AuthService
     * 4. Редирект на /dashboard или назад с ошибкой
     *
     * @return void
     */
    public function login(): void
    {
        // Если уже авторизован — на дашборд
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        // Базовая валидация
        if ($login === '' || $password === '') {
            Session::flash('error', 'Заполните все поля');
            Session::flash('old_login', $login);
            $this->redirect('/login');
            return;
        }

        // Проверка CSRF-токена
        if (!csrf_verify()) {
            Session::flash('error', 'Ошибка безопасности. Попробуйте ещё раз.');
            Session::flash('old_login', $login);
            $this->redirect('/login');
            return;
        }

        // Rate limiting — защита от brute force по IP
        $rateLimitKey = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey)) {
            Session::flash('error', 'Слишком много попыток входа. Подождите 15 минут.');
            Session::flash('old_login', $login);
            $this->redirect('/login');
            return;
        }

        // Попытка авторизации
        $result = $this->authService->login($login, $password);

        if (!$result['success']) {
            // Фиксируем неудачную попытку
            $this->rateLimiter->hit($rateLimitKey);

            Session::flash('error', $result['error']);
            Session::flash('old_login', $login);
            $this->redirect('/login');
            return;
        }

        // Успешный вход — очищаем счётчик попыток
        $this->rateLimiter->clear($rateLimitKey);

        // Редирект на дашборд
        $this->redirect('/dashboard');
    }

    /**
     * Выход из системы
     *
     * @return void
     */
    public function logout(): void
    {
        $this->authService->logout();
        // После destroy сессии нужно начать новую для flash-сообщений
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        Session::flash('success', 'Вы вышли из системы');
        $this->redirect('/login');
    }
}
