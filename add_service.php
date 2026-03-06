<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch categories from categories table
$categoryQuery = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryQuery->fetch_all(MYSQLI_ASSOC);

if(isset($_POST['add_service'])){

    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $skill_level = $_POST['skill_level'];
    $category = $_POST['category'];

    // INSERT using prepared statement
    $stmt = $conn->prepare("INSERT INTO services (title, description, category, price, skill_level, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->bind_param("sssdis", $title, $description, $category, $price, $skill_level, $user_id);

    if($stmt->execute()){
        $message = "Service added successfully!";
    } else {
        $message = "Error adding service: " . $stmt->error;
    }

    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<div class="container profile-page">

<h2>Add New Service</h2>

<?php if($message != ""): ?>
<p class="success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" class="form-container">

    <input type="text" name="title" placeholder="Service Title" required>

    <textarea name="description" placeholder="Description" required></textarea>

    <input type="number" step="0.01" name="price" placeholder="Price" required>

    <select name="skill_level" required>
        <option value="">Select Skill Level</option>
        <option value="Beginner">Beginner</option>
        <option value="Intermediate">Intermediate</option>
        <option value="Advanced">Advanced</option>
    </select>

    <select name="category" required>
        <option value="">Select Category</option>
        <?php foreach($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['name']); ?>">
                <?= htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="add_service" class="btn primary">Add Service</button>

</form>

<p><a href="dashboard.php">Back to Dashboard</a></p>

</div>

<?php include 'footer.php'; ?>