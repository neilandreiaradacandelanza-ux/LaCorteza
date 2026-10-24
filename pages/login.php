<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../db/connect.php';

    $error = '';
    $success = '';
    $unverified_email = '';

    if (isset($_SESSION['success_msg'])) {
        $success = $_SESSION['success_msg'];
        unset($_SESSION['success_msg']);
    }

    // Page to return to after login (only local pages like "book-now.php?package=...")
    $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
    if (!preg_match('/^[a-z-]+\.php(\?[\w%=&.+-]*)?$/i', $redirect)) {
        $redirect = '';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login_input = trim($_POST['login_input'] ?? '');
        $password    = $_POST['password'] ?? '';

        if (empty($login_input) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            // Select role along with account details
            $stmt = mysqli_prepare($connect2db, "SELECT id, firstname, lastname, email, password, role, email_verified_at FROM accounts WHERE username = ? OR email = ? ORDER BY id LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                $userRole = strtolower($user['role'] ?? 'customer');

                // Customers must click the link in their verification email before they can log in (admins are exempt)
                if (password_verify($password, $user['password']) && $userRole !== 'admin' && empty($user['email_verified_at'])) {
                    $unverified_email = $user['email'];
                    $error = "Please verify your email before logging in. Check your inbox for the link we sent.";
                } elseif (password_verify($password, $user['password'])) {

                    // Fresh session ID on login, and drop any leftover session from a previous account
                    session_regenerate_id(true);
                    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_name'],
                          $_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['user']);

                    if ($userRole === 'admin') {
                        // Set Admin Session Variables
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?: 'Admin';
                        
                        // Redirect to Admin Dashboard
                        header("Location: ../admin/dashboard.php");
                        exit();
                    } else {
                        // Set Regular Customer Session Variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['full_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'firstname' => $user['firstname'],
                            'lastname' => $user['lastname'],
                            'email' => $user['email']
                        ];

                        // Redirect back to the page that required login, or to Customer Dashboard / Home
                        header("Location: " . ($redirect ?: "dashboard.php"));
                        exit();
                    }
                }
            }

            if (empty($error)) {
                $error = "Invalid username or password.";
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login - La Corteza</title>
        <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
        <link rel="icon" type="image/png" href="../css/images/logo.png">
    </head>
    <body class="auth-page">
        <main class="auth-split">
            <!-- Photo side: shows the venue instead of an empty card -->
            <aside class="auth-split-photo">
                <a href="dashboard.php" class="auth-back">&larr; Back to home</a>
                <div class="auth-photo-caption">
                    <span class="auth-eyebrow">La Corteza Pool &amp; Venue</span>
                    <h2>Your day by the water starts here.</h2>
                </div>
            </aside>

            <section class="auth-split-form">
                <img src="../css/images/logo.png" alt="La Corteza Logo" class="auth-logo">
                <h1>Welcome back</h1>
                <p class="auth-subtitle">Log in to book and manage your reservations.</p>

                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="error">
                        <?php echo htmlspecialchars($error); ?>
                        <?php if ($unverified_email): ?>
                            <a href="verify-email.php?email=<?php echo urlencode($unverified_email); ?>">Resend the verification email</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="auth-form">
                    <div class="auth-field">
                        <label for="login_input">Username or email</label>
                        <input type="text" id="login_input" name="login_input" value="<?php echo htmlspecialchars($_POST['login_input'] ?? ''); ?>" autocomplete="username" required autofocus>
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-password">
                            <input type="password" id="password" name="password" autocomplete="current-password" required>
                            <button type="button" class="auth-toggle" aria-label="Show password" onclick="togglePassword(this)">Show</button>
                        </div>
                        <a href="forgot-password.php" class="auth-forgot">Forgot password?</a>
                    </div>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                    <button type="submit" class="auth-submit">Log in</button>
                </form>

                <p class="auth-switch">
                    New to La Corteza?
                    <a href="register.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>">Create an account</a>
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