<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../functions/env.php';

error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG') === 'false' ? 0 : 1);

// Uses environment variables on Railway (Aiven credentials); falls back to .env / local XAMPP defaults.
$host = env('DB_HOST', "localhost");
$port = (int) env('DB_PORT', 3306);
$user = env('DB_USER', "root");
$password = env('DB_PASSWORD', "");
$database = env('DB_NAME', "lacorteza");
$result = "";
$resultClass = "";

$connect2db = mysqli_init();

// Aiven requires SSL. Set DB_SSL=true; optionally provide the CA cert contents in DB_CA_CERT.
if (env('DB_SSL') === 'true') {
    $caPath = null;
    if (env('DB_CA_CERT')) {
        // Repair the PEM if its line breaks were lost when pasted into the hosting dashboard.
        $cert = trim(str_replace('\n', "\n", env('DB_CA_CERT')));
        if (strpos($cert, "\n") === false && preg_match('/-----BEGIN CERTIFICATE-----(.+)-----END CERTIFICATE-----/', $cert, $m)) {
            $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split(preg_replace('/\s+/', '', $m[1]), 64, "\n") . "-----END CERTIFICATE-----";
        }
        $caPath = sys_get_temp_dir() . '/aiven-ca.pem';
        file_put_contents($caPath, $cert . "\n");
    }
    mysqli_ssl_set($connect2db, null, null, $caPath, null, null);
    $flags = $caPath ? MYSQLI_CLIENT_SSL : MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
}

// PHP 8.1+ throws on connection failure, so catch it to show a readable message instead of a blank 500.
try {
    $connected = @mysqli_real_connect($connect2db, $host, $user, $password, $database, $port, null, $flags ?? 0);
    $connectError = $connected ? '' : mysqli_connect_error();
} catch (mysqli_sql_exception $e) {
    $connected = false;
    $connectError = $e->getMessage();
}

if (!$connected) {
    error_log("Database connection failed ($host:$port): $connectError");
    die("Database connection failed: " . htmlspecialchars($connectError));
}

// Alias $conn so all scripts using $conn work without error
$conn = $connect2db;
?>