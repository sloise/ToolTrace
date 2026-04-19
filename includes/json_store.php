<?php
/**
 * Simple JSON file persistence with locking.
 * Replace calls in domain helpers with DB queries when you add a database.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function tooltrace_json_read(string $filename, mixed $default = []): mixed
{
    $path = TOOLTRACE_DATA_DIR . DIRECTORY_SEPARATOR . $filename;
    if (!is_readable($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function tooltrace_json_write(string $filename, mixed $data): bool
{
    if (!is_dir(TOOLTRACE_DATA_DIR)) {
        if (!mkdir(TOOLTRACE_DATA_DIR, 0777, true) && !is_dir(TOOLTRACE_DATA_DIR)) {
            return false;
        }
    }
    $path = TOOLTRACE_DATA_DIR . DIRECTORY_SEPARATOR . $filename;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return file_put_contents($path, $json, LOCK_EX) !== false;
}
