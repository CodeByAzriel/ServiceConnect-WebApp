<?php
session_start();
include 'db.php';
include 'header.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch data
$users = $conn->query("SELECT id, name, email, role, profile_pic FROM users ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$services = $conn->query("
    SELECT s.id, s.title, s.price, s.status, u.name AS provider, u.profile_pic
    FROM services s
    JOIN users u ON u.id = s.provider_id
    ORDER BY s.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="dashboard-container">
    <h2>Admin Dashboard</h2>
    <div class="tabs">
        <button class="tablink" onclick="openTab(event,'users')">Users</button>
        <button class="tablink" onclick="openTab(event,'categories')">Categories</button>
        <button class="tablink" onclick="openTab(event,'services')">Services</button>
    </div>

    <!-- Users Tab -->
    <div id="users" class="tabcontent">
        <h3>All Users</h3>
        <table class="dashboard-table">
            <tr><th>Profile</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td><img src="<?= htmlspecialchars($u['profile_pic'] ?? 'images/default.png'); ?>" class="dashboard-pic" alt="Profile"></td>
                <td><?= htmlspecialchars($u['name']); ?></td>
                <td><?= htmlspecialchars($u['email']); ?></td>
                <td><?= htmlspecialchars($u['role']); ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $u['id']; ?>" class="btn secondary">Edit</a>
                    <a href="delete_user.php?id=<?= $u['id']; ?>" class="btn danger" onclick="return confirm('Delete this user?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Categories Tab -->
    <div id="categories" class="tabcontent">
        <h3>All Categories</h3>
        <table class="dashboard-table">
            <tr><th>Name</th><th>Actions</th></tr>
            <?php foreach($categories as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['name']); ?></td>
                <td>
                    <a href="edit_category.php?id=<?= $c['id']; ?>" class="btn secondary">Edit</a>
                    <a href="delete_category.php?id=<?= $c['id']; ?>" class="btn danger" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="add_category.php" class="btn primary">Add New Category</a>
    </div>

    <!-- Services Tab -->
    <div id="services" class="tabcontent">
        <h3>All Services</h3>
        <table class="dashboard-table">
            <tr><th>Provider</th><th>Profile</th><th>Title</th><th>Price (R)</th><th>Status</th><th>Actions</th></tr>
            <?php foreach($services as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['provider']); ?></td>
                <td><img src="uploads/<?= htmlspecialchars($s['profile_pic'] ?? 'default.png'); ?>" class="dashboard-pic" alt="Profile"></td>
                <td><?= htmlspecialchars($s['title']); ?></td>
                <td>R<?= number_format($s['price'],2); ?></td>
                <td><?= htmlspecialchars($s['status']); ?></td>
                <td>
                    <a href="edit_service.php?id=<?= $s['id']; ?>" class="btn secondary">Edit</a>
                    <a href="delete_service.php?id=<?= $s['id']; ?>" class="btn danger" onclick="return confirm('Delete this service?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="add_service.php" class="btn primary">Add New Service</a>
    </div>
</div>

<script>
function openTab(evt, tabName){
    document.querySelectorAll(".tabcontent").forEach(t=>t.style.display="none");
    document.querySelectorAll(".tablink").forEach(t=>t.classList.remove("active"));
    document.getElementById(tabName).style.display="block";
    evt.currentTarget.classList.add("active");
}
document.querySelector(".tablink").click();
</script>

<?php include 'footer.php'; ?>