<?php
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

if (ob_get_length()) {
    ob_clean();
}

$user_id = intval($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['error' => 'Please log in to pay for a reservation.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$reservation_id = intval($input['reservation_id'] ?? 0);

// Look up the reservation server-side: it must belong to this customer and still be awaiting payment
$stmt = $conn->prepare("SELECT total_amount FROM reservations WHERE id = ? AND user_id = ? AND status = 'Pending' LIMIT 1");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    echo json_encode(['error' => 'Reservation not found or no longer awaiting payment.']);
    exit();
}

// 50% downpayment, computed from the stored total (never trusted from the browser)
$amount = round(floatval($reservation['total_amount']) * 0.50, 2);
if ($amount <= 0) {
    echo json_encode(['error' => 'This reservation has no amount due.']);
    exit();
}

$payload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'reference_id' => 'RESERVATION_' . $reservation_id,
            'amount' => [
                'currency_code' => 'PHP',
                'value' => number_format($amount, 2, '.', '')
            ],
            'description' => 'Downpayment for Reservation #' . $reservation_id
        ]
    ]
];

// Call the paypalRequest function from helper
$response = paypalRequest('POST', '/v2/checkout/orders', $payload);

if ($response['status'] === 201 && isset($response['data']['id'])) {
    echo json_encode(['id' => $response['data']['id']]);
} else {
    echo json_encode([
        'error' => 'PayPal Order Creation Failed',
        'details' => $response['data'] ?? 'No response data'
    ]);
}
exit();
