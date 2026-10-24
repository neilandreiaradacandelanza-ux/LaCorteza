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

$msg = '';

// Handle Adding a Blocked Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_block') {
    $blocked_date = trim($_POST['blocked_date']);
    $time_slot = trim($_POST['time_slot']);
    $reason = trim($_POST['reason']);

    if (!empty($blocked_date) && !empty($reason)) {
        $stmt = $conn->prepare("INSERT INTO schedules (blocked_date, time_slot, reason) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE time_slot = VALUES(time_slot), reason = VALUES(reason)");
        $stmt->bind_param("sss", $blocked_date, $time_slot, $reason);
        if ($stmt->execute()) {
            $msg = "Schedule blocked successfully!";
        } else {
            $msg = "Error blocking schedule date.";
        }
        $stmt->close();
    }
}

// Handle Removing a Blocked Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_block') {
    $block_id = intval($_POST['block_id']);
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $block_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Blocked date removed!";
}

// Fetch All Blocked Schedules
$blocked_schedules = $conn->query("SELECT * FROM schedules ORDER BY blocked_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance & Schedule Blockouts - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body>

    <!-- TOP NAVBAR -->
    <header class="navbar navbar-centered">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link">
                <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img">
            </a>
        </div>
        <!-- Hamburger: shown on small screens, opens the links and account actions -->
        <button type="button" class="nav-toggle" aria-label="Open menu" aria-expanded="false" onclick="const bar = this.closest('.navbar'); const open = bar.classList.toggle('nav-open'); this.setAttribute('aria-expanded', open); this.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="packages.php">Rates &amp; Packages</a>
            <a href="schedules.php"> Schedules</a>
        </nav>
        <div class="nav-actions">
            <span class="user-id">👤 Administrator (<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'System Administrator'); ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="dashboard-container">

        <!-- HEADER BANNER -->
        <div class="welcome-banner">
            <div>
                <h1>Manage <span>Schedule Closures & Maintenance</span></h1>
                <p>Block specific dates or time slots for maintenance, private events, or venue unavailability.</p>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-top: 20px; font-weight: 500;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- BLOCK DATE FORM CARD -->
        <div class="booking-card" style="margin-top: 24px;">
            <h3 style="color: var(--color-dark-olive);">Block a Date / Shift</h3>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_block">
                
                <div class="form-group">
                    <label>Select Date:</label>
                    <input type="date" name="blocked_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Select Shift / Time Slot:</label>
                    <select name="time_slot" class="form-input">
                        <option value="All Shifts">Entire Day (All Shifts)</option>
                        <option value="Day Shift (8:00 AM - 5:00 PM)">Day Shift Only</option>
                        <option value="Night Shift (6:00 PM - 12:00 MN)">Night Shift Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reason / Explanation:</label>
                    <input type="text" name="reason" class="form-input" placeholder="e.g. Resort Maintenance, Private Event" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="logout-btn" style="background: var(--color-dark-olive); color: white; border: none; padding: 10px 16px; cursor: pointer; border-radius: 6px; font-weight: 600; height: 38px;">
                        🚫 Save Unavailable Date
                    </button>
                </div>
            </form>
        </div>

        <!-- CURRENT BLOCKED DATES TABLE -->
        <div class="booking-card" style="margin-top: 24px;">
            <h3 style="color: var(--color-dark-olive); margin-bottom: 4px;">Active Blocked Dates</h3>
            <p class="card-desc" style="margin-bottom: 20px;">Manage active resort blackout dates and schedules.</p>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); color: var(--color-dark-olive);">
                            <th style="padding: 12px 8px;">Date</th>
                            <th style="padding: 12px 8px;">Shift</th>
                            <th style="padding: 12px 8px;">Reason</th>
                            <th style="padding: 12px 8px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($blocked_schedules && $blocked_schedules->num_rows > 0): ?>
                            <?php while ($row = $blocked_schedules->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: 12px 8px;"><strong><?php echo $row['blocked_date']; ?></strong></td>
                                    <td style="padding: 12px 8px;"><?php echo htmlspecialchars($row['time_slot']); ?></td>
                                    <td style="padding: 12px 8px; color: #991b1b; font-weight: 500;"><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td style="padding: 12px 8px;">
                                        <form method="POST" onsubmit="return confirm('Unblock this date?');" style="margin: 0;">
                                            <input type="hidden" name="action" value="remove_block">
                                            <input type="hidden" name="block_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn-danger">
                                                🗑️ Remove Block
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 24px; color: var(--color-text-muted);">No blackout dates set.</td>
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