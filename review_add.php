<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if(!$service_id || $rating < 1 || $rating > 5){
    die("Invalid data.");
}

// Optional: Check if the user actually booked this service
$stmt = $conn->prepare("SELECT id FROM bookings WHERE service_id=? AND user_id=?");
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$booking){
    die("You can only review services you booked.");
}

// Insert review
$stmt = $conn->prepare("INSERT INTO reviews (client_id, service_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiis", $user_id, $service_id, $rating, $comment);
if($stmt->execute()){
    $stmt->close();
    header("Location: profile.php?msg=Review+submitted");
    exit;
} else {
    die("Error submitting review.");
}
?>