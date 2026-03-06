<?php
// db.php - Database connection for ServiceConnect

$servername = "sql302.infinityfree.com";      // InfinityFree host
$username   = "if0_41319048";                  // Your MySQL username
$password   = "n10U0mZeZrE6f50";              // Your MySQL password
$dbname     = "if0_41319048_serviceconnect";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, 3306);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8 for proper encoding
$conn->set_charset("utf8");
?>