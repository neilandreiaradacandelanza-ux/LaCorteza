<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db/connect.php';
require_once __DIR__ . '/../functions/auth_functions.php';

$result = '';$resultClass = '';
$booking_fields = ['full_name', 'email', 'phone', 'reservation_date', 'shift', 'guest_count', 'package_type'];

// Handle Booking Submit using auth_function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (empty($_SESSION['user_id'])) {
        // Guests can fill in the form, but must log in to book: keep their details and send them to login
        $_SESSION['pending_booking'] = array_intersect_key($_POST, array_flip($booking_fields));
        $_SESSION['success_msg'] = "Please log in or create an account to complete your booking. Your booking details have been saved.";
        header("Location: login.php?redirect=book-now.php");
        exit();
    }
    unset($_SESSION['pending_booking']);
    book_reservation($_POST,$connect2db, $result,$resultClass);
}

// Values to refill the form with: the just-submitted form (after an error) or details saved before login
$form = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : ($_SESSION['pending_booking'] ?? []);
if (!empty($_SESSION['pending_booking']) && !empty($_SESSION['user_id']) && empty($result)) {
    $resultClass = "success";
    $result = "Welcome back! Your booking details were restored. Please review them and click Confirm & Proceed to Payment.";
}
$old = function ($field, $default = '') use ($form) {
    return htmlspecialchars($form[$field] ?? $default);
};

// Fetch active packages created by Admin from database
$packages_query = mysqli_query($connect2db, "SELECT * FROM packages WHERE status = 'Active' ORDER BY id ASC");
$packages_list = [];
if ($packages_query) {
    while ($row = mysqli_fetch_assoc($packages_query)) {
        $packages_list[] =$row;
    }
}

// Fetch blocked schedule dates to pass to JS
$schedules_query = mysqli_query($connect2db, "SELECT blocked_date, time_slot, reason FROM schedules");
$blocked_schedules = [];
if ($schedules_query) {
    while ($s_row = mysqli_fetch_assoc($schedules_query)) {
        $blocked_schedules[] =$s_row;
    }
}

// Check if a package was passed via URL parameter
$preselected_package =$_GET['package'] ?? $form['package_type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Now - La Corteza Pool & Venue</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="navbar">
        <div class="nav-brand">
            <a href="dashboard.php" class="logo-link"><img src="../css/images/logo.png" alt="La Corteza Pool & Venue" class="brand-logo-img"></a>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="book-now.php" class="active">Book Now</a>
            <a href="reservation.php">Reservations</a>
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

    <!-- MAIN CONTAINER -->
    <main class="dashboard-container">

        <div class="section-header">
            <h2>Reserve Your Venue & Pool</h2>
            <p>Fill out your details and schedule to submit your reservation request.</p>
        </div>

        <?php if (!empty($result)): ?>
            <div class="<?php echo $resultClass; ?>" style="text-align: center; margin-bottom: 20px; padding: 12px; border-radius: 6px; background: <?php echo ($resultClass === 'error') ? '#fee2e2' : '#d1fae5'; ?>; color: <?php echo ($resultClass === 'error') ? '#991b1b' : '#065f46'; ?>;">
                <?php echo htmlspecialchars($result); ?>
            </div>
        <?php endif; ?>

        <div id="client-date-warning" style="display: none; text-align: center; margin-bottom: 20px; padding: 12px; border-radius: 6px; background: #fee2e2; color: #991b1b; font-weight: 500;"></div>

        <div class="booking-layout">
            <!-- MAIN BOOKING FORM -->
            <div class="booking-main-content">
                <form action="" method="POST" class="booking-card">

                    <span class="card-title-badge">Step 1 of 3</span>
                    <h3>Guest Information</h3>
                    <p class="card-desc">Your contact details for booking confirmation.</p>

                    <div class="form-group margin-bottom-md">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" placeholder="e.g. Neil Andrei Candelanza" value="<?php echo $old('full_name', trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? ''))); ?>" required>
                    </div>

                    <div class="form-grid-2 margin-bottom-md">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g. neil@example.com" value="<?php echo $old('email', $_SESSION['user']['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Mobile / Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="e.g. 09817088574" value="<?php echo $old('phone'); ?>" required>
                        </div>
                    </div>

                    <div class="summary-divider" style="margin: 24px 0;"></div>

                    <span class="card-title-badge" style="top: inherit;">Step 2 of 3</span>
                    <h3>Reservation Details</h3>
                    <p class="card-desc">Choose your preferred date, shift, and guest count.</p>

                    <div class="form-grid-2 margin-bottom-md">
                        <div class="form-group">
                            <label for="reservation_date">Reservation Date</label>
                            <input type="date" id="reservation_date" name="reservation_date" class="form-input" value="<?php echo $old('reservation_date'); ?>" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="shift">Preferred Shift</label>
                            <select id="shift" name="shift" class="form-input" required>
                                <?php foreach (['Day Shift (8:00 AM - 5:00 PM)', 'Night Shift (6:00 PM - 12:00 MN)', '24 Hours Exclusive'] as $shift_option): ?>
                                    <option value="<?php echo $shift_option; ?>" <?php echo ($form['shift'] ?? '') === $shift_option ? 'selected' : ''; ?>><?php echo $shift_option; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group margin-bottom-md">
                        <label for="guest_count">Estimated Number of Guests</label>
                        <input type="number" id="guest_count" name="guest_count" class="form-input" placeholder="e.g. 30" min="1" max="150" value="<?php echo $old('guest_count'); ?>" required>
                    </div>

                    <div class="summary-divider" style="margin: 24px 0;"></div>

                    <span class="card-title-badge" style="top: inherit;">Step 3 of 3</span>
                    <h3>Select Package</h3>
                    <p class="card-desc">Pick a reservation tier for your event.</p>

                    <div class="package-options-grid margin-bottom-md">
                        <?php if (!empty($packages_list)): ?>
                            <?php 
                            $default_title = '';$default_price = 0;
                            
                            foreach ($packages_list as$index => $pkg):$is_checked = false;
                                if (!empty($preselected_package) && strtolower($preselected_package) === strtolower($pkg['title'])) {$is_checked = true;
                                } elseif (empty($preselected_package) && $index === 0) {$is_checked = true;
                                }

                                if ($is_checked) {
                                    $default_title =$pkg['title'];
                                    $default_price =$pkg['price'];
                                }
                            ?>
                                <label class="package-option-card" style="cursor: pointer;">
                                    <input type="radio" name="package_type" value="<?php echo htmlspecialchars($pkg['title']); ?>" data-price="<?php echo $pkg['price']; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                    <div class="package-content">
                                        <div class="package-header" style="display: flex; justify-content: space-between; align-items: center;">
                                            <h4><?php echo htmlspecialchars($pkg['title']); ?></h4>
                                            <span class="package-price" style="font-weight: bold; color: var(--color-dark-olive, #2e7d32);">₱<?php echo number_format($pkg['price'], 0); ?></span>
                                        </div>
                                        <p style="font-size: 13px; color: #666; margin-top: 4px;"><?php echo htmlspecialchars($pkg['description'] ?: 'Exclusive resort reservation package.'); ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <label class="package-option-card">
                                <input type="radio" name="package_type" value="Day / Night Shift Exclusive" data-price="10000" checked>
                                <div class="package-content">
                                    <div class="package-header">
                                        <h4>Day / Night Shift Exclusive</h4>
                                        <span class="package-price">₱10,000</span>
                                    </div>
                                    <p>Exclusive use of pool, pavilion, function area, and basic sound system.</p>
                                </div>
                            </label>
                            <?php 
                            $default_title = 'Day / Night Shift Exclusive';$default_price = 10000;
                            ?>
                        <?php endif; ?>
                    </div>

                    <button type="submit" id="btn-submit-booking" name="submit_booking" class="btn-primary" style="width: 100%; margin-top: 10px; padding: 14px;">Confirm & Proceed to Payment</button>

                </form>
            </div>

            <!-- SIDEBAR SUMMARY -->
            <div class="booking-sidebar">
                <div class="summary-card">
                    <h3>Booking Summary</h3>
                    
                    <div class="summary-row">
                        <span class="info-label">Selected Package</span>
                        <span id="summary-package-name" class="info-value" style="font-size: 12.5px;"><?php echo htmlspecialchars($default_title); ?></span>
                    </div>

                    <div class="summary-row">
                        <span class="info-label">Total Amount</span>
                        <span id="summary-total-price" class="info-value price">₱<?php echo number_format($default_price, 2); ?></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-info-note">
                        📌 <strong>Note:</strong> You will be redirected to the PayPal checkout page to pay the 50% downpayment upon submitting this form.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        const packageRadios = document.querySelectorAll('input[name="package_type"]');
        const summaryPackageName = document.getElementById('summary-package-name');
        const summaryTotalPrice = document.getElementById('summary-total-price');
        const blockedSchedules = <?php echo json_encode($blocked_schedules); ?>;

        const dateInput = document.getElementById('reservation_date');
        const shiftInput = document.getElementById('shift');
        const warningDiv = document.getElementById('client-date-warning');
        const submitBtn = document.getElementById('btn-submit-booking');

        function validateBlockedDate() {
            const selectedDate = dateInput.value;
            const selectedShift = shiftInput.value;
            
            if (!selectedDate) return;

            const match = blockedSchedules.find(item => {
                return item.blocked_date === selectedDate && (item.time_slot === 'All Shifts' || item.time_slot === selectedShift);
            });

            if (match) {
                warningDiv.style.display = 'block';
                warningDiv.innerHTML = `⚠️ <strong>Date Unavailable:</strong> The resort is closed on ${selectedDate} (${selectedShift}). Reason: ${match.reason}`;
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                warningDiv.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }

        dateInput.addEventListener('change', validateBlockedDate);
        shiftInput.addEventListener('change', validateBlockedDate);

        packageRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const price = e.target.getAttribute('data-price');
                summaryPackageName.textContent = e.target.value;
                summaryTotalPrice.textContent = '₱' + parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            });
        });
    </script>

    <!-- FOOTER -->
    <footer style="margin-top: 60px; padding: 24px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 13px; color: #666; background-color: #fff;">
        <p>&copy; <?php echo date('Y'); ?> La Corteza Pool & Venue. All rights reserved.</p>
    </footer>

</body>
</html>