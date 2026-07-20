<?php
/**
 * Выбор ссылки на изображение из чата родительской задачи.
 *
 * @param array $images Массив файлов с id, file_name, user_name и created_at.
 * @param string $idPrefix Уникальный префикс id внутри страницы.
 * @param int|null $selectedId Ранее выбранный файл после ошибки валидации.
 */
function renderSourceImagePicker(array $images, string $idPrefix, ?int $selectedId = null): void
{
    if (empty($images)) {
        return;
    }
    ?>
    <details class="rounded-lg border border-gray-200 bg-gray-50">
        <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-blue-600 focus-visible:outline-none">
            Сослаться на изображение из чата
        </summary>
        <fieldset class="border-t border-gray-200 p-3">
            <legend class="sr-only">Изображение из чата родительской задачи</legend>
            <p class="mb-2 text-xs text-gray-500">Выберите изображение — ссылка появится первым сообщением в новой доработке.</p>
            <div class="grid max-h-72 grid-cols-2 gap-2 overflow-y-auto p-0.5 sm:grid-cols-3">
                <label class="cursor-pointer">
                    <input type="radio" name="source_image_id" value="" class="peer sr-only" <?= $selectedId ? '' : 'checked' ?>>
                    <span class="flex h-full min-h-20 items-center justify-center rounded-md border border-gray-200 bg-white p-2 text-center text-xs text-gray-600 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-200">
                        Без ссылки
                    </span>
                </label>
                <?php foreach ($images as $image): ?>
                    <?php $optionId = $idPrefix . '-source-image-' . (int) $image['id']; ?>
                    <label for="<?= e($optionId) ?>" class="cursor-pointer">
                        <input id="<?= e($optionId) ?>" type="radio" name="source_image_id" value="<?= (int) $image['id'] ?>" class="peer sr-only" <?= $selectedId === (int) $image['id'] ? 'checked' : '' ?>>
                        <span class="block h-full overflow-hidden rounded-md border border-gray-200 bg-white peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-200">
                            <img src="<?= url('/files/' . (int) $image['id'] . '/download') ?>"
                                 alt="<?= e($image['file_name']) ?>" loading="lazy"
                                 class="h-20 w-full object-cover">
                            <span class="block truncate px-2 pt-1.5 text-xs font-medium text-gray-700" title="<?= e($image['file_name']) ?>"><?= e($image['file_name']) ?></span>
                            <span class="block truncate px-2 pb-1.5 text-[11px] text-gray-500">
                                <?= e($image['user_name'] ?? '') ?><?= !empty($image['created_at']) ? ' · ' . date('d.m H:i', strtotime($image['created_at'])) : '' ?>
                            </span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </details>
    <?php
}
