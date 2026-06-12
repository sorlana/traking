<?php
/**
 * TaskFile — Модель файлов, прикреплённых к задачам
 *
 * Таблица: task_files
 * Хранит информацию о загруженных файлах: имя, путь, тип, размер.
 */

namespace Models;

use Helpers\Database;

class TaskFile extends Model
{
    /** @var string Имя таблицы */
    protected string $table = 'task_files';

    /** @var array Поля, разрешённые для массового заполнения */
    protected array $fillable = [
        'task_id',
        'comment_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * Получить файлы задачи с JOIN на users (кто загрузил)
     *
     * @param int $taskId ID задачи
     * @return array Массив файлов с именами загрузивших
     */
    public function getByTask(int $taskId): array
    {
        $sql = "SELECT tf.*, u.name as uploader_name
                FROM task_files tf
                JOIN users u ON tf.uploaded_by = u.id
                WHERE tf.task_id = ?
                ORDER BY tf.created_at DESC";

        return $this->db()->fetchAll($sql, [$taskId]);
    }
}
