-- Миграция: таблица пользовательских настроек уведомлений
-- schedule_days — строка вида "1,2,3,4,5" (пн-пт по ISO: 1=пн, 7=вс)

CREATE TABLE IF NOT EXISTS `user_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `push_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `dnd_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `schedule_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `schedule_start` TIME NOT NULL DEFAULT '09:00:00',
    `schedule_end` TIME NOT NULL DEFAULT '18:00:00',
    `schedule_days` VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_settings_user` (`user_id`),
    CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
