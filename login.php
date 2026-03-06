<?php
session_start();
include 'db.php';

// Prevent accidental output before redirect
ob_start();

$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if($user['role'] === 'admin'){
            header("Location: admin_dashboard.php");
            exit;
        } else {
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceConnect - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="form-container">
    <h2>Login</h2>
    <?php if($error != ''): ?>
        <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn primary">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
    <p><a href="index.php">Back to Home</a></p>
</div>

<?php include 'footer.php'; ?>

</body>
</html>