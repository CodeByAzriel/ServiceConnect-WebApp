<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle sending a message
if(isset($_POST['send_message'])){
    $receiver_id = intval($_POST['receiver_id']);
    $msg = trim($_POST['message_text']);
    if(!empty($msg)){
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?,?,?)");
        $stmt->bind_param("iis", $user_id, $receiver_id, $msg);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle reporting a user
if(isset($_POST['report_user'])){
    $reported_user = intval($_POST['reported_user_id']);
    $report_msg = trim($_POST['report_text']);
    if(!empty($report_msg)){
        $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, message) VALUES (?,?,?)");
        $stmt->bind_param("iis", $user_id, $reported_user, $report_msg);
        $stmt->execute();
        $stmt->close();
        $message = "Report sent to admin!";
    }
}

// Fetch users (exclude admins)
$users = [];
$search = $_GET['search'] ?? '';
$search = "%".$search."%";
$stmt = $conn->prepare("SELECT id, name, location FROM users WHERE role!='admin' AND name LIKE ? AND id!=? ORDER BY name ASC");
$stmt->bind_param("si", $search, $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $users[] = $row;
}
$stmt->close();

// Selected chat
$chat_with = intval($_GET['user'] ?? 0);
$messages = [];
$chat_user = null;
if($chat_with){
    // Fetch messages
    $stmt = $conn->prepare("
        SELECT m.*, u.name AS sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.timestamp ASC
    ");
    $stmt->bind_param("iiii", $user_id, $chat_with, $chat_with, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $messages[] = $row;
    }
    $stmt->close();

    // Chat user info
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id=?");
    $stmt->bind_param("i", $chat_with);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat_user = $res->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messaging - ServiceConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#f3f4f6; color:#1f2937; }
.container { max-width:1200px; margin:40px auto; display:flex; gap:25px; }
.user-list { flex:1; background:#fff; border-radius:18px; padding:20px; box-shadow:0 10px 25px rgba(0,0,0,0.08); max-height:80vh; overflow-y:auto; }
.user-list h2 { font-size:1.5rem; color:#4f46e5; margin-bottom:15px; }
.user-list input { width:100%; padding:10px 15px; margin-bottom:15px; border-radius:12px; border:1px solid #d1d5db; }
.user-list a { display:block; padding:12px 15px; margin-bottom:10px; background:#f9fafb; border-radius:12px; text-decoration:none; color:#111827; font-weight:500; transition:0.3s; }
.user-list a:hover, .user-list a.active { background:#4f46e5; color:#fff; }
.chat-box { flex:3; display:flex; flex-direction:column; background:#fff; border-radius:18px; padding:20px; box-shadow:0 10px 25px rgba(0,0,0,0.08); max-height:80vh; }
.chat-header { font-size:1.25rem; font-weight:600; color:#4f46e5; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; }
.chat-messages { flex:1; overflow-y:auto; padding-right:10px; margin-bottom:15px; }
.message { padding:10px 15px; margin-bottom:10px; border-radius:18px; max-width:70%; }
.message.sent { background:#4f46e5; color:#fff; margin-left:auto; border-bottom-right-radius:4px; }
.message.received { background:#f3f4f6; color:#111827; margin-right:auto; border-bottom-left-radius:4px; }
.chat-input { display:flex; gap:10px; }
.chat-input textarea { flex:1; padding:10px 15px; border-radius:12px; border:1px solid #d1d5db; resize:none; }
.chat-input button { padding:10px 18px; border:none; border-radius:12px; background:#4f46e5; color:#fff; font-weight:600; cursor:pointer; transition:0.3s; }
.chat-input button:hover { opacity:0.85; }
.report-btn { background:#ef4444; color:#fff; padding:6px 12px; border-radius:12px; border:none; cursor:pointer; font-weight:600; margin-left:10px; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

<div class="user-list">
<h2>Users</h2>
<form method="GET">
<input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
</form>

<?php if($users): ?>
<?php foreach($users as $u): ?>
<a href="messages.php?user=<?= $u['id'] ?>" class="<?= ($chat_with==$u['id'])?'active':'' ?>">
<?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['location'] ?? 'No location') ?>)
</a>
<?php endforeach; ?>
<?php else: ?>
<p>No users found.</p>
<?php endif; ?>
</div>

<div class="chat-box">
<?php if($chat_with && $chat_user): ?>
<div class="chat-header">
<span><?= htmlspecialchars($chat_user['name']) ?></span>
<form method="POST" style="margin:0;">
<input type="hidden" name="reported_user_id" value="<?= $chat_user['id'] ?>">
<button type="submit" name="report_user" class="report-btn">Report</button>
</form>
</div>

<div class="chat-messages" id="chat-messages">
<?php if($messages): ?>
<?php foreach($messages as $m): ?>
<div class="message <?= ($m['sender_id']==$user_id)?'sent':'received' ?>">
<?= htmlspecialchars($m['message_text']) ?>
<br><small style="opacity:0.7;"><?= $m['timestamp'] ?></small>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>No messages yet. Start the conversation!</p>
<?php endif; ?>
</div>

<form method="POST" class="chat-input">
<input type="hidden" name="receiver_id" value="<?= $chat_user['id'] ?>">
<textarea name="message_text" placeholder="Type your message..." required></textarea>
<button type="submit" name="send_message">Send</button>
</form>

<?php else: ?>
<p>Select a user to start chatting.</p>
<?php endif; ?>
</div>

</div>

<script>
// Scroll to newest message on load
function scrollChat() {
    var chatDiv = document.getElementById('chat-messages');
    if(chatDiv){
        chatDiv.scrollTop = chatDiv.scrollHeight;
    }
}
scrollChat();

// Auto-refresh messages every 3 seconds
setInterval(function(){
    if(<?= $chat_with ?: 0 ?>){
        fetch('messages.php?ajax=1&user=<?= $chat_with ?>')
        .then(res => res.text())
        .then(html => {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html,'text/html');
            var newMessages = doc.getElementById('chat-messages');
            if(newMessages){
                document.getElementById('chat-messages').innerHTML = newMessages.innerHTML;
                scrollChat();
            }
        });
    }
},3000);
</script>

<?php
// AJAX only messages return
if(isset($_GET['ajax']) && $chat_with){
    echo '<div id="chat-messages">';
    foreach($messages as $m){
        echo '<div class="message '.($m['sender_id']==$user_id?'sent':'received').'">';
        echo htmlspecialchars($m['message_text']);
        echo '<br><small style="opacity:0.7;">'.$m['timestamp'].'</small>';
        echo '</div>';
    }
    echo '</div>';
    exit;
}
?>

</body>
</html>