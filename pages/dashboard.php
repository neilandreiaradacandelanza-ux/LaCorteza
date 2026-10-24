<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db/connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Corteza Pool & Venue</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

    <script>
    // Press Ctrl + Shift + A to go to the Admin Portal
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            window.location.href = '../admin/index.php';
        }
    });
    </script>   
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="navbar">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link"><img src="../css/images/logo.png" alt="La Corteza Pool & Venue" class="brand-logo-img"></a>
        </div>
        <!-- Hamburger: shown on small screens, opens the links and account actions -->
        <button type="button" class="nav-toggle" aria-label="Open menu" aria-expanded="false" onclick="const bar = this.closest('.navbar'); const open = bar.classList.toggle('nav-open'); this.setAttribute('aria-expanded', open); this.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="book-now.php">Book Now</a>
            <a href="reservation.php">Reservations</a>
            <a href="rates.php">Rates & Packages</a>
            <a href="contacts.php">Contact Us</a>
        </nav>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-id"><?php echo htmlspecialchars(trim($_SESSION['full_name'] ?? '') ?: 'My Account'); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-primary" style="padding: 8px 16px;">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="dashboard-container">
        
        <!-- WELCOME BANNER -->
        <section class="welcome-banner">
            <div>
                <h1>Welcome to La Corteza Pool & Venue<?php echo isset($_SESSION['full_name']) ? ', <span>' . htmlspecialchars($_SESSION['full_name']) . '</span>' : ''; ?>!</h1>
                <p>Manage your pool and venue reservations or check upcoming availability below.</p>
            </div>
            <a href="book-now.php" class="btn-primary">+ New Reservation</a>
        </section>

        <!-- RESORT GALLERY SECTION -->
        <section class="gallery-section">
            <div class="section-header">
                <h2>Resort Gallery</h2>
                <p>Click on any photo to view in full size.</p>
            </div>

            <div class="gallery-grid">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <a href="#img-<?php echo $i; ?>" class="gallery-card">
                        <img src="../css/images/<?php echo $i; ?>.jpg" onerror="this.onerror=null; this.src='../css/images/<?php echo $i; ?>.png';" alt="La Corteza Resort View <?php echo $i; ?>" class="gallery-img" loading="lazy">
                        <div class="gallery-overlay"><span class="gallery-tag">View Full Photo 🔍</span></div>
                    </a>

                    <!-- PURE CSS LIGHTBOX MODAL FOR EACH IMAGE -->
                    <div id="img-<?php echo $i; ?>" class="lightbox-modal">
                        <a href="#close" class="lightbox-close">&times;</a>
                        <div class="lightbox-box">
                            <img src="../css/images/<?php echo $i; ?>.jpg" onerror="this.onerror=null; this.src='../css/images/<?php echo $i; ?>.png';" alt="La Corteza Resort View <?php echo $i; ?>">
                            <p class="lightbox-caption">La Corteza Resort View #<?php echo $i; ?></p>
                        </div>
                        <a href="#close" class="lightbox-backdrop"></a>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

    </main>

    <!-- FOOTER WITH DISCRETE ADMIN PORTAL LINK -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
        <p style="margin-top: 8px;">
        </p>
    </footer>

</body>
</html>