<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends an HTML email through Gmail SMTP using MAIL_USERNAME / MAIL_PASSWORD (a Google App Password).
// Returns true when the email was handed to the server, false on failure (details go to the PHP error log).
function send_mail($to, $subject, $html)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = env('MAIL_USERNAME');
        $mail->Password   = env('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(env('MAIL_USERNAME'), 'La Corteza Resort');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

// Shared layout for La Corteza emails: a heading, a short message and one button
function email_template($heading, $message, $buttonText, $link, $footnote)
{
    $link = htmlspecialchars($link);
    return "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #2c3e2e; max-width: 520px;'>
            <h2 style='color: #3b5212;'>{$heading}</h2>
            <p>{$message}</p>
            <p style='margin: 24px 0;'>
                <a href='{$link}' style='background-color: #88b035; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;'>{$buttonText}</a>
            </p>
            <p style='font-size: 12px; color: #666;'>If the button doesn't work, copy this link into your browser:<br>{$link}</p>
            <p style='font-size: 12px; color: #666;'>{$footnote}</p>
        </div>
    ";
}

// Creates a fresh verification token for an unverified account and emails the link. Returns true if sent.
function send_verification_email($connect2db, $accountId, $email, $firstname, $redirect = '')
{
    $token = bin2hex(random_bytes(32));

    $stmt = mysqli_prepare($connect2db, "UPDATE accounts SET verification_token = ?, verification_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ? AND email_verified_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'si', $token, $accountId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $link = app_url() . '/pages/verify-email.php?token=' . urlencode($token) . ($redirect ? '&redirect=' . urlencode($redirect) : '');

    $html = email_template(
        'Verify your email',
        'Hi ' . htmlspecialchars($firstname) . ', thanks for signing up with La Corteza Pool &amp; Venue. Please confirm your email address to activate your account (link expires in 24 hours):',
        'Verify Email',
        $link,
        "If you didn't create an account, you can safely ignore this email."
    );

    return send_mail($email, 'Verify your email - La Corteza', $html);
}
