<?php
// logout.php - Log the user out
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>