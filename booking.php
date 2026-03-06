<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// -----------------------
// 1. User books a service
// -----------------------
if(isset($_POST['book_service'])){
    $service_id = intval($_POST['service_id']);

    // Prevent duplicate pending booking
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE service_id=? AND user_id=? AND status='pending'");
    $stmt->bind_param("ii", $service_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $message = "You already requested this service.";
    } else {
        // Insert booking
        $insert = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, status) VALUES (?, ?, NOW(), 'pending')");
        $insert->bind_param("ii", $user_id, $service_id);
        if($insert->execute()){
            $message = "Booking request sent!";
        } else {
            $message = "Error booking service: " . $insert->error;
        }
        $insert->close();
    }
    $stmt->close();
}

// -----------------------
// 2. Provider Approve/Decline
// -----------------------
if(isset($_POST['update_booking'])){
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'] === 'approved' ? 'approved' : 'declined';
    $update = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $update->bind_param("si", $new_status, $booking_id);
    if($update->execute()){
        $message = "Booking status updated!";
    } else {
        $message = "Error updating booking: " . $update->error;
    }
    $update->close();
}

// -----------------------
// 3. Fetch bookings for provider
// -----------------------
$providerBookings = [];
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.user_id, b.service_id, b.booking_date, b.status,
           u.name AS client_name, s.title AS service_title
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.user_id = u.id
    WHERE s.user_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $providerBookings[] = $row;
}
$stmt->close();
?>

<?php include 'header.php'; ?>

<div class="container profile-page">
<h2>Booking Requests</h2>

<?php if($message != ""): ?>
<p class="success"><?= htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if(!empty($providerBookings)): ?>
<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>Client</th>
        <th>Service</th>
        <th>Date Requested</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach($providerBookings as $b): ?>
    <tr id="booking-<?= $b['booking_id']; ?>">
        <td><?= htmlspecialchars($b['client_name']); ?></td>
        <td><?= htmlspecialchars($b['service_title']); ?></td>
        <td><?= $b['booking_date']; ?></td>
        <td><?= ucfirst($b['status']); ?></td>
        <td>
            <?php if($b['status']=='pending'): ?>
            <form method="POST" style="display:flex;gap:5px;">
                <input type="hidden" name="booking_id" value="<?= $b['booking_id']; ?>">
                <button type="submit" name="update_booking" value="approved">Yes</button>
                <button type="submit" name="update_booking" value="declined">Decline</button>
            </form>
            <?php else: ?>
                <?= ucfirst($b['status']); ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>No booking requests yet.</p>
<?php endif; ?>

</div>

<?php include 'footer.php'; ?>