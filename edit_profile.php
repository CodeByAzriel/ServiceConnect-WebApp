<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

$stmt = $conn->prepare("SELECT name,email,location FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $location = $_POST['location'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, location=? WHERE id=?");
    $stmt->bind_param("sssi",$name,$email,$location,$user_id);
    if($stmt->execute()){
        $message = "Profile updated successfully!";
        $_SESSION['name'] = $name; // update session if needed
    } else {
        $error = "Update failed. Try again.";
    }
    $stmt->close();
}
?>

<?php include 'header.php'; ?>
<div class="container profile-page">
    <h2>Edit Profile</h2>
    <?php if($message != ''): ?><p class="success"><?= $message; ?></p><?php endif; ?>
    <?php if($error != ''): ?><p class="error"><?= $error; ?></p><?php endif; ?>

    <form method="POST" class="form-container">
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        <input type="text" name="location" value="<?= htmlspecialchars($user['location']); ?>" required>
        <button type="submit" class="btn primary">Update Profile</button>
    </form>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
<?php include 'footer.php'; ?>