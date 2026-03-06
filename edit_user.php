<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$user){
    die("User not found.");
}

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $role, $user_id);
    $stmt->execute();
    $stmt->close();

    $message = "User updated successfully.";
}
?>

<?php include 'header.php'; ?>
<div class="container profile-page">
    <h2>Edit User</h2>
    <?php if($message) echo "<p class='success'>$message</p>"; ?>
    <form method="POST" class="form-container">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

        <label>Role</label>
        <select name="role" required>
            <option value="user" <?= $user['role']=='user'?'selected':''; ?>>User</option>
            <option value="admin" <?= $user['role']=='admin'?'selected':''; ?>>Admin</option>
        </select>

        <button type="submit" class="btn primary">Update User</button>
    </form>
</div>
<?php include 'footer.php'; ?>