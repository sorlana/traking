<?php

namespace Services;

use Helpers\Database;

class ProjectLinkService
{
    public function ensureTables(): void
    {
        $db = Database::getInstance();
        $db->query(
            "CREATE TABLE IF NOT EXISTS project_link_groups (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                project_id INT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                caption VARCHAR(500) NULL,
                icon_path VARCHAR(500) NULL,
                created_by INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_project_link_groups_project (project_id),
                CONSTRAINT fk_project_link_groups_project FOREIGN KEY (project_id) REFERENCES projects(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_project_link_groups_user FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->query(
            "CREATE TABLE IF NOT EXISTS project_link_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                group_id INT UNSIGNED NOT NULL,
                item_type ENUM('link', 'login', 'password') NOT NULL,
                label VARCHAR(255) NOT NULL,
                value_text TEXT NULL,
                value_encrypted TEXT NULL,
                created_by INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_project_link_items_group (group_id),
                CONSTRAINT fk_project_link_items_group FOREIGN KEY (group_id) REFERENCES project_link_groups(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_project_link_items_user FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getGroups(int $projectId): array
    {
        $this->ensureTables();
        $db = Database::getInstance();
        $groups = $db->fetchAll(
            'SELECT * FROM project_link_groups WHERE project_id = ? ORDER BY created_at, id',
            [$projectId]
        );
        foreach ($groups as &$group) {
            $group['items'] = $db->fetchAll(
                "SELECT id, group_id, item_type, label, value_text, created_at
                 FROM project_link_items WHERE group_id = ? ORDER BY created_at, id",
                [(int) $group['id']]
            );
        }
        unset($group);
        return $groups;
    }

    public function createGroup(
        int $projectId,
        string $title,
        ?string $caption,
        ?string $iconPath,
        int $userId
    ): int {
        $this->ensureTables();
        return (int) Database::getInstance()->insert('project_link_groups', [
            'project_id' => $projectId,
            'title' => $title,
            'caption' => $caption,
            'icon_path' => $iconPath,
            'created_by' => $userId,
        ]);
    }

    public function findGroup(int $groupId): ?array
    {
        $this->ensureTables();
        return Database::getInstance()->fetch(
            'SELECT * FROM project_link_groups WHERE id = ?',
            [$groupId]
        );
    }

    public function createItem(
        int $groupId,
        string $type,
        string $label,
        string $value,
        int $userId
    ): int {
        $this->ensureTables();
        $type = in_array($type, ['link', 'login', 'password'], true) ? $type : 'link';
        $isPassword = $type === 'password';
        return (int) Database::getInstance()->insert('project_link_items', [
            'group_id' => $groupId,
            'item_type' => $type,
            'label' => $label,
            'value_text' => $isPassword ? null : $value,
            'value_encrypted' => $isPassword ? $this->encrypt($value) : null,
            'created_by' => $userId,
        ]);
    }

    public function getItemWithProject(int $itemId): ?array
    {
        $this->ensureTables();
        return Database::getInstance()->fetch(
            "SELECT pli.*, plg.project_id
             FROM project_link_items pli
             JOIN project_link_groups plg ON plg.id = pli.group_id
             WHERE pli.id = ?",
            [$itemId]
        );
    }

    public function revealValue(array $item): string
    {
        if (($item['item_type'] ?? '') !== 'password') {
            return (string) ($item['value_text'] ?? '');
        }
        return $this->decrypt((string) ($item['value_encrypted'] ?? ''));
    }

    private function encrypt(string $value): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $value,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($ciphertext === false) {
            throw new \RuntimeException('Не удалось зашифровать пароль');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    private function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < 29) {
            throw new \RuntimeException('Пароль повреждён');
        }
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);
        $value = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($value === false) {
            throw new \RuntimeException('Не удалось расшифровать пароль');
        }
        return $value;
    }

    private function encryptionKey(): string
    {
        $environmentKey = trim((string) getenv('TRACKING_CREDENTIALS_KEY'));
        if ($environmentKey !== '') {
            return hash('sha256', $environmentKey, true);
        }

        // uploads исключён из FTP-деплоя и сохраняется между публикациями.
        $keyPath = BASE_PATH . '/storage/uploads/.project_credentials_key';
        $keyDirectory = dirname($keyPath);
        if (!is_dir($keyDirectory) && !mkdir($keyDirectory, 0755, true) && !is_dir($keyDirectory)) {
            throw new \RuntimeException('Не удалось подготовить хранилище ключа шифрования');
        }
        if (!is_file($keyPath)) {
            $handle = @fopen($keyPath, 'x');
            if (is_resource($handle)) {
                fwrite($handle, base64_encode(random_bytes(32)));
                fclose($handle);
                @chmod($keyPath, 0600);
            }
        }

        $stored = is_file($keyPath) ? trim((string) file_get_contents($keyPath)) : '';
        $decoded = base64_decode($stored, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new \RuntimeException('Не удалось подготовить ключ шифрования');
        }
        return $decoded;
    }
}
