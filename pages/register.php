<?php
require_once __DIR__ . '/../db/connect.php';

$error = '';
$success = '';

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
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email or username already exists
        $stmt = mysqli_prepare($connect2db, "SELECT id FROM accounts WHERE email = ? OR username = ?");
        mysqli_stmt_bind_param($stmt, "ss", $email, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Email or Username already exists.";
        } else {
            // Hash password and insert user into accounts table
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare($connect2db, "INSERT INTO accounts (firstname, lastname, email, username, password) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "sssss", $firstname, $lastname, $email, $username, $hashed_password);

            if (mysqli_stmt_execute($insert_stmt)) {
                $_SESSION['success_msg'] = "Registration successful! Please log in.";
                header("Location: login.php");
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
    <title>Register</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-card">
        <h1>Create Account</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
            <input type="text" name="firstname" placeholder="First Name" required>
            <input type="text" name="lastname" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <button type="submit">Register</button>
        </form>

        <div class="links single">
            <a href="login.php">Already have an account? Login here</a>
        </div>
    </div>
</body>
</html>