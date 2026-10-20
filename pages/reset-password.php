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
$success = '';

if (empty($token)) {
    die('Invalid access: No reset token provided.');
}

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

// Reject if missing or expired
if (!$row) {
    die('This reset link is invalid or expired.');
}

// Handle Form Submission (Setting New Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $newPass = $_POST['password'];
    $email   = $row['email'];

    // 1. Hash the new password securely
    $hash = password_hash($newPass, PASSWORD_DEFAULT);

    // 2. Update user password in accounts table
    $updateStmt = mysqli_prepare($connect2db, "UPDATE accounts SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($updateStmt, 'ss', $hash, $email);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // 3. Delete used token(s) from password_resets table
        $deleteStmt = mysqli_prepare($connect2db, "DELETE FROM password_resets WHERE email = ?");
        mysqli_stmt_bind_param($deleteStmt, 's', $email);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);

        $success = 'Password successfully updated! <a href="login.php" style="color: var(--color-palm-green); font-weight: bold;">Click here to Login</a>';
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
<body>
    <div class="login-card">
        <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img" style="margin: 0 auto;">
        <h1>Set New Password</h1>

        <?php if (!empty($success)): ?>
            <p style="color: green; text-align: center; margin-bottom: 16px;"><?php echo $success; ?></p>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="error" style="color: red; margin-bottom: 12px; text-align: center;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="reset-password.php?token=<?php echo urlencode($token); ?>" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="password" placeholder="Enter new password" required minlength="6">
                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>