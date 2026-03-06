<?php
session_start();
include 'db.php';

// Only allow admin
if(!isset($_SESSION['user_id']) || $_SESSION['current_role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// Get user ID and ensure it’s an integer
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Prevent admin deleting themselves
if($user_id === (int)$_SESSION['user_id']){
    die("Cannot delete your own account.");
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: admin_dashboard.php?msg=User+deleted");
exit;
?>