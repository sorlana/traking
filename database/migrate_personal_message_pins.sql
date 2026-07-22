-- Личные закрепы сообщений: каждый пользователь видит только собственный набор.
-- task_comments.is_pinned продолжает хранить общие закрепы для всех участников.
CREATE TABLE IF NOT EXISTS `task_comment_personal_pins` (
    `comment_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`comment_id`, `user_id`),
    INDEX `idx_personal_pins_user` (`user_id`),
    CONSTRAINT `fk_personal_pins_comment` FOREIGN KEY (`comment_id`) REFERENCES `task_comments` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_personal_pins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
