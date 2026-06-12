<?php
/**
 * Controller — Базовый контроллер приложения
 *
 * Все контроллеры наследуют этот класс.
 * Предоставляет общие методы: рендеринг шаблонов, редиректы,
 * JSON-ответы, проверку прав и валидацию данных.
 *
 * Пример использования:
 *   class TaskController extends Controller {
 *       public function show($id) {
 *           $task = (new Task())->find($id);
 *           $this->authorize($task !== null, 'Задача не найдена');
 *           return $this->view('tasks/show', ['task' => $task]);
 *       }
 *   }
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;

class Controller
{
    /**
     * Рендерит шаблон и отправляет HTML-ответ
     *
     * @param string $template Имя шаблона (например: 'tasks/show')
     * @param array $data Данные для передачи в шаблон
     * @return void
     */
    protected function view(string $template, array $data = []): void
    {
        Response::view($template, $data);
    }

    /**
     * Редирект на указанный URL
     *
     * @param string $url URL для перенаправления
     * @return void
     */
    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    /**
     * Отправить JSON-ответ
     *
     * @param mixed $data Данные
     * @param int $code HTTP-код ответа
     * @return void
     */
    protected function json(mixed $data, int $code = 200): void
    {
        Response::json($data, $code);
    }

    /**
     * Проверка прав доступа
     * Если условие false — отправляет 403 Forbidden и прекращает выполнение
     *
     * @param bool $condition Условие доступа (true = разрешено)
     * @param string $message Сообщение при отказе
     * @return void
     */
    protected function authorize(bool $condition, string $message = 'Доступ запрещён'): void
    {
        if (!$condition) {
            Response::forbidden($message);
        }
    }

    /**
     * Валидация входных данных
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации [поле => 'правило1|правило2']
     * @return array Массив ошибок (пустой если всё ОК)
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = new Validator();
        return $validator->validate($data, $rules);
    }
}
