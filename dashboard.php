<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

require_once 'DBConnect.php';

$username = $_SESSION['username'] ?? 'User';
$user_type = $_SESSION['user_type'] ?? 'user';
$account_id = (int)($_SESSION['account_id'] ?? 0);

if ($user_type === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $text = trim($_POST['text'] ?? '');

    if ($receiver_id <= 0 || $receiver_id === $account_id) {
        $error = 'Please choose a valid recipient.';
    } elseif ($text === '') {
        $error = 'Message text cannot be empty.';
    } else {
        $check = $conn->prepare("SELECT account_id FROM account WHERE account_id = ? AND utype = 'user' AND COALESCE(isbot, 0) = 0");
        $check->bind_param('i', $receiver_id);
        $check->execute();
        $validRecipient = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$validRecipient) {
            $error = 'That recipient was not found.';
        } else {
            $stmt = $conn->prepare('INSERT INTO message (sender_id, receiver_id, text) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $account_id, $receiver_id, $text);
            if ($stmt->execute()) {
                $notice = 'Message sent.';
            } else {
                $error = 'Message could not be sent.';
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $message_id = (int)($_POST['message_id'] ?? 0);
    if ($message_id > 0) {
        $stmt = $conn->prepare('DELETE FROM message WHERE message_id = ? AND (sender_id = ? OR receiver_id = ?)');
        $stmt->bind_param('iii', $message_id, $account_id, $account_id);
        if ($stmt->execute()) {
            $notice = 'Message deleted.';
        } else {
            $error = 'Message could not be deleted.';
        }
        $stmt->close();
    }
}

$recipientStmt = $conn->prepare("\n    SELECT a.account_id, COALESCE(p.screenname, a.username) AS display_name\n    FROM account a\n    LEFT JOIN profile p ON p.acc_id = a.account_id\n    WHERE a.account_id <> ? AND a.utype = 'user' AND COALESCE(a.isbot, 0) = 0\n    ORDER BY display_name\n");
$recipientStmt->bind_param('i', $account_id);
$recipientStmt->execute();
$recipients = $recipientStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recipientStmt->close();

$msgStmt = $conn->prepare("\n    SELECT\n        m.message_id,\n        m.sender_id,\n        m.receiver_id,\n        m.text,\n        m.sent_at,\n        m.read_at,\n        COALESCE(sender_profile.screenname, sender_account.username) AS sender_name,\n        COALESCE(receiver_profile.screenname, receiver_account.username) AS receiver_name\n    FROM message m\n    JOIN account sender_account ON sender_account.account_id = m.sender_id\n    JOIN account receiver_account ON receiver_account.account_id = m.receiver_id\n    LEFT JOIN profile sender_profile ON sender_profile.acc_id = sender_account.account_id\n    LEFT JOIN profile receiver_profile ON receiver_profile.acc_id = receiver_account.account_id\n    WHERE m.sender_id = ? OR m.receiver_id = ?\n    ORDER BY m.sent_at DESC\n    LIMIT 50\n");
$msgStmt->bind_param('ii', $account_id, $account_id);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();

$readStmt = $conn->prepare('UPDATE message SET read_at = COALESCE(read_at, NOW()) WHERE receiver_id = ? AND read_at IS NULL');
$readStmt->bind_param('i', $account_id);
$readStmt->execute();
$readStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Parasocial</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .logo { font-size: 1.5em; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .nav-link { color: white; text-decoration: none; padding: 8px 14px; border-radius: 20px; font-size: .95em; }
        .nav-link:hover { background: rgba(255,255,255,0.15); }
        .logout-btn { background: rgba(255,255,255,0.2); border: 2px solid white; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: bold; }
        .logout-btn:hover { background: white; color: #667eea; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .welcome-title { color: #333; font-size: 2em; margin-bottom: 10px; }
        .welcome-subtitle { color: #666; font-size: 1.1em; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .feature-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .feature-icon { font-size: 2.5em; color: #667eea; margin-bottom: 15px; }
        .feature-title { font-size: 1.3em; color: #333; margin-bottom: 10px; font-weight: bold; }
        .feature-title a { color: inherit; text-decoration: none; }
        .feature-text { color: #666; line-height: 1.5; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; }
        .alert.ok { background: #e9f7ef; color: #1e7040; border: 1px solid #bfe8cf; }
        .alert.err { background: #fdecea; color: #9f2d20; border: 1px solid #f5c2bd; }
        .section-title { font-size: 1.45em; color: #333; margin-bottom: 18px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .message-form { background: #f0f2ff; border-radius: 12px; padding: 18px; margin-bottom: 22px; }
        .message-row { display: flex; gap: 10px; flex-wrap: wrap; }
        select, textarea, button { font-family: inherit; }
        .recipient-select { border: 2px solid #e0e0e0; border-radius: 8px; padding: 9px 12px; min-width: 200px; flex: 1; }
        .message-input { border: 2px solid #e0e0e0; border-radius: 8px; padding: 9px 12px; min-height: 42px; flex: 3; min-width: 260px; resize: vertical; }
        .send-btn { background: linear-gradient(135deg,#667eea,#764ba2); border: none; color: white; border-radius: 8px; padding: 10px 20px; font-weight: bold; cursor: pointer; }
        .message-item { display: flex; gap: 14px; padding: 14px; border-radius: 10px; margin-bottom: 10px; background: #f8f9ff; border-left: 3px solid #667eea; }
        .message-item.sent { background: #fafafa; border-left-color: #aaa; }
        .avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#667eea,#764ba2); color: white; font-weight: bold; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .message-body { flex: 1; min-width: 0; }
        .message-meta { font-weight: bold; color: #333; }
        .timestamp { color: #999; font-size: .82em; margin-left: 8px; font-weight: normal; }
        .message-text { color: #555; margin-top: 6px; white-space: pre-wrap; overflow-wrap: anywhere; }
        .delete-btn { background: transparent; border: none; color: #999; cursor: pointer; padding: 4px 8px; }
        .delete-btn:hover { color: #b00020; }
        .empty { color: #888; text-align: center; padding: 25px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
            <div class="user-info">
                <a class="nav-link" href="find_matches.php"><i class="fas fa-heart"></i> Find Matches</a>
                <a class="nav-link" href="messages.php"><i class="fas fa-comments"></i> Messages</a>
                <a class="nav-link" href="edit_profile.php"><i class="fas fa-user-edit"></i> Profile</a>
                <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <span><i class="fas fa-user-circle"></i> <?php echo h($username); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h1 class="welcome-title">Welcome back, <?php echo h($username); ?></h1>
            <p class="welcome-subtitle">Use the tools below to manage your profile, find matches, and send private messages.</p>
        </div>

        <?php if ($notice): ?><div class="alert ok"><?php echo h($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert err"><?php echo h($error); ?></div><?php endif; ?>

        <div class="card-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-heart"></i></div>
                <h3 class="feature-title"><a href="find_matches.php">Find Matches</a></h3>
                <p class="feature-text">Find someone else to bother.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-user-edit"></i></div>
                <h3 class="feature-title"><a href="edit_profile.php">Edit Profile</a></h3>
                <p class="feature-text">Update your screenname, summary, likes, dislikes, and privacy settings.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-cog"></i></div>
                <h3 class="feature-title"><a href="settings.php">Settings</a></h3>
                <p class="feature-text">Manage account settings.</p>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title"><i class="fas fa-envelope"></i> Private Messages</h2>

            <form class="message-form" method="POST">
                <div class="message-row">
                    <select name="receiver_id" class="recipient-select" required>
                        <option value="">Select recipient</option>
                        <?php foreach ($recipients as $recipient): ?>
                            <option value="<?php echo (int)$recipient['account_id']; ?>"><?php echo h($recipient['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="text" class="message-input" placeholder="Write a private message..." required></textarea>
                    <button type="submit" name="send_message" class="send-btn"><i class="fas fa-paper-plane"></i> Send</button>
                </div>
            </form>

            <?php if (empty($messages)): ?>
                <div class="empty"><i class="fas fa-envelope-open"></i><br>No messages yet.</div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <?php
                        $isSent = ((int)$message['sender_id'] === $account_id);
                        $displayName = $isSent ? 'You → ' . $message['receiver_name'] : $message['sender_name'];
                        $initial = strtoupper(substr($isSent ? $message['receiver_name'] : $message['sender_name'], 0, 1));
                    ?>
                    <div class="message-item <?php echo $isSent ? 'sent' : ''; ?>">
                        <div class="avatar"><?php echo h($initial); ?></div>
                        <div class="message-body">
                            <div class="message-meta">
                                <?php echo h($displayName); ?>
                                <span class="timestamp"><?php echo h(date('M j, Y g:i A', strtotime($message['sent_at']))); ?></span>
                            </div>
                            <div class="message-text"><?php echo h($message['text']); ?></div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete this message?');">
                            <input type="hidden" name="message_id" value="<?php echo (int)$message['message_id']; ?>">
                            <button type="submit" name="delete_message" class="delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
