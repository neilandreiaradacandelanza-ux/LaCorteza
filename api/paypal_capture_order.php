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

$user_id = intval($_SESSION['user_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
$orderID = $input['orderID'] ?? '';
$reservation_id = intval($input['reservation_id'] ?? 0);

if ($user_id <= 0 || empty($orderID) || $reservation_id <= 0) {
    echo json_encode(['error' => 'Missing Order ID, Reservation ID or login session']);
    exit();
}

// 1. The reservation must belong to this customer and still be awaiting payment
$stmt = $conn->prepare("SELECT total_amount FROM reservations WHERE id = ? AND user_id = ? AND status = 'Pending' LIMIT 1");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    echo json_encode(['error' => 'Reservation not found or no longer awaiting payment.']);
    exit();
}

$expected_amount = number_format(round(floatval($reservation['total_amount']) * 0.50, 2), 2, '.', '');

// 2. Before taking the money, check the PayPal order is for this reservation and the full downpayment
$order = paypalRequest('GET', '/v2/checkout/orders/' . urlencode($orderID));
$unit = $order['data']['purchase_units'][0] ?? [];

if (($order['status'] ?? 0) !== 200
    || ($unit['reference_id'] ?? '') !== 'RESERVATION_' . $reservation_id
    || ($unit['amount']['currency_code'] ?? '') !== 'PHP'
    || ($unit['amount']['value'] ?? '') !== $expected_amount) {
    echo json_encode(['error' => 'PayPal order does not match this reservation\'s downpayment.']);
    exit();
}

// 3. Capture Order via PayPal API using paypalRequest helper
$response = paypalRequest('POST', '/v2/checkout/orders/' . urlencode($orderID) . '/capture');

$httpCode = $response['status'] ?? 0;
$captureData = $response['data'] ?? [];

if ($httpCode === 201 || $httpCode === 200) {
    $status = $captureData['status'] ?? '';
    $capture = $captureData['purchase_units'][0]['payments']['captures'][0] ?? [];

    if ($status === 'COMPLETED' && ($capture['amount']['value'] ?? '') === $expected_amount) {
        $capture_id = $capture['id'] ?? $orderID;
        $paid_amount = floatval($capture['amount']['value']);

        // 4. Update Reservation Status to 'Confirmed'
        $stmt = $conn->prepare("UPDATE reservations SET status = 'Confirmed' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $stmt->close();
        }

        // 5. Insert Record into Payment Table, using the amount PayPal actually captured
        $stmtPay = $conn->prepare("
            INSERT INTO payment (reservation_id, payment_type, payment_amount, paypal_order_id, paypal_capture_id, payment_status)
            VALUES (?, 'PayPal', ?, ?, ?, 'Completed')
        ");
        if ($stmtPay) {
            $stmtPay->bind_param("idss", $reservation_id, $paid_amount, $orderID, $capture_id);
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
