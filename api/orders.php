<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_check.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Join payment table with reservations to get package details and payment history
$sql = "SELECT p.payment_id AS id, 
               r.package_type AS product_name, 
               p.payment_amount AS amount, 
               'PHP' AS currency, 
               p.payment_status AS status, 
               p.created_at 
        FROM payment p
        JOIN reservations r ON p.reservation_id = r.id
        WHERE r.user_id = ?
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($payments);