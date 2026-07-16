-- Добавляем поле sort_order для задач (порядок отображения в проекте)
ALTER TABLE `tasks` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `closed_at`;
-- Индекс для сортировки
ALTER TABLE `tasks` ADD INDEX `idx_tasks_sort_order` (`project_id`, `sort_order`);
-- Инициализируем порядок по дате создания
UPDATE `tasks` t
JOIN (
    SELECT id, ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY created_at ASC) as rn
    FROM tasks
) ranked ON t.id = ranked.id
SET t.sort_order = ranked.rn;
