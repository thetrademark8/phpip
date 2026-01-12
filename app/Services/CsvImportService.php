<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    private const DELIMITER = ';';

    /**
     * Import data from a CSV file into a database table.
     *
     * @param string $filePath Path to the CSV file
     * @param string $table Target database table
     * @param string|array $uniqueKey Column(s) to check for duplicates
     * @param array $excludeColumns Columns to exclude from import
     * @param array $translatableColumns Columns that should be converted to JSON format
     * @param callable|null $rowTransformer Optional callback to transform each row
     * @return array{inserted: int, skipped: int, errors: int}
     */
    public function importFromCsv(
        string $filePath,
        string $table,
        string|array $uniqueKey,
        array $excludeColumns = [],
        array $translatableColumns = [],
        ?callable $rowTransformer = null
    ): array {
        $stats = ['inserted' => 0, 'skipped' => 0, 'errors' => 0];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            Log::error("CsvImportService: Unable to open file {$filePath}");
            return $stats;
        }

        // Parse headers from first line
        $headers = fgetcsv($handle, 0, self::DELIMITER);
        if ($headers === false) {
            fclose($handle);
            Log::error("CsvImportService: Unable to read headers from {$filePath}");
            return $stats;
        }

        // Clean headers (remove BOM if present)
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        // Get existing keys for duplicate detection
        $existingKeys = $this->getExistingKeys($table, $uniqueKey);

        // Process each row
        $rowNumber = 1;
        while (($row = fgetcsv($handle, 0, self::DELIMITER)) !== false) {
            $rowNumber++;

            try {
                // Skip empty rows
                if (count($row) === 1 && empty($row[0])) {
                    continue;
                }

                // Combine headers with values
                if (count($headers) !== count($row)) {
                    Log::warning("CsvImportService: Row {$rowNumber} has mismatched column count");
                    $stats['errors']++;
                    continue;
                }

                $data = array_combine($headers, $row);

                // Transform values (NULL strings, dates, translatable, etc.)
                $data = $this->transformRow($data, $translatableColumns);

                // Apply custom transformer if provided
                if ($rowTransformer !== null) {
                    $data = $rowTransformer($data);
                }

                // Remove excluded columns
                foreach ($excludeColumns as $col) {
                    unset($data[$col]);
                }

                // Check for duplicates
                $keyValue = $this->extractKeyValue($data, $uniqueKey);
                if ($existingKeys->contains($keyValue)) {
                    $stats['skipped']++;
                    continue;
                }

                // Insert the row
                DB::table($table)->insert($data);
                $existingKeys->push($keyValue);
                $stats['inserted']++;

            } catch (\Exception $e) {
                Log::error("CsvImportService: Error on row {$rowNumber}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        fclose($handle);

        return $stats;
    }

    /**
     * Get existing key values from the table for duplicate detection.
     */
    public function getExistingKeys(string $table, string|array $uniqueKey): \Illuminate\Support\Collection
    {
        $query = DB::table($table);

        if (is_array($uniqueKey)) {
            // For composite keys, concatenate values
            return $query->get($uniqueKey)->map(function ($row) use ($uniqueKey) {
                $values = [];
                foreach ($uniqueKey as $col) {
                    $values[] = $row->$col ?? '';
                }
                return implode('|', $values);
            });
        }

        return $query->pluck($uniqueKey);
    }

    /**
     * Transform a CSV row, converting NULL strings, dates, booleans, and translatable fields.
     */
    private function transformRow(array $row, array $translatableColumns = []): array
    {
        $transformed = [];

        foreach ($row as $key => $value) {
            $parsedValue = $this->parseValue($value, $key);

            // Convert translatable columns to JSON format
            if (in_array($key, $translatableColumns) && $parsedValue !== null) {
                // Check if value is already valid JSON
                if (!$this->isValidJson($parsedValue)) {
                    // Convert plain text to JSON with 'en' as default language
                    $parsedValue = json_encode(['en' => $parsedValue], JSON_UNESCAPED_UNICODE);
                }
            }

            $transformed[$key] = $parsedValue;
        }

        return $transformed;
    }

    /**
     * Check if a string is valid JSON.
     */
    private function isValidJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Parse a single value, handling NULL strings and date formats.
     */
    private function parseValue(string $value, string $column): mixed
    {
        $value = trim($value);

        // Handle NULL strings
        if ($value === 'NULL' || $value === '') {
            return null;
        }

        // Handle date columns (format: dd/MM/yyyy HH:mm)
        if ($this->isDateColumn($column) && preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value)) {
            return $this->parseDate($value);
        }

        return $value;
    }

    /**
     * Check if a column is a date column based on naming convention.
     */
    private function isDateColumn(string $column): bool
    {
        $dateColumns = ['created_at', 'updated_at', 'deleted_at'];
        return in_array($column, $dateColumns) || str_ends_with($column, '_at') || str_ends_with($column, '_date');
    }

    /**
     * Parse a date string from dd/MM/yyyy HH:mm format to Y-m-d H:i:s.
     */
    private function parseDate(string $value): ?string
    {
        // Format: dd/MM/yyyy HH:mm
        $date = \DateTime::createFromFormat('d/m/Y H:i', $value);
        if ($date === false) {
            // Try without time
            $date = \DateTime::createFromFormat('d/m/Y', $value);
        }

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    /**
     * Extract the key value for duplicate checking.
     */
    private function extractKeyValue(array $data, string|array $uniqueKey): string
    {
        if (is_array($uniqueKey)) {
            $values = [];
            foreach ($uniqueKey as $col) {
                $values[] = $data[$col] ?? '';
            }
            return implode('|', $values);
        }

        return (string) ($data[$uniqueKey] ?? '');
    }

    /**
     * Preview the contents of a CSV file without importing.
     *
     * @return array{headers: array, rows: array, total: int}
     */
    public function preview(string $filePath, int $limit = 10): array
    {
        $result = ['headers' => [], 'rows' => [], 'total' => 0];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return $result;
        }

        // Parse headers
        $headers = fgetcsv($handle, 0, self::DELIMITER);
        if ($headers === false) {
            fclose($handle);
            return $result;
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $result['headers'] = array_map('trim', $headers);

        // Read rows
        $count = 0;
        while (($row = fgetcsv($handle, 0, self::DELIMITER)) !== false) {
            $count++;
            if (count($result['rows']) < $limit) {
                $result['rows'][] = array_combine($result['headers'], $row);
            }
        }
        $result['total'] = $count;

        fclose($handle);

        return $result;
    }
}
