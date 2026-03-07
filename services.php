<?php
session_start();
include 'db.php';

/* ======================
   HANDLE FILTER INPUTS
====================== */
$selectedLocation = $_GET['location'] ?? '';
$searchQuery = $_GET['query'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

/* ======================
   GET LOCATIONS
====================== */
$locations = [];
$locationResult = $conn->query("SELECT id, name FROM locations ORDER BY name ASC");
if($locationResult){
    while($row = $locationResult->fetch_assoc()){
        $locations[] = $row;
    }
}

/* ======================
   GET CATEGORIES
====================== */
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if($catResult){
    while($row = $catResult->fetch_assoc()){
        $categories[] = $row;
    }
}

/* ======================
   BUILD SERVICE QUERY
====================== */
$sql = "
SELECT
    s.id,
    s.title,
    s.description,
    s.price,
    s.skill_level,
    s.category,
    u.name AS provider_name,
    u.profile_pic,
    l.name AS location_name,
    l.id AS location_id
FROM services s
JOIN users u ON s.user_id = u.id
JOIN locations l ON u.location_id = l.id
WHERE s.status='active'
";

$params = [];
$types = "";

/* LOCATION FILTER */
if(!empty($selectedLocation)){
    $sql .= " AND l.id = ?";
    $types .= "i";
    $params[] = $selectedLocation;
}

/* CATEGORY FILTER */
if(!empty($categoryFilter)){
    $sql .= " AND s.category = ?";
    $types .= "s";
    $params[] = $categoryFilter;
}

/* SEARCH FILTER */
if(!empty($searchQuery)){
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
    $types .= "ss";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY s.created_at DESC";

/* ======================
   EXECUTE QUERY
====================== */
$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ======================
   FETCH SERVICES
====================== */
$services = [];
while($row = $result->fetch_assoc()){
    $srvId = $row['id'];
    $ratingResult = $conn->query("
        SELECT AVG(rating) AS avg_rating,
               COUNT(*) AS total_reviews
        FROM reviews
        WHERE service_id = $srvId
    ");
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
   Modern Services Page UI 2026
=========================== */
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f5f7;
    margin: 0;
    padding: 0;
    color: #1f2937;
}

.services-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.services-page h1 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
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
    border-radius: 15px;
    border: 1px solid #ddd;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}

.search-form select:hover,
.search-form input:hover {
    border-color: #4f46e5;
    box-shadow: 0 0 8px rgba(79,70,229,0.2);
}

.search-form button {
    background: #4f46e5;
    color: #fff;
    border: none;
    padding: 12px 25px;
    border-radius: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-form button:hover {
    background: #3730a3;
    box-shadow: 0 5px 15px rgba(55,48,163,0.2);
}

/* Cards Grid */
.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
}

/* Individual Card */
.service-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.07);
    padding: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.service-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
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
    border-radius: 15px;
    cursor: pointer;
    border: none;
    transition: all 0.3s ease;
}

.service-card .btn.primary {
    background: #4f46e5;
    color: #fff;
}

.service-card .btn.primary:hover {
    background: #3730a3;
    box-shadow: 0 6px 15px rgba(55,48,163,0.2);
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
<h1>Services in <?= htmlspecialchars($selectedLocation ?? 'All Locations'); ?></h1>

<?php if(isset($_SESSION['user_id'])): ?>
<div style="text-align:center; margin-bottom:20px;">
<a href="add_service.php" class="btn primary">+ Add Your Service</a>
</div>
<?php endif; ?>

<form method="GET" class="search-form">
<select name="location">
<option value="">All Locations</option>
<?php foreach($locations as $loc): ?>
<option value="<?= $loc['id']; ?>" <?= ($selectedLocation == $loc['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($loc['name']); ?>
</option>
<?php endforeach; ?>
</select>

<input type="text" name="query" placeholder="Search services..." value="<?= htmlspecialchars($searchQuery); ?>">

<select name="category">
<option value="">All Categories</option>
<?php foreach($categories as $cat): ?>
<option value="<?= htmlspecialchars($cat['name']); ?>" <?= ($categoryFilter == $cat['name']) ? 'selected' : '' ?>>
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
<img src="https://randomuser.me/api/portraits/<?= rand(0,1)?'men':'women'; ?>/<?= rand(1,99); ?>.jpg" alt="<?= htmlspecialchars($service['provider_name']); ?>">
<?php endif; ?>
</div>

<h3><?= htmlspecialchars($service['title']); ?></h3>
<p><?= htmlspecialchars($service['description']); ?></p>
<p><strong>Price:</strong> R<?= number_format($service['price'],2); ?></p>
<p><strong>Provider:</strong> <?= htmlspecialchars($service['provider_name']); ?></p>
<p><strong>Location:</strong> <?= htmlspecialchars($service['location_name']); ?></p>
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