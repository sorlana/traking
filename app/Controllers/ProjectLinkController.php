<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\Response;
use Helpers\Session;
use Middleware\ProjectAccessMiddleware;
use Services\ProjectLinkService;

class ProjectLinkController extends Controller
{
    private ProjectLinkService $service;

    public function __construct()
    {
        $this->service = new ProjectLinkService();
    }

    public function createGroup(string $id): void
    {
        $projectId = (int) $id;
        $this->authorize(can('edit_project', $projectId), 'Недостаточно прав');

        $title = trim((string) ($_POST['group_title'] ?? ''));
        $caption = trim((string) ($_POST['group_caption'] ?? ''));
        if ($title === '' || mb_strlen($title) > 255) {
            $this->fail($projectId, 'Укажите название группы длиной до 255 символов');
            return;
        }
        if (mb_strlen($caption) > 500) {
            $this->fail($projectId, 'Подпись должна быть не длиннее 500 символов');
            return;
        }

        $iconPath = null;
        try {
            $iconPath = $this->storeIcon($projectId, $_FILES['group_icon'] ?? null);
            $this->service->createGroup(
                $projectId,
                $title,
                $caption !== '' ? $caption : null,
                $iconPath,
                (int) Auth::id()
            );
        } catch (\Throwable $e) {
            if ($iconPath !== null) {
                $fullPath = BASE_PATH . '/storage/uploads/' . $iconPath;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
            $this->fail($projectId, $e->getMessage() ?: 'Не удалось создать группу');
            return;
        }

        Session::flash('success', 'Группа создана');
        $this->redirect("/projects/{$projectId}?tab=links");
    }

    public function createItem(string $id): void
    {
        $groupId = (int) $id;
        $group = $this->service->findGroup($groupId);
        if (!$group) {
            Response::notFound('Группа не найдена');
            return;
        }
        $projectId = (int) $group['project_id'];
        $this->authorize(can('edit_project', $projectId), 'Недостаточно прав');

        $type = (string) ($_POST['item_type'] ?? 'link');
        $label = trim((string) ($_POST['item_label'] ?? ''));
        $value = trim((string) ($_POST['item_value'] ?? ''));
        if (!in_array($type, ['link', 'login', 'password'], true)) {
            $this->fail($projectId, 'Выберите корректный тип записи');
            return;
        }
        if ($label === '' || mb_strlen($label) > 255) {
            $this->fail($projectId, 'Укажите название записи длиной до 255 символов');
            return;
        }
        if ($value === '' || mb_strlen($value) > 2048) {
            $this->fail($projectId, 'Укажите значение длиной до 2048 символов');
            return;
        }
        if ($type === 'link') {
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
            if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                $this->fail($projectId, 'Ссылка должна начинаться с http:// или https://');
                return;
            }
        }

        try {
            $this->service->createItem($groupId, $type, $label, $value, (int) Auth::id());
        } catch (\Throwable $e) {
            $this->fail($projectId, 'Не удалось добавить запись');
            return;
        }

        Session::flash('success', 'Запись добавлена');
        $this->redirect("/projects/{$projectId}?tab=links");
    }

    public function value(string $id): void
    {
        $item = $this->service->getItemWithProject((int) $id);
        if (!$item) {
            $this->json(['error' => 'Запись не найдена'], 404);
            return;
        }
        if (!ProjectAccessMiddleware::check((int) $item['project_id'])) {
            $this->json(['error' => 'Нет доступа'], 403);
            return;
        }

        try {
            header('Cache-Control: no-store, private');
            $this->json(['success' => true, 'value' => $this->service->revealValue($item)]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Не удалось получить значение'], 500);
        }
    }

    public function icon(string $id): void
    {
        $group = $this->service->findGroup((int) $id);
        if (!$group || empty($group['icon_path'])) {
            Response::notFound('Иконка не найдена');
            return;
        }
        if (!ProjectAccessMiddleware::check((int) $group['project_id'])) {
            Response::forbidden('Нет доступа');
            return;
        }

        $fullPath = BASE_PATH . '/storage/uploads/' . $group['icon_path'];
        if (!is_file($fullPath)) {
            Response::notFound('Иконка не найдена');
            return;
        }
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if (!isset($mimeTypes[$extension])) {
            Response::notFound('Иконка не найдена');
            return;
        }
        header('Content-Type: ' . $mimeTypes[$extension]);
        header('Content-Length: ' . filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        readfile($fullPath);
        exit;
    }

    private function storeIcon(int $projectId, ?array $file): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Не удалось загрузить иконку');
        }
        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new \RuntimeException('Иконка должна быть не больше 2 МБ');
        }
        if (!is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new \RuntimeException('Некорректный файл иконки');
        }

        $imageInfo = @getimagesize((string) $file['tmp_name']);
        $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            throw new \RuntimeException('Допустимы иконки JPG, PNG и WebP');
        }

        $directory = BASE_PATH . "/storage/uploads/projects/{$projectId}/links";
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Не удалось создать папку для иконки');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
            throw new \RuntimeException('Не удалось сохранить иконку');
        }
        return "projects/{$projectId}/links/{$filename}";
    }

    private function fail(int $projectId, string $message): void
    {
        Session::flash('error', $message);
        $this->redirect("/projects/{$projectId}?tab=links");
    }
}
