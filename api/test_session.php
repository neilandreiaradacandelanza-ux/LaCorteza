<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION
]);