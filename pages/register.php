<?php
require_once __DIR__ . '/../db/connect.php';
require_once __DIR__ . '/../functions/mail_functions.php';

$error = '';
$success = '';
$unverified = false;

// Carry the "return to" page through registration so login can send the user back (e.g. to book-now.php)
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if (!preg_match('/^[a-z-]+\.php(\?[\w%=&.+-]*)?$/i', $redirect)) {
    $redirect = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname        = trim($_POST['firstname'] ?? '');
    $lastname         = trim($_POST['lastname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email or username already exists
        $stmt = mysqli_prepare($connect2db, "SELECT email, email_verified_at FROM accounts WHERE email = ? OR username = ?");
        mysqli_stmt_bind_param($stmt, "ss", $email, $username);
        mysqli_stmt_execute($stmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($existing && strcasecmp($existing['email'], $email) === 0 && empty($existing['email_verified_at'])) {
            // Registered before but never clicked the link: point them to a fresh verification email
            $unverified = true;
            $error = "This email is already registered but not verified yet.";
        } elseif ($existing) {
            $error = "Email or Username already exists.";
        } else {
            // Hash password and insert user into accounts table (unverified until they click the emailed link)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare($connect2db, "INSERT INTO accounts (firstname, lastname, email, username, password, confirm_password, email_verified_at) VALUES (?, ?, ?, ?, ?, '', NULL)");
            mysqli_stmt_bind_param($insert_stmt, "sssss", $firstname, $lastname, $email, $username, $hashed_password);

            if (mysqli_stmt_execute($insert_stmt)) {
                $sent = send_verification_email($connect2db, mysqli_insert_id($connect2db), $email, $firstname, $redirect);
                header("Location: verify-email.php?" . ($sent ? 'sent=1' : 'failed=1') . '&email=' . urlencode($email));
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - La Corteza</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../css/images/logo.png">
</head>
<body class="auth-page">
    <main class="auth-split auth-compact">
        <aside class="auth-split-photo photo-venue">
            <a href="dashboard.php" class="auth-back">&larr; Back to home</a>
            <div class="auth-photo-caption">
                <span class="auth-eyebrow">La Corteza Pool &amp; Venue</span>
                <h2>Birthdays, reunions, team buildings. Plan yours here.</h2>
            </div>
        </aside>

        <section class="auth-split-form">
            <img src="../css/images/logo.png" alt="La Corteza Logo" class="auth-logo">
            <h1>Create an account</h1>
            <p class="auth-subtitle">Sign up to book a date and track your reservations.</p>

            <?php if (!empty($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if ($unverified): ?>
                        <a href="verify-email.php?email=<?php echo urlencode($_POST['email'] ?? ''); ?>">Resend the verification email</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php $old = function ($field) { return htmlspecialchars($_POST[$field] ?? ''); }; ?>
            <form action="register.php" method="POST" class="auth-form">
                <div class="auth-form-row">
                    <div class="auth-field">
                        <label for="firstname">First name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo $old('firstname'); ?>" autocomplete="given-name" required autofocus>
                    </div>
                    <div class="auth-field">
                        <label for="lastname">Last name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo $old('lastname'); ?>" autocomplete="family-name" required>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $old('email'); ?>" autocomplete="email" required>
                </div>

                <div class="auth-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo $old('username'); ?>" autocomplete="username" required>
                </div>

                <div class="auth-form-row">
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-password">
                            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="6">
                            <button type="button" class="auth-toggle" aria-label="Show password" onclick="togglePassword(this)">Show</button>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="confirm_password">Confirm password</label>
                        <div class="auth-password">
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required minlength="6">
                            <button type="button" class="auth-toggle" aria-label="Show password" onclick="togglePassword(this)">Show</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                <button type="submit" class="auth-submit">Create account</button>
            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="login.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>">Log in</a>
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