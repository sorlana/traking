<?php

namespace Services;

use Helpers\Database;

class CommentPinService
{
    public function ensureTable(): void
    {
        Database::getInstance()->query(
            "CREATE TABLE IF NOT EXISTS task_comment_personal_pins (
                comment_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (comment_id, user_id),
                INDEX idx_personal_pins_user (user_id),
                CONSTRAINT fk_personal_pins_comment FOREIGN KEY (comment_id) REFERENCES task_comments(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_personal_pins_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function annotateMessages(array &$messages, int $userId): void
    {
        foreach ($messages as &$message) {
            $message['is_personal_pinned'] = 0;
        }
        unset($message);

        if ($messages === [] || $userId <= 0) {
            return;
        }

        $ids = array_values(array_filter(array_map(
            static fn(array $message): int => (int) ($message['id'] ?? 0),
            $messages
        )));
        if ($ids === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT comment_id FROM task_comment_personal_pins WHERE user_id = ? AND comment_id IN ({$placeholders})";
        $params = array_merge([$userId], $ids);
        try {
            $rows = Database::getInstance()->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->ensureTable();
            $rows = Database::getInstance()->fetchAll($sql, $params);
        }
        $personalIds = array_fill_keys(array_map(static fn(array $row): int => (int) $row['comment_id'], $rows), true);

        foreach ($messages as &$message) {
            $message['is_personal_pinned'] = isset($personalIds[(int) ($message['id'] ?? 0)]) ? 1 : 0;
        }
        unset($message);
    }

    public function togglePersonal(int $commentId, int $userId): int
    {
        $this->ensureTable();
        $db = Database::getInstance();
        $existing = $db->fetch(
            'SELECT comment_id FROM task_comment_personal_pins WHERE comment_id = ? AND user_id = ?',
            [$commentId, $userId]
        );

        if ($existing) {
            $db->delete('task_comment_personal_pins', 'comment_id = ? AND user_id = ?', [$commentId, $userId]);
            return 0;
        }

        $db->query(
            'INSERT INTO task_comment_personal_pins (comment_id, user_id) VALUES (?, ?)',
            [$commentId, $userId]
        );
        return 1;
    }
}
