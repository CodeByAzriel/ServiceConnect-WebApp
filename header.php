<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceConnect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <nav class="navbar">
        <!-- Logo only, bigger -->
        <div class="logo">
            <a href="index.php">
                <img src="images/logo.png" alt="ServiceConnect Logo" class="logo-img">
            </a>
        </div>

        <!-- Navigation Links -->
        <ul class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
               
   <?php if(isset($_SESSION['user_id'])): ?>
    <?php if($_SESSION['role'] === 'admin'): ?>
        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
    <?php else: ?>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="services.php">Services</a></li>
    <?php endif; ?>
    \
    <li><a href="messages.php">Messages</a></li>
    <li><a href="profile.php">Profile</a></li>
    <li><a href="logout.php">Logout</a></li>
<?php else: ?>
    <li><a href="services.php">Services</a></li>
    <li><a href="login.php">Login</a></li>
    <li><a href="register.php">Register</a></li>

    <?php endif; ?>

<?php else: ?>
    <!-- Guests -->
    <li><a href="services.php">Services</a></li>
    <li><a href="login.php">Login</a></li>
    <li><a href="register.php">Register</a></li>
<?php endif; ?>
        </ul>

        <!-- Optional Hamburger for mobile -->
        <div class="hamburger" onclick="toggleMenu(this)">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
</header>

<script>
function toggleMenu(menu) {
    menu.classList.toggle('toggle');
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('active');
}
</script>
</body>
</html>