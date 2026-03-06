<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile pic upload
if(isset($_POST['update_pic']) && isset($_FILES['profile_pic'])){
    $file = $_FILES['profile_pic'];
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(in_array($ext, $allowed) && $file['size'] <= 2*1024*1024){ // 2MB
        $newName = "user_{$user_id}_" . time() . "." . $ext;
        move_uploaded_file($file['tmp_name'], "images/profiles/".$newName);
        $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
        $stmt->bind_param("si", $newName, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Profile picture updated!";
        $user['profile_pic'] = $newName;
    } else {
        $message = "Invalid file type or too large!";
    }
}

// Handle password change
if(isset($_POST['change_password'])){
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if($new !== $confirm){
        $message = "New passwords do not match!";
    } else {
        // Check current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();

        if(password_verify($current, $hash)){
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $newHash, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Password updated successfully!";
        } else {
            $message = "Current password is incorrect!";
        }
    }
}

// Handle account deletion
if(isset($_POST['delete_account'])){
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    session_destroy();
    header("Location: index.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<div class="container profile-page">
<h1>Profile Settings</h1>

<?php if($message): ?>
<p class="success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Profile Picture -->
<div class="profile-section">
<h2>Profile Picture</h2>
<img src="<?= !empty($user['profile_pic']) ? 'images/profiles/'.$user['profile_pic'] : 'https://randomuser.me/api/portraits/lego/1.jpg' ?>" 
alt="Profile Picture" class="profile-pic">

<form method="POST" enctype="multipart/form-data">
<input type="file" name="profile_pic" accept="image/*" required>
<button type="submit" name="update_pic" class="btn primary">Update Picture</button>
</form>
</div>

<!-- Change Password -->
<div class="profile-section">
<h2>Change Password</h2>
<form method="POST">
<input type="password" name="current_password" placeholder="Current Password" required>
<input type="password" name="new_password" placeholder="New Password" required>
<input type="password" name="confirm_password" placeholder="Confirm New Password" required>
<button type="submit" name="change_password" class="btn primary">Update Password</button>
</form>
</div>

<!-- Delete Account -->
<div class="profile-section">
<h2>Delete Account</h2>
<p>Warning: This action is permanent.</p>
<form method="POST" onsubmit="return confirm('Are you sure you want to delete your account?');">
<button type="submit" name="delete_account" class="btn delete">Delete Account</button>
</form>
</div>

</div>

<?php include 'footer.php'; ?>

<style>
.container.profile-page { max-width:700px; margin:50px auto; background:#f9fafb; padding:30px; border-radius:18px; box-shadow:0 10px 25px rgba(0,0,0,0.08); }
h1 { color:#4f46e5; font-size:2rem; margin-bottom:25px; }
.profile-section { margin-bottom:30px; }
.profile-pic { width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:15px; }
input[type="file"], input[type="password"] { width:100%; padding:10px 15px; margin-bottom:15px; border-radius:12px; border:1px solid #d1d5db; }
button.btn { padding:10px 18px; border:none; border-radius:12px; font-weight:600; cursor:pointer; transition:0.3s; }
button.btn.primary { background:#4f46e5; color:#fff; }
button.btn.primary:hover { opacity:0.85; }
button.btn.delete { background:#ef4444; color:#fff; }
button.btn.delete:hover { opacity:0.85; }
.success { background:#22c55e; color:#fff; padding:10px 15px; border-radius:12px; margin-bottom:20px; display:inline-block; }
</style>