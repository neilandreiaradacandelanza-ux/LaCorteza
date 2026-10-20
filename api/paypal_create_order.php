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

$input = json_decode(file_get_contents('php://input'), true);
$reservation_id = intval($input['reservation_id'] ?? 0);
$amount = floatval($input['amount'] ?? 0);

if ($reservation_id <= 0 || $amount <= 0) {
    echo json_encode(['error' => 'Invalid reservation ID or amount.']);
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