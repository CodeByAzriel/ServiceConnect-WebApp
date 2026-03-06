<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch service owner
$stmt = $conn->prepare("SELECT user_id FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$service){
    die("Service not found.");
}

// Only owner or admin can delete
if($service['user_id'] != $user_id && $_SESSION['role'] !== 'admin'){
    die("Unauthorized access.");
}

// Delete service
$stmt = $conn->prepare("DELETE FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->close();

header("Location: dashboard.php?msg=Service+deleted");
exit;
?>