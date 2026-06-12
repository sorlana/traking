<?php
/**
 * ProjectDocument — Модель документов проекта
 *
 * Таблица: project_documents
 * Хранит файлы и внешние ссылки, привязанные к проекту.
 */

namespace Models;

use Helpers\Database;

class ProjectDocument extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'project_documents';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = ['project_id', 'title', 'document_type', 'file_path', 'external_url', 'comment', 'uploaded_by'];

    /**
     * Получить все документы проекта
     *
     * @param int $projectId ID проекта
     * @return array Массив документов с данными загрузившего
     */
    public function getByProject(int $projectId): array
    {
        $sql = "SELECT pd.*, u.name as uploader_name
                FROM project_documents pd
                JOIN users u ON pd.uploaded_by = u.id
                WHERE pd.project_id = ?
                ORDER BY pd.created_at DESC";

        return $this->db()->fetchAll($sql, [$projectId]);
    }
}
