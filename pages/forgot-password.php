<?php
// Set standardized timezone first
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/connect.php';
require_once __DIR__ . '/../functions/mail_functions.php';

$sent = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // 1. Check if email exists in accounts table
    $stmt = mysqli_prepare($connect2db, "SELECT id FROM accounts WHERE email = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            // 2. Clear only expired reset requests, so links in earlier (still valid) emails keep working
            $clearStmt = mysqli_prepare($connect2db, "DELETE FROM password_resets WHERE email = ? AND expires_at <= NOW()");
            mysqli_stmt_bind_param($clearStmt, 's', $email);
            mysqli_stmt_execute($clearStmt);
            mysqli_stmt_close($clearStmt);

            // 3. Generate secure token
            $token = bin2hex(random_bytes(32));

            // 4. Save into password_resets using MySQL DATE_ADD for accurate database time
            $insertStmt = mysqli_prepare($connect2db, "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            mysqli_stmt_bind_param($insertStmt, 'ss', $email, $token);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);

            // 5. Build the reset link from this site's own address (APP_URL, or the current request when unset)
            $link = app_url() . "/pages/reset-password.php?token=" . urlencode($token);

            // 6. Send Email via PHPMailer
            send_mail($email, 'Password Reset Request - La Corteza', email_template(
                'Password Reset Request',
                'We received a request to reset the password for your La Corteza account. Click the button below to choose a new password (link expires in 1 hour):',
                'Reset Password',
                $link,
                'If you did not make this request, you can safely ignore this email.'
            ));
        }
        mysqli_stmt_close($stmt);
    }

    // Redirect after POST so a page refresh doesn't resend the form and issue another token
    header('Location: forgot-password.php?sent=1');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body class="auth-page">
    <main class="auth-split">
        <aside class="auth-split-photo">
            <a href="dashboard.php" class="auth-back">&larr; Back to home</a>
            <div class="auth-photo-caption">
                <span class="auth-eyebrow">La Corteza Pool &amp; Venue</span>
                <h2>Locked out? We'll get you back in.</h2>
            </div>
        </aside>

        <section class="auth-split-form">
            <img src="../css/images/logo.png" alt="La Corteza Logo" class="auth-logo">

            <?php if ($sent): ?>
                <!-- Generic response for security: never reveal whether the email has an account -->
                <div class="auth-status">
                    <div class="auth-status-icon">&#9993;</div>
                    <h1>Check your email</h1>
                    <p>If that email belongs to an account, we've sent a link to reset your password. The link expires in 1 hour.</p>
                    <a href="login.php" class="auth-submit">Back to log in</a>
                </div>
                <p class="auth-switch">
                    Didn't get it?
                    <a href="forgot-password.php">Send another link</a>
                </p>
            <?php else: ?>
                <h1>Forgot password?</h1>
                <p class="auth-subtitle">Enter your account email and we'll send you a link to reset it.</p>

                <form action="forgot-password.php" method="POST" class="auth-form">
                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" autocomplete="email" required autofocus>
                    </div>
                    <button type="submit" class="auth-submit">Send reset link</button>
                </form>

                <p class="auth-switch">
                    Remembered it?
                    <a href="login.php">Back to log in</a>
                </p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>