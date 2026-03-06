<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['booking_id'], $_POST['action'])){
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action'];

    // Map string actions to status
    $allowed = ['approved','declined','completed'];
    if(!in_array($action, $allowed)){
        $_SESSION['message'] = "Invalid action.";
        header("Location: dashboard.php");
        exit;
    }

    // Check if the booking belongs to a service of this user
    $stmt = $conn->prepare("
        SELECT b.id 
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.id=? AND s.user_id=?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $stmt->close();

        // Update booking status
        $update = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $update->bind_param("si", $action, $booking_id);
        $update->execute();
        $update->close();

        $_SESSION['message'] = "Booking status updated to '$action'.";
    } else {
        $stmt->close();
        $_SESSION['message'] = "Booking not found or not yours.";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
}

header("Location: dashboard.php");
exit;