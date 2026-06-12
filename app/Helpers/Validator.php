<?php
/**
 * Validator — Валидация входных данных
 *
 * Проверяет данные по набору правил и возвращает массив ошибок.
 * Правила задаются строкой через символ «|».
 *
 * Поддерживаемые правила:
 *   required      — поле обязательно
 *   email         — корректный email
 *   numeric       — числовое значение
 *   min:N         — минимальная длина строки (N символов)
 *   max:N         — максимальная длина строки (N символов)
 *   in:val1,val2  — значение из списка
 *   file          — файл загружен (проверяет $_FILES)
 *   file_max:N    — максимальный размер файла (в килобайтах)
 *   unique:table,column — уникальность значения в таблице БД
 *
 * Пример использования:
 *   $validator = new Validator();
 *   $errors = $validator->validate($data, [
 *       'email'    => 'required|email|max:255',
 *       'password' => 'required|min:6',
 *       'role'     => 'required|in:admin,manager,executor',
 *   ]);
 *   if (!empty($errors)) { ... }
 */

namespace Helpers;

class Validator
{
    /** @var array Массив ошибок валидации */
    private array $errors = [];

    /**
     * Валидировать данные по правилам
     *
     * @param array $data Данные для проверки [поле => значение]
     * @param array $rules Правила [поле => 'правило1|правило2']
     * @return array Массив ошибок [поле => [сообщение1, сообщение2, ...]]
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return $this->errors;
    }

    /**
     * Применить одно правило к полю
     *
     * @param string $field Имя поля
     * @param mixed $value Значение поля
     * @param string $rule Правило (например: 'min:6')
     * @return void
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Разбираем правило с параметром (min:6 → rule=min, param=6)
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }

        match ($rule) {
            'required'  => $this->validateRequired($field, $value),
            'email'     => $this->validateEmail($field, $value),
            'numeric'   => $this->validateNumeric($field, $value),
            'min'       => $this->validateMin($field, $value, (int) $param),
            'max'       => $this->validateMax($field, $value, (int) $param),
            'in'        => $this->validateIn($field, $value, $param),
            'file'      => $this->validateFile($field),
            'file_max'  => $this->validateFileMax($field, (int) $param),
            'unique'    => $this->validateUnique($field, $value, $param),
            default     => null, // Неизвестное правило — пропускаем
        };
    }

    /**
     * Правило: поле обязательно для заполнения
     */
    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, "Поле «{$field}» обязательно для заполнения");
        }
    }

    /**
     * Правило: корректный email
     */
    private function validateEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Поле «{$field}» должно быть корректным email-адресом");
        }
    }

    /**
     * Правило: числовое значение
     */
    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "Поле «{$field}» должно быть числом");
        }
    }

    /**
     * Правило: минимальная длина строки
     */
    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value !== null && $value !== '' && mb_strlen((string) $value) < $min) {
            $this->addError($field, "Поле «{$field}» должно содержать не менее {$min} символов");
        }
    }

    /**
     * Правило: максимальная длина строки
     */
    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value !== null && $value !== '' && mb_strlen((string) $value) > $max) {
            $this->addError($field, "Поле «{$field}» должно содержать не более {$max} символов");
        }
    }

    /**
     * Правило: значение из списка допустимых
     */
    private function validateIn(string $field, mixed $value, ?string $param): void
    {
        if ($value !== null && $value !== '' && $param !== null) {
            $allowed = explode(',', $param);
            if (!in_array((string) $value, $allowed, true)) {
                $this->addError($field, "Поле «{$field}» должно быть одним из: {$param}");
            }
        }
    }

    /**
     * Правило: файл загружен
     */
    private function validateFile(string $field): void
    {
        $file = $_FILES[$field] ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            $this->addError($field, "Файл «{$field}» не загружен");
        }
    }

    /**
     * Правило: максимальный размер файла (в килобайтах)
     */
    private function validateFileMax(string $field, int $maxKb): void
    {
        $file = $_FILES[$field] ?? null;

        if ($file !== null && $file['error'] === UPLOAD_ERR_OK) {
            $fileSizeKb = $file['size'] / 1024;
            if ($fileSizeKb > $maxKb) {
                $this->addError($field, "Файл «{$field}» превышает максимальный размер {$maxKb} КБ");
            }
        }
    }

    /**
     * Правило: уникальность значения в таблице
     * Формат параметра: table,column
     */
    private function validateUnique(string $field, mixed $value, ?string $param): void
    {
        if ($value === null || $value === '' || $param === null) {
            return;
        }

        $parts = explode(',', $param);
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? $field;

        if ($table === '') {
            return;
        }

        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT COUNT(*) as cnt FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        if ($result && (int) $result['cnt'] > 0) {
            $this->addError($field, "Значение поля «{$field}» уже существует");
        }
    }

    /**
     * Добавить сообщение об ошибке
     *
     * @param string $field Имя поля
     * @param string $message Сообщение об ошибке
     * @return void
     */
    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
