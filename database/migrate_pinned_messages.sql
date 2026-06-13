-- Миграция: добавление поля is_pinned для закрепления сообщений в чате задач
ALTER TABLE `task_comments` ADD COLUMN `is_pinned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `parent_comment_id`;
