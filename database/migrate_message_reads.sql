-- Миграция: отметки о прочтении сообщений в чате
-- Каждая запись = пользователь прочитал сообщение
CREATE TABLE IF NOT EXISTS `message_reads` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `comment_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_message_read` (`comment_id`, `user_id`),
    INDEX `idx_message_reads_user` (`user_id`),
    CONSTRAINT `fk_message_reads_comment` FOREIGN KEY (`comment_id`) REFERENCES `task_comments` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_message_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
