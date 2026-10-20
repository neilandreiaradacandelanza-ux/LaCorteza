<?php
// Reads a setting from the server environment (Railway), falling back to the local .env file (XAMPP).
function env($key, $default = null)
{
    static $local = null;
    if ($local === null) {
        $file = __DIR__ . '/../.env';
        $local = file_exists($file) ? (parse_ini_file($file) ?: []) : [];
    }
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    return $local[$key] ?? $default;
}
