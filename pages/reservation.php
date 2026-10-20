<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db/connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$reservations = [];

// Handle user-initiated cancellation for pending bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
    $res_id = intval($_POST['reservation_id']);
    if ($user_id && $res_id > 0) {
        $cancel_stmt = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'");
        if ($cancel_stmt) {
            $cancel_stmt->bind_param("ii", $res_id, $user_id);
            $cancel_stmt->execute();
            $cancel_stmt->close();
        }
    }
    header("Location: reservation.php?msg=cancelled");
    exit();
}

// Fetch user reservations if logged in
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - La Corteza Pool & Venue</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .btn-pay {
            background-color: #6f4e37;
            color: #ffffff;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s ease;
            display: inline-block;
        }
        .btn-pay:hover {
            background-color: #543a27;
        }
    </style>
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="navbar">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link"><img src="../css/images/logo.png" alt="La Corteza Pool & Venue" class="brand-logo-img"></a>
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

    <main class="dashboard-container">

        <div class="section-header" style="text-align: center; margin-bottom: 28px;">
            <h2>Your Reservations</h2>
            <p><?php echo $user_id ? 'Manage and review your upcoming resort bookings below.' : 'Track your reservation status or log in to view history.'; ?></p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'booked'): ?>
            <div class="success" style="text-align: center; margin-bottom: 20px; padding: 12px; background-color: #e6f4ea; color: #2f7551; border-radius: 8px;">
                🎉 Reservation submitted successfully! Click <strong>Pay Downpayment</strong> below to complete your payment via PayPal.
            </div>
        <?php endif; ?>

        <?php if ($user_id): ?>
            <!-- LOGGED-IN VIEW: SIDE-BY-SIDE 2-COLUMN GRID -->
            <?php if (!empty($reservations)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; max-width: 1100px; margin: 0 auto;">
                    <?php foreach ($reservations as $res): ?>
                        <?php 
                            $status = $res['status'] ?? 'Pending';
                            $statusClass = 'status-pending';
                            if ($status === 'Confirmed' || $status === 'Paid') {
                                $statusClass = 'status-confirmed';
                            } elseif ($status === 'Cancelled') {
                                $statusClass = 'status-cancelled';
                            }

                            $shift = $res['time_slot'] ?? $res['shift'] ?? 'N/A';
                            $guests = $res['guest_count'] ?? $res['adults_count'] ?? 0;
                            $amount = $res['total_amount'] ?? $res['amount'] ?? 0;
                            $downpayment = $amount * 0.50;
                        ?>
                        <div class="summary-card" style="padding: 28px; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
                            
                            <div>
                                <!-- CARD HEADER -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 18px; border-bottom: 1px solid var(--color-border, #e2e8f0); padding-bottom: 14px;">
                                    <div style="flex: 1;">
                                        <span style="font-size: 12px; font-weight: bold; color: var(--color-text-muted, #666); text-transform: uppercase; letter-spacing: 0.5px;">Reservation #<?php echo $res['id']; ?></span>
                                        <h3 style="font-size: 20px; margin: 4px 0 0 0; color: var(--color-dark-olive, #2e7d32); line-height: 1.3;">
                                            <?php echo htmlspecialchars($res['package_type']); ?>
                                        </h3>
                                    </div>
                                    <div>
                                        <span class="status-badge <?php echo $statusClass; ?>" style="font-size: 13px; padding: 6px 14px; font-weight: 600;">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- CARD DETAILS GRID -->
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px;">
                                    
                                    <div style="background-color: var(--color-bg-light, #f8fafc); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--color-border, #e2e8f0);">
                                        <span class="info-label" style="font-size: 11.5px; display: block; margin-bottom: 4px;">📅 Date</span>
                                        <strong style="font-size: 14.5px; color: #1e293b;"><?php echo htmlspecialchars($res['reservation_date']); ?></strong>
                                    </div>

                                    <div style="background-color: var(--color-bg-light, #f8fafc); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--color-border, #e2e8f0);">
                                        <span class="info-label" style="font-size: 11.5px; display: block; margin-bottom: 4px;">👥 Guests</span>
                                        <strong style="font-size: 14.5px; color: #1e293b;"><?php echo htmlspecialchars($guests); ?> Persons</strong>
                                    </div>

                                    <div style="background-color: var(--color-bg-light, #f8fafc); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--color-border, #e2e8f0); grid-column: span 2;">
                                        <span class="info-label" style="font-size: 11.5px; display: block; margin-bottom: 4px;">⏰ Shift</span>
                                        <strong style="font-size: 14px; color: #1e293b;"><?php echo htmlspecialchars($shift); ?></strong>
                                    </div>

                                    <div style="background-color: var(--color-bg-light, #f8fafc); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--color-border, #e2e8f0); grid-column: span 2;">
                                        <span class="info-label" style="font-size: 11.5px; display: block; margin-bottom: 4px;">💳 Total Amount (Downpayment 50%: ₱<?php echo number_format($downpayment, 2); ?>)</span>
                                        <strong style="font-size: 16px; color: var(--color-palm-green-hover, #2e7d32);">₱<?php echo number_format($amount, 2); ?></strong>
                                    </div>

                                </div>
                            </div>

                            <!-- CARD FOOTER ACTIONS -->
                            <?php if ($status === 'Pending'): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--color-border, #e2e8f0); margin-top: 10px; padding-top: 14px;">
                                    
                                    <!-- CANCEL BUTTON -->
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this pending reservation request?');">
                                        <input type="hidden" name="action" value="cancel_reservation">
                                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                        <button type="submit" class="logout-btn" style="padding: 8px 16px; font-size: 13px; cursor: pointer;">Cancel Request</button>
                                    </form>

                                    <!-- PAYPAL CHECKOUT BUTTON -->
                                    <a href="checkout.php?reservation_id=<?php echo $res['id']; ?>" class="btn-pay">
                                        Pay Downpayment (PayPal) &rarr;
                                    </a>    

                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="summary-card" style="max-width: 500px; margin: 0 auto; text-align: center; padding: 40px 32px;">
                    <h3 style="margin-bottom: 8px;">No Active Bookings</h3>
                    <p style="margin-bottom: 20px; color: var(--color-text-muted, #666); font-size: 14px;">You haven't submitted any resort reservations yet.</p>
                    <a href="book-now.php" class="btn-primary" style="display: inline-block; padding: 12px 28px; text-decoration: none;">Book a Venue Now</a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- GUEST VIEW: LOGIN PROMPT -->
            <div class="summary-card" style="max-width: 500px; margin: 0 auto; padding: 36px 32px; text-align: center;">
                <h3 style="margin-bottom: 8px;">Look Up Reservation</h3>
                <p style="font-size: 14px; color: #666; margin-bottom: 24px;">Already made a booking? Log in to view your complete reservation history.</p>
                <a href="login.php" class="btn-primary" style="display: inline-block; width: 100%; padding: 12px; text-decoration: none;">Log In to Account</a>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
        <p style="margin-top: 8px;">
            <a href="index.php" style="color: #cbd5e1; text-decoration: none; font-size: 11px;">Admin Portal</a>
        </p>
    </footer>

</body>
</html>