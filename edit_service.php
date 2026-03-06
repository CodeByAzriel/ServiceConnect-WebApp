<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch service
$stmt = $conn->prepare("SELECT * FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$service){
    die("Service not found.");
}

// Only owner or admin can edit
if($service['user_id'] != $user_id && $_SESSION['role'] !== 'admin'){
    die("Unauthorized access.");
}

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE services SET title=?, description=?, price=?, status=? WHERE id=?");
    $stmt->bind_param("ssdsi", $title, $description, $price, $status, $service_id);
    $stmt->execute();
    $stmt->close();

    $message = "Service updated successfully.";
}
?>

<?php include 'header.php'; ?>

<div class="container profile-page">
    <h2>Edit Service</h2>

    <?php if($message) echo "<p class='success'>$message</p>"; ?>

    <form method="POST" class="form-container">

        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($service['title']); ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($service['description']); ?></textarea>

        <label>Price (R)</label>
        <input type="number" step="0.01" name="price" value="<?= $service['price']; ?>" required>

        <label>Status</label>
        <select name="status">
            <option value="active" <?= $service['status']=='active'?'selected':''; ?>>Active</option>
            <option value="inactive" <?= $service['status']=='inactive'?'selected':''; ?>>Inactive</option>
        </select>

        <button type="submit" class="btn primary">Update Service</button>

    </form>
</div>

<?php include 'footer.php'; ?>