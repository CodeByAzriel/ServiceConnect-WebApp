<?php

include 'db.php'; // DB connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch categories
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch popular services
$popularServices = [];
$servicesResult = $conn->query("
    SELECT s.id, s.title, s.description, s.price, u.name AS provider_name
    FROM services s
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'active' AND u.role = 'user'
    ORDER BY s.created_at DESC
    LIMIT 6
");

if ($servicesResult) {
    while ($row = $servicesResult->fetch_assoc()) {
        $popularServices[] = $row;
    }
}


// Fetch top professionals (users who provide services)
$topPros = [];
$prosResult = $conn->query("
    SELECT u.id, u.name, AVG(r.rating) AS avg_rating
    FROM users u
    JOIN services s ON s.user_id = u.id
    LEFT JOIN reviews r ON r.service_id = s.id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY avg_rating DESC
    LIMIT 6
");

if ($prosResult) {
    while ($row = $prosResult->fetch_assoc()) {
        $topPros[] = $row;
    }
}
if ($prosResult) {
    while ($row = $prosResult->fetch_assoc()) {
        $topPros[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceConnect - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-text">
        <h1>Find. Book. Expert Help. Locally.</h1>
        <p>Connect with verified professionals in plumbing, tech, home repair, tutoring, and more. Fast, reliable, and secure.</p>

        <!-- Search Form -->
        <form class="search-form" method="GET" action="services.php">
            <input type="text" name="query" placeholder="What service do you need? (e.g., Plumber, Web Design)">
            <button type="submit" class="btn primary">Find Pros</button>
        </form>

        <!-- Categories -->
        <div class="categories">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <a class="category-btn" href="services.php?category=<?= urlencode($cat['name']); ?>">
                        <?= htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No categories available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="feature-card">
        <div class="icon">🔧</div>
        <h3>Vetted Experts</h3>
        <p>All professionals are verified for quality and reliability.</p>
    </div>
    <div class="feature-card">
        <div class="icon">💳</div>
        <h3>Secured Payments</h3>
        <p>Pay safely and securely at the time of service.</p>
    </div>
    <div class="feature-card">
        <div class="icon">📍</div>
        <h3>Local Focus</h3>
        <p>Connect with professionals near you quickly.</p>
    </div>
</section>

<!-- Popular Services -->
<section class="services">
    <h2>Popular Services</h2>
    <div class="cards-container">
        <?php if (!empty($popularServices)): ?>
            <?php foreach ($popularServices as $service): ?>
                <div class="service-card">
                    <h3><?= htmlspecialchars($service['title']); ?></h3>
                    <p><?= htmlspecialchars($service['description']); ?></p>
                    <p><strong>Price:</strong> R<?= number_format($service['price'], 2); ?></p>
                    <p><strong>Provider:</strong> <?= htmlspecialchars($service['provider_name']); ?></p>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="book.php">
                            <input type="hidden" name="service_id" value="<?= $service['id']; ?>">
                            <button type="submit" name="book_service" class="btn primary">Book Now (Pay at Service)</button>
                        </form>
                    <?php else: ?>
                        <p>
    <a href="login.php" class="btn login-btn">Login to Book</a>
</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No services available right now.</p>
        <?php endif; ?>
    </div>
</section>



<?php include 'footer.php'; ?>

</body>
</html>