<?php
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjust relative path using __DIR__ so it connects properly from pages/ to api/
require_once __DIR__ . '/../api/config.php'; 
require_once __DIR__ . '/../api/paypal_config.php'; 

// Guard: Ensure user is logged in
if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
} 

$user_id = $_SESSION['user_id'];
$reservation_id = intval($_GET['reservation_id'] ?? 0);

if ($reservation_id <= 0) {
    die("Invalid Reservation ID.");
}

// Fetch reservation details for the logged-in user
$stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    die("Reservation not found or access denied.");
}

// Calculate Total Amount & 50% Downpayment
$total_amount = floatval($reservation['total_amount']);
$downpayment_amount = round($total_amount * 0.50, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Downpayment Checkout - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <!-- PayPal JavaScript SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=PHP"></script>
    <style>
        #message {
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
            margin-top: 15px;
            min-height: 20px;
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="navbar">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link">
                <img src="../css/images/logo.png" alt="La Corteza Pool & Venue" class="brand-logo-img">
            </a>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="book-now.php">Book Now</a>
            <a href="reservation.php" class="active">Reservations</a>
            <a href="rates.php">Rates & Packages</a>
            <a href="contacts.php">Contact Us</a>
        </nav>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-id">Account ID: #<?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-primary" style="padding: 8px 16px;">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN DASHBOARD CONTAINER -->
    <main class="dashboard-container">

        <div class="section-header">
            <h2>Complete Your Booking Downpayment</h2>
            <p>Pay your 50% initial downpayment safely via PayPal to confirm your resort slot.</p>
        </div>

        <div class="booking-layout">

            <!-- MAIN CHECKOUT PAYMENT CONTENT -->
            <div class="booking-main-content">
                <div class="booking-card">
                    <span class="card-title-badge">Payment Checkout</span>
                    <h3>Select Payment Method</h3>
                    <p class="card-desc">Review your booking summary on the right and proceed with PayPal checkout below.</p>

                    <div style="margin-top: 20px;">
                        <div id="paypal-buttons"></div> 
                        <p id="message"></p>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR BOOKING SUMMARY -->
            <div class="booking-sidebar">
                <div class="summary-card">
                    <h3>Reservation Details</h3>

                    <div class="summary-row" style="margin-top: 10px;">
                        <span class="info-label">Reference ID</span>
                        <span class="info-value">#<?= htmlspecialchars($reservation['id']) ?></span>
                    </div>

                    <div class="summary-row">
                        <span class="info-label">Package</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['package_type']) ?></span>
                    </div>

                    <div class="summary-row">
                        <span class="info-label">Event Date</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['reservation_date']) ?></span>
                    </div>

                    <div class="summary-row">
                        <span class="info-label">Time Shift</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['time_slot']) ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span class="info-label">Total Amount</span>
                        <span class="info-value">₱<?= number_format($total_amount, 2) ?></span>
                    </div>

                    <div class="summary-row" style="margin-top: 6px;">
                        <span class="info-label" style="font-weight: 700; color: var(--color-dark-olive);">Downpayment Due (50%)</span>
                        <span class="info-value price" style="font-size: 16px; font-weight: 800; color: var(--color-palm-green-hover);">₱<?= number_format($downpayment_amount, 2) ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-info-note">
                        📌 <strong>Downpayment Guarantee:</strong> Paying this initial 50% secures your selected date and shift. The remaining balance will be settled upon arrival at the venue.
                    </div>

                    <div style="margin-top: 15px; text-align: center;">
                        <a href="reservation.php" class="btn-outline-sm" style="display: inline-block; width: 100%; text-align: center; box-sizing: border-box;">&larr; Return to My Reservations</a>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <script> 
        const message = document.getElementById('message'); 

        paypal.Buttons({ 
            // 1. Create Order
            createOrder: function () { 
                return fetch('../api/paypal_create_order.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },  
                    body: JSON.stringify({ 
                        reservation_id: <?= $reservation_id ?>,
                        amount: <?= $downpayment_amount ?>
                    }) 
                }) 
                .then(res => res.json()) 
                .then(order => { 
                    if (order.id) {
                        return order.id;
                    } else {
                        throw new Error(order.error || 'Failed to create PayPal order.');
                    }
                }); 
            }, 
            
            // 2. Capture Order upon Approval
            onApprove: function (data) { 
                return fetch('../api/paypal_capture_order.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },  
                    body: JSON.stringify({ 
                        orderID: data.orderID,
                        reservation_id: <?= $reservation_id ?>,
                        amount: <?= $downpayment_amount ?>
                    })  
                }) 
                .then(res => res.json()) 
                .then(result => { 
                    if (result.success || result.capture_id) {
                        message.style.color = '#2f7551';
                        message.textContent = 'Payment successful! Redirecting to reservations...'; 
                        
                        setTimeout(() => {
                            window.location.href = 'reservation.php?booking=paid';
                        }, 2000);
                    } else {
                        message.style.color = '#9b2c2c';
                        message.textContent = 'Payment failed: ' + (result.error || 'Unknown error');
                    }
                })
                .catch(err => {
                    message.style.color = '#9b2c2c';
                    message.textContent = 'Error processing transaction capture.';
                    console.error(err);
                }); 
            }, 
            
            // 3. User cancels payment
            onCancel: function () { 
                message.style.color = '#c68b59';
                message.textContent = 'Payment was cancelled. Your reservation remains Pending.'; 
            }, 
            
            // 4. SDK Error
            onError: function (err) { 
                message.style.color = '#9b2c2c';
                message.textContent = 'Something went wrong during PayPal payment processing.';  
                console.error(err); 
            } 
        }).render('#paypal-buttons');
    </script>

    <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>

</body> 
</html>