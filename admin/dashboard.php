<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db/connect.php';

// Strict Guard: Admin Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Handle Reservation Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $res_id = intval($_POST['reservation_id']);
    $new_status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $res_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php?msg=updated");
    exit();
}

// Fetch Metrics safely
$total_res = 0;
$pending_res = 0;
$confirmed_res = 0;

$q_total = $conn->query("SELECT COUNT(*) as count FROM reservations");
if ($q_total) { $total_res = $q_total->fetch_assoc()['count'] ?? 0; }

$q_pending = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'Pending' OR status = '' OR status IS NULL");
if ($q_pending) { $pending_res = $q_pending->fetch_assoc()['count'] ?? 0; }

$q_confirmed = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status IN ('Confirmed', 'Paid')");
if ($q_confirmed) { $confirmed_res = $q_confirmed->fetch_assoc()['count'] ?? 0; }

// Fetch All Reservations joined with Account/User details (full name + phone)
$query = "SELECT r.*, 
                 COALESCE(NULLIF(a.full_name, ''), NULLIF(TRIM(CONCAT(COALESCE(a.firstname,''), ' ', COALESCE(a.lastname,''))), ''), 'Guest Customer') AS full_name, 
                 COALESCE(NULLIF(r.phone_number, ''), 'No phone provided') AS phone
          FROM reservations r 
          LEFT JOIN accounts a ON r.user_id = a.id 
          ORDER BY r.created_at DESC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Management - La Corteza</title>
    <!-- Connects directly to your style.css stylesheet -->
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .banner-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
    </style>
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body>

    <!-- TOP NAVBAR -->
    <header class="navbar">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link">
                <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img">
            </a>
        </div>
        <div class="nav-actions">
            <span class="user-id">👤 Administrator (<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'System Administrator'); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="dashboard-container">

        <!-- HEADER BANNER -->
        <div class="welcome-banner">
            <div>
                <h1>Reservation <span>Management</span></h1>
                <p>Overview of all resort bookings and status controls.</p>
            </div>
            <div class="banner-actions">
                <a href="schedules.php" class="btn-outline-sm">📅 Blackout Schedules</a>
                <a href="packages.php" class="btn-outline-sm">🏷️ Manage Rates & Packages &rarr;</a>
            </div>
        </div>

        <!-- STATS METRICS OVERVIEW -->
        <div class="reservation-stats-grid">
            <div class="stat-card">
                <span class="stat-label">TOTAL BOOKINGS</span>
                <div class="stat-value"><?php echo $total_res; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-label">PENDING REVIEW</span>
                <div class="stat-value text-warning"><?php echo $pending_res; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-label">CONFIRMED / PAID</span>
                <div class="stat-value"><?php echo $confirmed_res; ?></div>
            </div>
        </div>

        <!-- RESERVATIONS TABLE -->
        <div class="booking-card" style="margin-top: 24px;">
            <h3 style="color: var(--color-dark-olive); margin-bottom: 4px;">All Bookings</h3>
            <p class="card-desc" style="margin-bottom: 20px;">View and manage guest reservation statuses below.</p>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); color: var(--color-dark-olive);">
                            <th style="padding: 12px 8px;">ID</th>
                            <th style="padding: 12px 8px;">Guest Info</th>
                            <th style="padding: 12px 8px;">Date & Shift</th>
                            <th style="padding: 12px 8px;">Package</th>
                            <th style="padding: 12px 8px;">Guests</th>
                            <th style="padding: 12px 8px;">Amount</th>
                            <th style="padding: 12px 8px;">Status</th>
                            <th style="padding: 12px 8px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php 
                                    $shift = $row['time_slot'] ?? $row['shift'] ?? 'N/A';
                                    $guests = $row['guest_count'] ?? $row['adults_count'] ?? 0;
                                    $amount = $row['total_amount'] ?? $row['amount'] ?? 0;
                                    $status = trim($row['status'] ?? 'Pending');
                                    
                                    // Fallback for empty DB values
                                    if (empty($status)) {
                                        $status = 'Pending';
                                    }

                                    $statusClass = 'status-pending';
                                    if ($status === 'Confirmed' || $status === 'Paid') {
                                        $statusClass = 'status-confirmed';
                                    } elseif ($status === 'Cancelled') {
                                        $statusClass = 'status-cancelled';
                                    }
                                ?>
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: 12px 8px;"><strong>#<?php echo $row['id']; ?></strong></td>
                                    <td style="padding: 12px 8px;">
                                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                        <span class="info-label">📞 <?php echo htmlspecialchars($row['phone']); ?></span>
                                    </td>
                                    <td style="padding: 12px 8px;">
                                        <strong><?php echo htmlspecialchars($row['reservation_date'] ?? 'N/A'); ?></strong><br>
                                        <span class="info-label"><?php echo htmlspecialchars($shift); ?></span>
                                    </td>
                                    <td style="padding: 12px 8px;"><?php echo htmlspecialchars($row['package_type'] ?? 'Standard'); ?></td>
                                    <td style="padding: 12px 8px;"><?php echo htmlspecialchars($guests); ?> pax</td>
                                    <td style="padding: 12px 8px;"><strong style="color: var(--color-palm-green-hover);">₱<?php echo number_format($amount, 2); ?></strong></td>
                                    <td style="padding: 12px 8px;">
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 8px;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" class="form-input" style="padding: 6px 10px; font-size: 12px; width: auto;" onchange="this.form.submit()">
                                                <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Confirmed" <?php echo ($status === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="Paid" <?php echo ($status === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                                <option value="Cancelled" <?php echo ($status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 24px; color: var(--color-text-muted);">No reservations found in database.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>   

</body>
</html>
