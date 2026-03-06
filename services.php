<?php
session_start();
include 'db.php'; // DB connection

// Set default current location if not set
if(!isset($_SESSION['current_location'])){
    $_SESSION['current_location'] = $_SESSION['home_location'] ?? null;
}

// Handle location change via dropdown
if(isset($_GET['location']) && !empty($_GET['location'])){
    $_SESSION['current_location'] = $_GET['location'];
}
$userLocation = $_SESSION['current_location'] ?? null;

// Sanitize inputs
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Fetch categories
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if($catResult){
    while($row = $catResult->fetch_assoc()){
        $categories[] = $row;
    }
}

// Fetch distinct locations for dropdown
$locations = [];
$locationResult = $conn->query("SELECT DISTINCT location FROM users WHERE location IS NOT NULL ORDER BY location ASC");
if($locationResult){
    while($row = $locationResult->fetch_assoc()){
        $locations[] = $row['location'];
    }
}

// Build base SQL
$sql = "SELECT s.id, s.title, s.description, s.price, s.skill_level, s.category,
               u.name AS provider_name, u.profile_pic, u.location
        FROM services s
        JOIN users u ON s.user_id = u.id
        WHERE s.status='active'";

$params = [];
$paramTypes = '';

// Location filter
if(!empty($userLocation)){
    $sql .= " AND u.location = ?";
    $paramTypes .= 's';
    $params[] = $userLocation;
}

// Category filter
if(!empty($categoryFilter)){
    $sql .= " AND s.category LIKE ?";
    $paramTypes .= 's';
    $params[] = "%$categoryFilter%";
}

// Search filter
if(!empty($searchQuery)){
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
    $paramTypes .= 'ss';
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY s.created_at DESC";

// Prepare statement
$stmt = $conn->prepare($sql);
if($stmt === false){
    die("Prepare failed: " . $conn->error);
}

// Bind params dynamically
if(count($params) > 0){
    $refs = [];
    foreach($params as $key => $value){
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $paramTypes);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$stmt->execute();
$serviceResult = $stmt->get_result();

$services = [];
while($row = $serviceResult->fetch_assoc()){
    // Get average rating
    $srvId = $row['id'];
    $ratingResult = $conn->query("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM reviews WHERE service_id=$srvId");
    $ratingData = $ratingResult->fetch_assoc();
    $row['avg_rating'] = $ratingData['avg_rating'] ?? 0;
    $row['total_reviews'] = $ratingData['total_reviews'] ?? 0;

    $services[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ServiceConnect - Services</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* ===========================
   Modern Services Page UI
=========================== */

/* Page layout */
body {
    font-family: 'Poppins', sans-serif;
    background: #f3f4f6;
    margin: 0;
    padding: 0;
    color: #1f2937;
}

.services-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Title */
.services-page h1 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 30px;
    color: #4f46e5;
}

/* Filters */
.search-form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
}

.search-form select,
.search-form input {
    padding: 12px 15px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
    outline: none;
    transition: 0.3s;
}

.search-form select:hover,
.search-form input:hover {
    border-color: #4f46e5;
}

.search-form button {
    background: #4f46e5;
    color: #fff;
    border: none;
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
}

.search-form button:hover {
    background: #3730a3;
}

/* Cards Grid */
.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
}

/* Individual Card */
.service-card {
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    padding: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

/* Provider Pic */
.provider-pic {
    display: flex;
    align-items: center;
    gap: 10px;
}

.provider-pic img {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #4f46e5;
}

/* Service Title */
.service-card h3 {
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0;
    color: #111827;
}

/* Details Text */
.service-card p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
    color: #374151;
}

/* Book Button */
.service-card .btn {
    margin-top: 10px;
    padding: 10px 20px;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 12px;
    cursor: pointer;
    border: none;
    transition: 0.3s;
}

.service-card .btn.primary {
    background: #4f46e5;
    color: #fff;
}

.service-card .btn.primary:hover {
    background: #3730a3;
}

/* Rating Stars */
.service-card .rating {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 600px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="services-page">
    <h1>Services in <?= htmlspecialchars($userLocation ?? 'All Locations'); ?></h1>

    <form method="GET" class="search-form">
        <select name="location">
            <option value="">-- Select Location --</option>
            <?php foreach($locations as $loc): ?>
                <option value="<?= htmlspecialchars($loc); ?>" <?= ($loc==$userLocation) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($loc); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="query" placeholder="Search services..." value="<?= htmlspecialchars($searchQuery); ?>">

        <select name="category">
            <option value="">All Categories</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['name']); ?>" <?= ($cat['name']==$categoryFilter) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn primary">Search</button>
    </form>

    <div class="cards-container">
        <?php if(!empty($services)): ?>
            <?php foreach($services as $service): ?>
                <div class="service-card">
                    <div class="provider-pic">
                        <?php if(!empty($service['profile_pic'])): ?>
                            <img src="<?= htmlspecialchars($service['profile_pic']); ?>" alt="<?= htmlspecialchars($service['provider_name']); ?>">
                        <?php else: ?>
                            <img src="https://randomuser.me/api/portraits/<?= rand(0,1) ? 'men' : 'women'; ?>/<?= rand(1,99); ?>.jpg" alt="<?= htmlspecialchars($service['provider_name']); ?>">
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($service['title']); ?></h3>
                    <p><?= htmlspecialchars($service['description']); ?></p>
                    <p><strong>Price:</strong> R<?= number_format($service['price'],2); ?></p>
                    <p><strong>Provider:</strong> <?= htmlspecialchars($service['provider_name']); ?> (Skill: <?= htmlspecialchars($service['skill_level']); ?>)</p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($service['category']); ?></p>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="book.php">
                            <input type="hidden" name="service_id" value="<?= $service['id']; ?>">
                            <button type="submit" name="book_service" class="btn primary">Book Now (Pay at Service)</button>
                        </form>
                  <?php else: ?>
    <a href="login.php" class="btn primary">Login to Book</a>
<?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No services found matching your search or location.</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>