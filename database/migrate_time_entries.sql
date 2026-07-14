-- Дневные записи времени для календаря пользователя.
CREATE TABLE IF NOT EXISTS `time_entries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `time_type` ENUM('executor', 'manager') NOT NULL DEFAULT 'executor',
    `hours` DECIMAL(6,2) NOT NULL,
    `entry_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_time_entries_user_date` (`user_id`, `entry_date`),
    INDEX `idx_time_entries_task` (`task_id`),
    CONSTRAINT `fk_time_entries_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_time_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
