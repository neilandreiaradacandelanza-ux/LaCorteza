<?php
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/connect.php';
require_once __DIR__ . '/../functions/mail_functions.php';

// Page to return to after logging in (carried through the emailed link, e.g. back to book-now.php)
$redirect = $_GET['redirect'] ?? '';
if (!preg_match('/^[a-z-]+\.php(\?[\w%=&.+-]*)?$/i', $redirect)) {
    $redirect = '';
}

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$state = 'resend'; // resend | sent | failed | verified | invalid

// Resend: issue a new link for an unverified account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($email)) {
    $stmt = mysqli_prepare($connect2db, "SELECT id, firstname FROM accounts WHERE email = ? AND email_verified_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $sent = true;
    if ($account) {
        $sent = send_verification_email($connect2db, $account['id'], $email, $account['firstname']);
    }

    // Same response whether or not the account exists, so this page can't be used to discover registered emails
    header('Location: verify-email.php?' . ($sent ? 'sent=1' : 'failed=1') . '&email=' . urlencode($email));
    exit;
}

if (!empty($_GET['token'])) {
    // Clicking the emailed link: the token must match an unverified account and be less than 24 hours old
    $stmt = mysqli_prepare($connect2db, "
        UPDATE accounts
        SET email_verified_at = NOW(), verification_token = NULL, verification_expires = NULL
        WHERE verification_token = ? AND verification_expires > NOW() AND email_verified_at IS NULL
    ");
    mysqli_stmt_bind_param($stmt, 's', $_GET['token']);
    mysqli_stmt_execute($stmt);
    $state = mysqli_stmt_affected_rows($stmt) === 1 ? 'verified' : 'invalid';
    mysqli_stmt_close($stmt);
} elseif (isset($_GET['sent'])) {
    $state = 'sent';
} elseif (isset($_GET['failed'])) {
    $state = 'failed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body class="auth-page">
    <main class="auth-split">
        <aside class="auth-split-photo photo-venue">
            <a href="dashboard.php" class="auth-back">&larr; Back to home</a>
            <div class="auth-photo-caption">
                <span class="auth-eyebrow">La Corteza Pool &amp; Venue</span>
                <h2>One quick step before your first booking.</h2>
            </div>
        </aside>

        <section class="auth-split-form">
            <img src="../css/images/logo.png" alt="La Corteza Logo" class="auth-logo">

            <?php if ($state === 'verified'): ?>
                <div class="auth-status">
                    <div class="auth-status-icon">&#10003;</div>
                    <h1>Email verified</h1>
                    <p>Your account is now active. Log in to start booking.</p>
                    <a href="login.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>" class="auth-submit">Log in</a>
                </div>
            <?php elseif ($state === 'sent'): ?>
                <div class="auth-status">
                    <div class="auth-status-icon">&#9993;</div>
                    <h1>Check your email</h1>
                    <p>
                        We sent a verification link<?php echo $email ? ' to <strong>' . htmlspecialchars($email) . '</strong>' : ''; ?>.
                        Click it to activate your account. The link expires in 24 hours.
                    </p>
                    <a href="login.php" class="auth-submit">Back to log in</a>
                </div>
            <?php else: ?>
                <?php if ($state === 'invalid'): ?>
                    <div class="auth-status">
                        <div class="auth-status-icon is-error">!</div>
                        <h1>Link expired</h1>
                        <p>This verification link is invalid, expired, or was already used. Enter your email to get a new one.</p>
                    </div>
                <?php else: ?>
                    <h1>Verify your email</h1>
                    <p class="auth-subtitle">Enter the email you signed up with and we'll send a new verification link.</p>
                <?php endif; ?>

                <?php if ($state === 'failed'): ?>
                    <div class="error">We couldn't send the verification email. Please try again in a moment.</div>
                <?php endif; ?>

                <form action="verify-email.php" method="POST" class="auth-form">
                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email" required>
                    </div>
                    <button type="submit" class="auth-submit">Send verification link</button>
                </form>
            <?php endif; ?>

            <p class="auth-switch">
                <?php if ($state === 'sent'): ?>
                    Didn't get it? Check your spam folder, or
                    <a href="verify-email.php?email=<?php echo urlencode($email); ?>">send another link</a>
                <?php else: ?>
                    <a href="login.php">Back to log in</a>
                <?php endif; ?>
            </p>
        </section>
    </main>
</body>
</html>
