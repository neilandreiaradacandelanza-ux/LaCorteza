<?php
// Suppress warnings to prevent HTML notices from breaking JSON output
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/paypal_config.php';
require_once __DIR__ . '/paypal_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any accidental output or whitespace buffer
if (ob_get_length()) {
    ob_clean();
}

$input = json_decode(file_get_contents('php://input'), true);
$orderID = $input['orderID'] ?? '';
$reservation_id = intval($input['reservation_id'] ?? 0);
$amount = floatval($input['amount'] ?? 0);

if (empty($orderID) || $reservation_id <= 0) {
    echo json_encode(['error' => 'Missing Order ID or Reservation ID']);
    exit();
}

// 1. Capture Order via PayPal API using paypalRequest helper
$response = paypalRequest('POST', '/v2/checkout/orders/' . $orderID . '/capture');

$httpCode = $response['status'] ?? 0;
$captureData = $response['data'] ?? [];

if ($httpCode === 201 || $httpCode === 200) {
    $status = $captureData['status'] ?? '';

    if ($status === 'COMPLETED') {
        $capture_id = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? $orderID;

        // 2. Update Reservation Status to 'Confirmed'
        $stmt = $conn->prepare("UPDATE reservations SET status = 'Confirmed' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $stmt->close();
        }

        // 3. Insert Record into Payment Table
        $stmtPay = $conn->prepare("
            INSERT INTO payment (reservation_id, payment_type, payment_amount, paypal_order_id, paypal_capture_id, payment_status)
            VALUES (?, 'PayPal', ?, ?, ?, 'Completed')
        ");
        if ($stmtPay) {
            $stmtPay->bind_param("idss", $reservation_id, $amount, $orderID, $capture_id);
            $stmtPay->execute();
            $stmtPay->close();
        }

        echo json_encode([
            'success' => true,
            'capture_id' => $capture_id
        ]);
    } else {
        echo json_encode([
            'error' => 'Payment capture status: ' . $status,
            'details' => $captureData
        ]);
    }
} else {
    echo json_encode([
        'error' => 'Failed to capture PayPal payment',
        'http_code' => $httpCode,
        'details' => $captureData
    ]);
}
exit();