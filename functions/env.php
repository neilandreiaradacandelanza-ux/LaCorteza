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

// Base URL of the site for links in emails, e.g. "http://localhost/lacorteza2" or "https://xxx.up.railway.app".
// Uses APP_URL when set; otherwise works it out from the current request so links always point at this copy of the site.
function app_url()
{
    $configured = env('APP_URL');
    if (!empty($configured)) {
        return rtrim($configured, '/');
    }

    // Railway sits behind a proxy that reports the original scheme in X-Forwarded-Proto
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Scripts live in /pages, /admin or /api; the site root is the folder above them
    $root = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/pages/x.php'))), '/');

    return $scheme . '://' . $host . $root;
}
