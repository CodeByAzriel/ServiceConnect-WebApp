<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();


// Handle messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// ---------------------------
// Dashboard Data
// ---------------------------

// Pending bookings count
$pendingCount = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt 
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    WHERE s.user_id = ? AND b.status='pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pendingCount);
$stmt->fetch();
$stmt->close();

// Total services
$stmt = $conn->prepare("SELECT COUNT(*) FROM services WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($totalServices);
$stmt->fetch();
$stmt->close();

// Latest bookings (limit 5)
$recentBookings = [];
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, u.name AS client_name, s.title AS service_title, b.booking_date, b.status
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.user_id = u.id
    WHERE s.user_id = ?
    ORDER BY b.booking_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $recentBookings[] = $row;
}
$stmt->close();

// User services for edit/delete
$services = [];
$stmt = $conn->prepare("SELECT * FROM services WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $services[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - ServiceConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f3f4f6; margin:0; padding:0; color:#1f2937; }
.dashboard-container { max-width:1200px; margin:0 auto; padding:40px 20px; }
.dashboard-container h1 { font-size:2.5rem; color:#4f46e5; font-weight:600; margin-bottom:30px; }
.dashboard-cards { display:flex; flex-wrap:wrap; gap:25px; margin-bottom:40px; }
.card { background: linear-gradient(135deg, #ffffff, #f9fafb); flex:1; min-width:200px; border-radius:18px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.08); transition:0.3s; }
.card:hover { transform: translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,0.15); }
.card h3 { font-size:1.25rem; margin-bottom:10px; color:#111827; }
.card-count { font-size:2rem; font-weight:600; color:#4f46e5; margin-bottom:15px; }
.card-link { font-weight:500; text-decoration:none; color:#4f46e5; }
.card-link:hover { text-decoration:underline; }
.booking-table-container h2, .services-table-container h2 { font-size:1.75rem; color:#4f46e5; margin-bottom:15px; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.05); margin-bottom:40px; }
th, td { padding:15px 12px; text-align:left; border-bottom:1px solid #e5e7eb; }
th { background:#f9fafb; font-weight:600; }
tr:hover { background:#f3f4f6; }
.status { font-weight:500; padding:5px 10px; border-radius:12px; color:#fff; display:inline-block; text-transform:capitalize; font-size:0.9rem; }
.status.pending { background:#facc15; }
.status.approved { background:#22c55e; }
.status.declined { background:#ef4444; }
.status.completed { background:#3b82f6; }
.btn.small { background:#4f46e5; color:#fff; padding:5px 12px; font-size:0.85rem; border-radius:10px; text-decoration:none; transition:0.3s; border:none; cursor:pointer; }
.btn.small:hover { opacity:0.85; }
.btn.approve { background:#22c55e; }
.btn.decline { background:#ef4444; }
.btn.complete { background:#3b82f6; }
.btn.edit { background:#fbbf24; }
.btn.delete { background:#ef4444; }
.booking-table form, .services-table form { display:inline-block; margin-right:5px; }
@media (max-width:768px){ .dashboard-cards { flex-direction:column; } .card { min-width:100%; } }
.success { background:#22c55e; color:#fff; padding:10px 15px; border-radius:12px; margin-bottom:20px; display:inline-block; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="dashboard-container">

<h1>Welcome back, <?= htmlspecialchars($user_name); ?>!</h1>

<?php if($message): ?>
<p class="success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="dashboard-cards">
    <div class="card">
        <h3>Pending Bookings</h3>
        <p class="card-count"><?= $pendingCount ?></p>
        <a href="#bookings" class="card-link">View All</a>
    </div>

    <div class="card">
        <h3>Total Services</h3>
        <p class="card-count"><?= $totalServices ?></p>
        <a href="#services" class="card-link">View Services</a>
    </div>
</div>

<!-- Bookings -->
<div class="booking-table-container" id="bookings">
<h2>Latest Booking Requests</h2>
<?php if($recentBookings): ?>
<table>
<thead>
<tr><th>Client</th><th>Service</th><th>Date</th><th>Status</th><th>Action</th></tr>
</thead>
<tbody>
<?php foreach($recentBookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['client_name']) ?></td>
<td><?= htmlspecialchars($b['service_title']) ?></td>
<td><?= $b['booking_date'] ?></td>
<td class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></td>
<td>
<?php if($b['status']=='pending'): ?>
<form method="POST" action="booking_action.php">
<input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
<input type="hidden" name="action" value="approved">
<button type="submit" class="btn small approve">Accept</button>
</form>
<form method="POST" action="booking_action.php">
<input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
<input type="hidden" name="action" value="declined">
<button type="submit" class="btn small decline">Decline</button>
</form>
<?php elseif($b['status']=='approved'): ?>
<form method="POST" action="booking_action.php">
<input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
<input type="hidden" name="action" value="completed">
<button type="submit" class="btn small complete">Complete</button>
</form>
<?php else: ?>
<?= ucfirst($b['status']) ?>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No recent bookings.</p>
<?php endif; ?>
</div>

<!-- Services -->
<div class="services-table-container" id="services">
<h2>Your Services</h2>
<?php if($services): ?>
<table>
<thead>
<tr><th>Title</th><th>Category</th><th>Price</th><th>Skill</th><th>Status</th><th>Action</th></tr>
</thead>
<tbody>
<?php foreach($services as $s): ?>
<tr>
<td><?= htmlspecialchars($s['title']) ?></td>
<td><?= htmlspecialchars($s['category']) ?></td>
<td>R<?= number_format($s['price'],2) ?></td>
<td><?= htmlspecialchars($s['skill_level']) ?></td>
<td class="status <?= $s['status'] ?? 'active' ?>"><?= ucfirst($s['status'] ?? 'active') ?></td>
<td>
<a href="edit_service.php?id=<?= $s['id'] ?>" class="btn small edit">Edit</a>
<form method="POST" action="delete_service.php" onsubmit="return confirm('Are you sure?')">
<input type="hidden" name="service_id" value="<?= $s['id'] ?>">
<button type="submit" class="btn small delete">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>You have no services yet.</p>
<?php endif; ?>
</div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>