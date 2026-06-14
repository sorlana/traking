<?php
/**
 * HelpController — Контроллер страницы помощи
 *
 * Отображает руководство пользователя с описанием функций
 * для Руководителя и Исполнителя.
 */

namespace Controllers;

class HelpController extends Controller
{
    /**
     * Страница помощи / руководство пользователя
     * GET /help
     */
    public function index(): void
    {
        $this->view('help/index', [
            'title' => 'Помощь — Traking',
        ]);
    }
}
