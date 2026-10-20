    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../db/connect.php';

    $error = '';
    $success = '';

    if (isset($_SESSION['success_msg'])) {
        $success = $_SESSION['success_msg'];
        unset($_SESSION['success_msg']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login_input = trim($_POST['login_input'] ?? '');
        $password    = $_POST['password'] ?? '';

        if (empty($login_input) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            // Select role along with account details
            $stmt = mysqli_prepare($connect2db, "SELECT id, firstname, lastname, email, password, role FROM accounts WHERE username = ? OR email = ? ORDER BY id LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password'])) {
                    
                    $userRole = strtolower($user['role'] ?? 'customer');

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

                        // Redirect to Customer Dashboard / Home
                        header("Location: dashboard.php");
                        exit();
                    }
                }
            }
            
            $error = "Invalid username or password.";
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login - La Corteza</title>
        <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="login-card">
            <img src="../css/images/logo.png" alt="La Corteza Logo" class="brand-logo-img" style="margin: 0 auto;">
            <h1>Welcome Back</h1>

            <?php if (!empty($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="text" name="login_input" placeholder="Username or Email" required>
                <input type="password" name="password" placeholder="Password" required>

                <button type="submit">Login</button>
            </form>

            <div class="links">
                <a href="forgot-password.php">Forgot Password?</a>
                <a href="register.php">Register here</a>
            </div>
        </div>
    </body>
    </html>