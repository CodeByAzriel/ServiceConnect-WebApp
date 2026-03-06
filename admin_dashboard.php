<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

/* Dashboard Stats */

$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalServices = $conn->query("SELECT COUNT(*) as total FROM services")->fetch_assoc()['total'];

/* Fetch Data */

$users = $conn->query("SELECT id,name,email,role FROM users ORDER BY id DESC");
$services = $conn->query("
SELECT services.id, services.title, services.category, users.name
FROM services
LEFT JOIN users ON services.user_id = users.id
ORDER BY services.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<style>

/* GENERAL */

body{
font-family: 'Segoe UI', sans-serif;
background:#f4f6f9;
margin:0;
padding:40px;
color:#333;
}

h1{
margin-bottom:25px;
}

/* DASHBOARD CARDS */

.dashboard{
display:flex;
gap:25px;
margin-bottom:40px;
flex-wrap:wrap;
}

.card{
background:white;
padding:25px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
width:220px;
transition:0.2s;
}

.card:hover{
transform:translateY(-3px);
}

.card h3{
margin:0;
font-size:18px;
color:#666;
}

.card p{
font-size:28px;
margin:10px 0 0 0;
font-weight:bold;
}

/* TABLES */

.table-container{
background:white;
padding:25px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
margin-bottom:40px;
}

table{
width:100%;
border-collapse:collapse;
margin-top:15px;
}

th{
background:#f2f2f2;
padding:12px;
text-align:left;
}

td{
padding:12px;
border-bottom:1px solid #eee;
}

tr:hover{
background:#fafafa;
}

/* BUTTONS */

.btn{
padding:7px 12px;
border:none;
border-radius:6px;
cursor:pointer;
font-size:13px;
}

.delete{
background:#ff4d4d;
color:white;
}

.edit{
background:#4a90e2;
color:white;
}

.delete:hover{
background:#e13c3c;
}

.edit:hover{
background:#3b78c7;
}

</style>

</head>

<body>

<h1>Admin Dashboard</h1>

<!-- Dashboard Stats -->

<div class="dashboard">

<div class="card">
<h3>Total Users</h3>
<p><?php echo $totalUsers; ?></p>
</div>

<div class="card">
<h3>Total Services</h3>
<p><?php echo $totalServices; ?></p>
</div>

</div>

<!-- USERS TABLE -->

<div class="table-container">

<h2>Manage Users</h2>

<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Actions</th>
</tr>

<?php while($row = $users->fetch_assoc()): ?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['role']; ?></td>

<td>

<a href="edit_user.php?id=<?php echo $row['id']; ?>">
<button class="btn edit">Edit</button>
</a>

<a href="delete_user.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete user?')">
<button class="btn delete">Delete</button>
</a>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>


<!-- SERVICES TABLE -->

<div class="table-container">

<h2>Manage Services</h2>

<table>

<tr>
<th>ID</th>
<th>Service</th>
<th>Category</th>
<th>Provider</th>
<th>Actions</th>
</tr>

<?php while($row = $services->fetch_assoc()): ?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['title']; ?></td>
<td><?php echo $row['category']; ?></td>
<td><?php echo $row['name']; ?></td>

<td>

<a href="edit_service.php?id=<?php echo $row['id']; ?>">
<button class="btn edit">Edit</button>
</a>

<a href="delete_service.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete service?')">
<button class="btn delete">Delete</button>
</a>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</body>
</html>