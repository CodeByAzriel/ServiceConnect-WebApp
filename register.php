<?php
// register.php - User Registration
session_start();
include 'header.php';
include 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $location_id = $_POST['location_id']; // dropdown selection

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, location_id, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $location_id);

        if ($stmt->execute()) {
            $stmt->close();
            $success = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $error = "Error: Email might already be in use.";
        }
    }
}

// Fetch locations for dropdown
$locations_result = $conn->query("SELECT id, name FROM locations ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceConnect - Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-container">
    <h2>Register</h2>

    <?php if($error != ''): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if($success != ''): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <select name="location_id" required>
            <option value="">Select Your City</option>
            <?php while($row = $locations_result->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
    <p><a href="index.php">Back to Home</a></p>
</div>

<?php include 'footer.php'; ?>
</body>
</html>