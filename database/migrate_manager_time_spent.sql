-- ============================================================
-- Миграция: Добавление поля manager_time_spent в таблицу tasks
-- Описание: Время руководителя (отдельно от исполнителя)
-- ============================================================

ALTER TABLE `tasks`
ADD COLUMN `manager_time_spent` DECIMAL(6,1) NULL DEFAULT NULL
AFTER `time_spent`;
