<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/connect.php';

// Fetch active packages dynamically from the database
$query = mysqli_query($connect2db, "SELECT * FROM packages WHERE status = 'Active' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rates & Packages - La Corteza Pool & Venue</title>
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
            <a href="rates.php" class="active">Rates & Packages</a>
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

    <!-- MAIN CONTAINER -->
    <main class="dashboard-container">

        <!-- HEADER SECTION -->
        <div class="section-header">
            <h2>Rates & Rental Packages</h2>
            <p>Choose the perfect package for your family gatherings, birthday celebrations, or private events.</p>
        </div>

        <!-- PACKAGES GRID -->
        <div class="gallery-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            
            <?php $has_packages = false; ?>
            <?php while ($pkg = mysqli_fetch_assoc($query)): ?>
                <?php $has_packages = true; ?>
                
                <div class="booking-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <?php if (!empty($pkg['badge'])): ?>
                            <span class="card-title-badge"><?php echo htmlspecialchars($pkg['badge']); ?></span>
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($pkg['title']); ?></h3>
                        
                        <p class="package-price" style="font-size: 24px; margin: 10px 0;">
                            ₱<?php echo number_format($pkg['price'], 2); ?> 
                            <span style="font-size: 13px; color: var(--color-text-muted); font-weight: normal;">/ shift</span>
                        </p>
                        
                        <p class="card-desc"><?php echo htmlspecialchars($pkg['description']); ?></p>
                        
                        <!-- Dynamic Inclusions List -->
                        <ul style="list-style-type: none; padding: 0; font-size: 13px; color: var(--color-text); line-height: 1.8; margin-bottom: 20px;">
                            <?php 
                            $features = explode('|', $pkg['inclusions']);
                            foreach ($features as $feature) {
                                $feature = trim($feature);
                                if (empty($feature)) continue;
                                
                                // Automatically switch between checkmark and red cross if it starts with 'X '
                                if (stripos($feature, 'X ') === 0) {
                                    echo '<li>❌ ' . htmlspecialchars(substr($feature, 2)) . '</li>';
                                } else {
                                    echo '<li>✅ ' . htmlspecialchars($feature) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                    
                    <!-- Updated Redirect to book-now.php with package selection -->
                    <a href="book-now.php?package=<?php echo urlencode($pkg['title']); ?>" class="btn-primary btn-full-width" style="text-align: center; text-decoration: none;">Book This Package</a>
                </div>
            <?php endwhile; ?>

            <?php if (!$has_packages): ?>
                <div class="empty-state-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <h3>No packages available right now</h3>
                    <p>Please check back later or contact the administrator.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- ADD-ONS & EXTRA RATES SECTION -->
        <div class="booking-card" style="margin-top: 40px;">
            <h3>Optional Add-ons & Extra Rates</h3>
            <p class="card-desc">Enhance your event experience with these optional rental add-ons.</p>

            <div class="form-grid-2">
                <div style="background: var(--color-water-aqua-light); padding: 16px; border-radius: var(--radius-sm);">
                    <h4 style="color: var(--color-dark-olive); margin-bottom: 8px;">🎤 Videoke Machine Rental</h4>
                    <p style="font-size: 13px; color: var(--color-text-muted);">₱1,000 per shift (Unlimited songs with updated songbook and dual mics).</p>
                </div>
                <div style="background: var(--color-water-aqua-light); padding: 16px; border-radius: var(--radius-sm);">
                    <h4 style="color: var(--color-dark-olive); margin-bottom: 8px;">🔥 Extra Barbecue Grill Station</h4>
                    <p style="font-size: 13px; color: var(--color-text-muted);">₱300 (Includes initial charcoal batch and grilling tools).</p>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>

</body>
</html>