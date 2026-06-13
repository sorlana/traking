-- Миграция: добавление колонки sound_enabled в user_settings
-- Звуковое уведомление при новых сообщениях и смене статуса

ALTER TABLE `user_settings` ADD COLUMN `sound_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `dnd_enabled`;
