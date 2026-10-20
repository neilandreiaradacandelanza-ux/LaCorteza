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
        $caPath = sys_get_temp_dir() . '/aiven-ca.pem';
        file_put_contents($caPath, env('DB_CA_CERT'));
    }
    mysqli_ssl_set($connect2db, null, null, $caPath, null, null);
    $flags = $caPath ? MYSQLI_CLIENT_SSL : MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
    $connected = @mysqli_real_connect($connect2db, $host, $user, $password, $database, $port, null, $flags);
} else {
    $connected = @mysqli_real_connect($connect2db, $host, $user, $password, $database, $port);
}

if (!$connected) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Alias $conn so all scripts using $conn work without error
$conn = $connect2db;
?>