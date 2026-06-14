-- ============================================================
-- Миграция: Добавление поля estimated_hours в таблицу projects
-- Описание: Расчётное (планируемое) количество часов на проект
-- ============================================================

ALTER TABLE `projects`
ADD COLUMN `estimated_hours` DECIMAL(7,1) NULL DEFAULT NULL
AFTER `deadline`;
