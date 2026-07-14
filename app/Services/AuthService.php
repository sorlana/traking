<?php
/**
 * AuthService — Сервис авторизации
 *
 * Централизованная логика входа/выхода/проверки токенов.
 * Используется в AuthController и AuthMiddleware.
 *
 * Логика входа:
 *   1. Находит пользователя по login (или email)
 *   2. Проверяет status = 'active'
 *   3. Проверяет пароль через password_verify
 *   4. Устанавливает пользователя в Auth (сессию)
 *   5. Генерирует токен и ставит cookie
 *   6. Обновляет last_login_at
 *
 * Пример использования:
 *   $authService = new AuthService();
 *   $result = $authService->login('admin', 'password123');
 *   if ($result['success']) { redirect('/dashboard'); }
 */

namespace Services;

use Models\User;
use Models\AuthToken;
use Helpers\Auth;
use Helpers\Session;

class AuthService
{
    /** @var User Модель пользователя */
    private User $userModel;

    /** @var AuthToken Модель токенов */
    private AuthToken $tokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tokenModel = new AuthToken();
    }

    /**
     * Авторизация пользователя по логину и паролю
     *
     * @param string $login Логин или email
     * @param string $password Пароль (plain-text)
     * @return array ['success' => bool, 'error' => string, 'user' => array|null]
     */
    public function login(string $login, string $password): array
    {
        // 1. Ищем пользователя по логину или email
        $user = $this->userModel->findByLogin($login);

        if ($user === null) {
            return [
                'success' => false,
                'error'   => 'Неверный логин или пароль',
                'user'    => null,
            ];
        }

        // 2. Проверяем статус — пользователь должен быть активен
        if (!$this->userModel->isActive($user)) {
            return [
                'success' => false,
                'error'   => 'Ваш аккаунт заблокирован. Обратитесь к администратору.',
                'user'    => null,
            ];
        }

        // 3. Проверяем пароль
        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error'   => 'Неверный логин или пароль',
                'user'    => null,
            ];
        }

        // 4. Авторизация успешна — устанавливаем пользователя в сессию
        Auth::setUser($user);

        // 5. Генерируем токен для «запомнить меня» (cookie)
        $this->generateToken((int) $user['id']);

        // 6. Обновляем время последнего входа
        $this->userModel->updateLastLogin((int) $user['id']);

        return [
            'success' => true,
            'error'   => '',
            'user'    => $user,
        ];
    }

    /**
     * Выход из системы
     *
     * Удаляет текущий токен из БД, очищает cookie и уничтожает сессию.
     *
     * @return void
     */
    public function logout(): void
    {
        // Удаляем токен из БД (если есть в cookie)
        $plainToken = $_COOKIE['auth_token'] ?? null;

        if ($plainToken !== null) {
            $tokenHash = hash('sha256', $plainToken);
            $token = $this->tokenModel->findByTokenHash($tokenHash);

            if ($token !== null) {
                $this->tokenModel->delete($token['id']);
            }
        }

        // Удаляем cookie
        setcookie('auth_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        // Очищаем авторизацию из сессии
        Auth::logout();

        // Уничтожаем сессию полностью
        Session::destroy();
    }

    /**
     * Проверить токен из cookie и восстановить авторизацию
     *
     * Используется в AuthMiddleware для автоматического входа
     * когда сессия истекла, но cookie ещё валиден.
     *
     * @param string $plainToken Plain-text токен из cookie
     * @return array|null Данные пользователя или null если токен невалиден
     */
    public function verifyToken(string $plainToken): ?array
    {
        // Хэшируем токен для поиска в БД
        $tokenHash = hash('sha256', $plainToken);

        // Ищем токен в БД
        $token = $this->tokenModel->findByTokenHash($tokenHash);

        if ($token === null) {
            return null;
        }

        // Проверяем срок действия
        if ($this->tokenModel->isExpired($token)) {
            // Токен просрочен — удаляем из БД
            $this->tokenModel->delete($token['id']);
            return null;
        }

        // Ищем пользователя
        $user = $this->userModel->find((int) $token['user_id']);

        if ($user === null || !$this->userModel->isActive($user)) {
            return null;
        }

        // Обновляем время последнего использования токена
        $this->tokenModel->updateLastUsed((int) $token['id']);

        // Восстанавливаем авторизацию в сессии
        Auth::setUser($user);

        return $user;
    }

    /**
     * Сгенерировать токен и установить cookie
     *
     * @param int $userId ID пользователя
     * @return string Plain-text токен
     */
    public function generateToken(int $userId): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Создаём токен через модель
        $plainToken = $this->tokenModel->createToken($userId, $userAgent, $ip);

        // Устанавливаем cookie (HttpOnly, Secure, SameSite)
        setcookie('auth_token', $plainToken, [
            'expires'  => time() + 7776000, // 3 месяца (90 дней)
            'path'     => '/',
            'secure'   => true,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        return $plainToken;
    }
}
