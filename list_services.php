<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all services of the logged-in user
$stmt = $conn->prepare("SELECT id, title, description, price, skill_level, status FROM services WHERE provider_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container profile-page">
    <h2>My Services</h2>

    <p><a href="add_service.php" class="btn primary">Add New Service</a></p>

    <?php if(!empty($services)): ?>
        <table class="services-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Skill Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['title']); ?></td>
                        <td><?= htmlspecialchars($service['description']); ?></td>
                        <td>$<?= number_format($service['price'], 2); ?></td>
                        <td><?= htmlspecialchars($service['skill_level']); ?></td>
                        <td><?= htmlspecialchars($service['status']); ?></td>
                        <td>
                            <a href="edit_service.php?id=<?= $service['id']; ?>" class="btn">Edit</a>
                            <a href="delete_service.php?id=<?= $service['id']; ?>" class="btn danger" onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have not added any services yet.</p>
    <?php endif; ?>
</div>