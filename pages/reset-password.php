<?php
// Set standardized timezone first
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/connect.php';

// Obtain token from query parameter or post body
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;
$row = null;

if (!empty($token)) {
    // Fetch token row using MySQL NOW() comparison directly
    $stmt = mysqli_prepare($connect2db, "
        SELECT email
        FROM password_resets
        WHERE token = ? AND expires_at > NOW()
        LIMIT 1
    ");

    if (!$stmt) {
        die('Database query error: ' . mysqli_error($connect2db));
    }

    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

// Missing, invalid or expired token: the page shows a "link expired" panel instead of the form
$invalid = !$row;

// Handle Form Submission (Setting New Password)
if (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST' && strlen($_POST['password'] ?? '') < 6) {
    $error = 'Password must be at least 6 characters.';
} elseif (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $email   = $row['email'];

    // 1. Hash the new password securely
    $hash = password_hash($newPass, PASSWORD_DEFAULT);

    // 2. Update user password in accounts table. Opening the emailed link proves they own the inbox, so this also verifies the email.
    $updateStmt = mysqli_prepare($connect2db, "UPDATE accounts SET password = ?, email_verified_at = COALESCE(email_verified_at, NOW()), verification_token = NULL WHERE email = ?");
    mysqli_stmt_bind_param($updateStmt, 'ss', $hash, $email);

    if (mysqli_stmt_execute($updateStmt)) {
        // 3. Delete used token(s) from password_resets table
        $deleteStmt = mysqli_prepare($connect2db, "DELETE FROM password_resets WHERE email = ?");
        mysqli_stmt_bind_param($deleteStmt, 's', $email);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);

        $success = true;
    } else {
        $error = 'Failed to update password: ' . mysqli_error($connect2db);
    }
    mysqli_stmt_close($updateStmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body class="auth-page">
    <main class="auth-split">
        <aside class="auth-split-photo">
            <a href="dashboard.php" class="auth-back">&larr; Back to home</a>
            <div class="auth-photo-caption">
                <span class="auth-eyebrow">La Corteza Pool &amp; Venue</span>
                <h2>Almost there. Pick a new password.</h2>
            </div>
        </aside>

        <section class="auth-split-form">
            <img src="../css/images/logo.png" alt="La Corteza Logo" class="auth-logo">

            <?php if ($success): ?>
                <div class="auth-status">
                    <div class="auth-status-icon">&#10003;</div>
                    <h1>Password updated</h1>
                    <p>Your password has been changed. You can now log in with your new password.</p>
                    <a href="login.php" class="auth-submit">Log in</a>
                </div>
            <?php elseif ($invalid): ?>
                <div class="auth-status">
                    <div class="auth-status-icon is-error">!</div>
                    <h1>Link expired</h1>
                    <p>This reset link is invalid or has expired. Reset links only work for 1 hour. Request a new one to continue.</p>
                    <a href="forgot-password.php" class="auth-submit">Request a new link</a>
                </div>
            <?php else: ?>
                <h1>Set a new password</h1>
                <p class="auth-subtitle">Choose a password with at least 6 characters.</p>

                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="reset-password.php?token=<?php echo urlencode($token); ?>" method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="auth-field">
                        <label for="password">New password</label>
                        <div class="auth-password">
                            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="6" autofocus>
                            <button type="button" class="auth-toggle" aria-label="Show password" onclick="togglePassword(this)">Show</button>
                        </div>
                    </div>
                    <button type="submit" class="auth-submit">Update password</button>
                </form>
            <?php endif; ?>

            <p class="auth-switch">
                <a href="login.php">Back to log in</a>
            </p>
        </section>
    </main>

    <script>
        function togglePassword(btn) {
            const input = btn.parentElement.querySelector('input');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        }
    </script>
</body>
</html>