<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - La Corteza Pool & Venue</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
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
            <a href="reservation.php">Reservations</a>
            <a href="rates.php">Rates & Packages</a>
            <a href="contacts.php" class="active">Contact Us</a>
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

    <!-- MAIN CONTAINER -->
    <main class="dashboard-container">

        <div class="section-header" style="text-align: center;">
            <h2>Contact & Resort Details</h2>
            <p>Get in touch with us directly or visit our social media channels.</p>
        </div>

        <!-- RESORT DETAILS CARD (CENTERED) -->
        <div style="max-width: 580px; width: 100%; margin: 0 auto;">
            <div class="summary-card" style="padding: 32px;">
                <h3 style="font-size: 20px; margin-bottom: 6px;">Resort Details</h3>
                
                <div style="display: flex; flex-direction: column; gap: 18px; font-size: 14px;">
                    <div>
                        <span class="info-label" style="font-size: 12px;">📍 Resort Location</span>
                        <p style="color: var(--color-dark-olive); font-weight: 600; margin-top: 4px; font-size: 15px;">
                            La Corteza Pool & Venue<br>
                            Purok 15, Brgy. Tipanoy, Philippines
                        </p>
                    </div>

                    <div class="summary-divider"></div>

                    <div>
                        <span class="info-label" style="font-size: 12px;">📞 Mobile / GCash Contact</span>
                        <p style="color: var(--color-dark-olive); font-weight: 600; margin-top: 4px; font-size: 15px;">
                            +63 981 708 8574
                        </p>
                    </div>

                    <div class="summary-divider"></div>

                    <div>
                        <span class="info-label" style="font-size: 12px;">✉️ Email Support</span>
                        <p style="color: var(--color-dark-olive); font-weight: 600; margin-top: 4px; font-size: 15px;">
                            roselyncortez@gmail.com
                        </p>
                    </div>

                    <div class="summary-divider"></div>

                    <div>
                        <span class="info-label" style="font-size: 12px;">⏰ Operating Hours</span>
                        <p style="color: var(--color-dark-olive); font-weight: 600; margin-top: 4px; font-size: 15px; line-height: 1.6;">
                            Day Shift: 8:00 AM - 5:00 PM<br>
                            Night Shift: 6:00 PM - 12:00 MN
                        </p>
                    </div>

                    <div class="summary-divider"></div>

                    <div>
                        <span class="info-label" style="font-size: 12px;">🌐 Follow Us On Social Media</span>
                        <div style="display: flex; gap: 12px; margin-top: 8px;">
                            <a href="https://www.facebook.com/profile.php?id=61576611069332" target="_blank" class="btn-outline-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                                📘 Facebook
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER WITH DISCRETE ADMIN PORTAL LINK -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>
</body>
</html>