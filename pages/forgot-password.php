<?php
// Set standardized timezone first
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

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

            // 5. Build accurate reset link
            $appUrl = rtrim(env('APP_URL', "http://localhost/lacorteza"), '/');
            $link = $appUrl . "/pages/reset-password.php?token=" . urlencode($token);

            // 6. Send Email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = env('MAIL_USERNAME');
                $mail->Password   = env('MAIL_PASSWORD'); // Google App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom(env('MAIL_USERNAME'), 'La Corteza Resort');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request - La Corteza';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; color: #2c3e2e;'>
                        <h2>Password Reset Request</h2>
                        <p>We received a request to reset your password for your La Corteza account.</p>
                        <p>Click the button below to update your password (link expires in 1 hour):</p>
                        <p style='margin: 20px 0;'>
                            <a href='{$link}' style='background-color: #556b2f; color: #ffffff; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                        </p>
                        <p style='font-size: 12px; color: #666;'>If you did not make this request, you can safely ignore this email.</p>
                    </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer error: ' . $mail->ErrorInfo);
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Redirect after POST so a page refresh doesn't resend the form and issue another token
    header('Location: forgot-password.php?sent=1');
    exit;
}

if (isset($_GET['sent'])) {
    // Generic response message for security
    $message = '<p style="color: green; text-align: center; margin-bottom: 12px;">If that email exists, a reset link has been sent to your inbox.</p>';
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
<body>
    <div class="login-card">
        <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img" style="margin: 0 auto;">
        <h1>Reset Password</h1>
        <p class="subtitle" style="text-align: center; margin-bottom: 16px;">Enter your account email below to request a reset link.</p>

        <?php if (!empty($message)) echo $message; ?>

        <form action="forgot-password.php" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit">Submit Request</button>
        </form>

        <div class="links single" style="margin-top: 16px; text-align: center;">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>