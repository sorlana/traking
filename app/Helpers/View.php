<?php
/**
 * View — Шаблонизатор для рендеринга PHP-шаблонов
 *
 * Возможности:
 * - Рендеринг шаблона по имени (например: 'auth/login' → views/auth/login.php)
 * - Передача данных в шаблон (массив переменных)
 * - Поддержка layout-ов (шаблон указывает переменную $layout)
 * - Экранирование вывода через хелпер e()
 *
 * Пример использования:
 *   // В контроллере:
 *   View::make('tasks/show', ['task' => $task, 'comments' => $comments]);
 *
 *   // В шаблоне views/tasks/show.php:
 *   <?php $layout = 'layouts/app'; ?>
 *   <h1><?= e($task['title']) ?></h1>
 *
 *   // В layout views/layouts/app.php:
 *   <html><body><?= $content ?></body></html>
 */

namespace Helpers;

class View
{
    /** @var string Базовый путь к директории шаблонов */
    private string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? BASE_PATH . '/views';
    }

    /**
     * Рендерит шаблон и возвращает HTML-строку
     *
     * @param string $template Имя шаблона (например: 'tasks/show')
     * @param array $data Данные для передачи в шаблон
     * @return string Готовый HTML
     * @throws \RuntimeException Если файл шаблона не найден
     */
    public function render(string $template, array $data = []): string
    {
        $filePath = $this->resolvePath($template);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Шаблон не найден: {$template} ({$filePath})");
        }

        // Рендерим содержимое шаблона и получаем имя layout (если задан)
        [$content, $layoutName] = $this->renderFile($filePath, $data);

        // Если шаблон указал layout — оборачиваем содержимое
        if ($layoutName !== null) {
            $layoutPath = $this->resolvePath($layoutName);

            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout не найден: {$layoutName} ({$layoutPath})");
            }

            // Передаём содержимое шаблона в layout как переменную $content
            $layoutData = array_merge($data, ['content' => $content]);
            [$content, ] = $this->renderFile($layoutPath, $layoutData);
        }

        return $content;
    }

    /**
     * Статический метод — удобный вызов рендеринга
     *
     * @param string $template Имя шаблона
     * @param array $data Данные для шаблона
     * @return string Готовый HTML
     */
    public static function make(string $template, array $data = []): string
    {
        $view = new self();
        return $view->render($template, $data);
    }

    /**
     * Рендерит PHP-файл с данными, возвращает HTML и имя layout (если задан)
     *
     * Внутри шаблона можно задать:
     *   <?php $layout = 'layouts/app'; ?>
     * Это укажет View обернуть результат в указанный layout.
     *
     * @param string $filePath Полный путь к файлу шаблона
     * @param array $data Переменные, доступные в шаблоне
     * @return array [string $html, ?string $layoutName]
     */
    private function renderFile(string $filePath, array $data): array
    {
        // Извлекаем переменные из массива — они станут доступны в шаблоне
        extract($data, EXTR_SKIP);

        // Переменная $layout может быть задана внутри шаблона
        $layout = null;

        ob_start();
        include $filePath;
        $output = ob_get_clean();

        return [$output, $layout];
    }

    /**
     * Преобразует имя шаблона в полный путь к файлу
     *
     * @param string $template Имя шаблона (например: 'auth/login')
     * @return string Полный путь (например: /path/views/auth/login.php)
     */
    private function resolvePath(string $template): string
    {
        // Защита от directory traversal
        $template = str_replace(['..', "\0"], '', $template);

        return $this->viewsPath . '/' . $template . '.php';
    }
}

