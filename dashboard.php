<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$user_type = $_SESSION['user_type'] ?? 'user';
$account_id = $_SESSION['account_id'] ?? 0;

require_once 'DBConnect.php';

// Handle send message from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dash_send_message'])) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $text        = trim($_POST['text'] ?? '');
    if ($receiver_id && $text !== '') {
        $stmt = $conn->prepare("INSERT INTO message (sender_id, receiver_id, text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $account_id, $receiver_id, $text);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard.php'); exit();
}

// Handle delete message from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dash_delete_message'])) {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    if ($msg_id) {
        $del = $conn->prepare("DELETE FROM message WHERE message_id = ? AND (sender_id = ? OR receiver_id = ?)");
        $del->bind_param("iii", $msg_id, $account_id, $account_id);
        $del->execute(); $del->close();
    }
    header('Location: dashboard.php'); exit();
}

// Fetch inbox: latest message per conversation partner
$inboxQuery = "
    SELECT
        m.message_id,
        m.text,
        m.sent_at,
        m.read_at,
        m.sender_id,
        m.receiver_id,
        CASE WHEN m.sender_id = {$account_id} THEN 'sent' ELSE 'received' END AS direction,
        p.screenname AS partner_screenname,
        a2.account_id AS partner_id
    FROM message m
    JOIN account a2 ON a2.account_id = CASE WHEN m.sender_id = {$account_id} THEN m.receiver_id ELSE m.sender_id END
    JOIN profile p  ON p.acc_id = a2.account_id
    WHERE m.sender_id = {$account_id} OR m.receiver_id = {$account_id}
    ORDER BY m.sent_at DESC
    LIMIT 20
";
$inboxResult = $conn->query($inboxQuery);
$inbox_messages = $inboxResult ? $inboxResult->fetch_all(MYSQLI_ASSOC) : [];

// My screenname for display
$mySnRow = $conn->query("SELECT screenname FROM profile WHERE acc_id = {$account_id}")->fetch_assoc();
$my_screenname = $mySnRow['screenname'] ?? $username;

// Count unread
$unreadCount = 0;
foreach ($inbox_messages as $im) {
    if ($im['direction'] === 'received' && !$im['read_at']) $unreadCount++;
}

// Mutual matches for quick-send dropdown
$matchStmt = $conn->prepare("
    SELECT a.account_id, p.screenname
    FROM matches m
    JOIN account a ON a.account_id = CASE WHEN m.user1_id = ? THEN m.user2_id ELSE m.user1_id END
    JOIN profile p ON p.acc_id = a.account_id
    WHERE (m.user1_id = ? OR m.user2_id = ?) AND m.status = 'matched'
    ORDER BY p.screenname
");
$matchStmt->bind_param("iii", $account_id, $account_id, $account_id);
$matchStmt->execute();
$my_matches = $matchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$matchStmt->close();


// Redirect to admin dashboard if admin
if ($user_type === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Para Social</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5em;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .logout-btn:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-title {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .feature-icon {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .feature-text {
            color: #666;
            line-height: 1.5;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1em;
            opacity: 0.9;
        }
        
        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .activity-item {
            padding: 15px;
            border-left: 3px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.9em;
        }

        /* ── Inbox section ──────────────────────────── */
        .inbox-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .inbox-title {
            font-size: 1.4em;
            color: #333;
            margin-bottom: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .inbox-badge {
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: .7em;
            font-weight: bold;
        }
        .inbox-msg {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 13px 14px;
            border-radius: 10px;
            margin-bottom: 8px;
            background: #f8f9ff;
            border-left: 3px solid #667eea;
            transition: background .2s;
        }
        .inbox-msg.unread {
            background: #eef0ff;
            border-left-color: #764ba2;
        }
        .inbox-msg.sent {
            border-left-color: #ccc;
            background: #fafafa;
        }
        .inbox-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white; font-weight: bold; font-size: 1em;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .inbox-meta { flex: 1; min-width: 0; }
        .inbox-sender { font-weight: bold; color: #333; font-size: .9em; }
        .inbox-timestamp { font-size: .78em; color: #aaa; margin-left: 8px; }
        .inbox-text { color: #555; font-size: .88em; margin-top: 3px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .inbox-actions { display: flex; gap: 6px; flex-shrink: 0; align-items: center; }
        .btn-reply { background: none; border: 1.5px solid #667eea; color: #667eea; border-radius: 7px; padding: 4px 12px; font-size: .8em; font-weight: bold; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-reply:hover { background: #667eea; color: white; }
        .btn-del-inbox { background: none; border: none; color: #ccc; cursor: pointer; font-size: .85em; padding: 4px; transition: color .2s; }
        .btn-del-inbox:hover { color: #b00020; }
        .quick-send { background: #f0f2ff; border-radius: 12px; padding: 18px; margin-top: 16px; }
        .quick-send label { font-weight: bold; color: #555; font-size: .9em; display: block; margin-bottom: 8px; }
        .quick-send-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .qs-select { border: 2px solid #e0e0e0; border-radius: 8px; padding: 9px 12px; font-size: .9em; font-family: inherit; flex: 1; min-width: 140px; outline: none; }
        .qs-select:focus { border-color: #667eea; }
        .qs-input { border: 2px solid #e0e0e0; border-radius: 8px; padding: 9px 14px; font-size: .9em; font-family: inherit; flex: 3; min-width: 160px; outline: none; }
        .qs-input:focus { border-color: #667eea; }
        .qs-send { background: linear-gradient(135deg,#667eea,#764ba2); border: none; color: white; border-radius: 8px; padding: 9px 20px; font-size: .9em; font-weight: bold; cursor: pointer; white-space: nowrap; }
        .qs-send:hover { opacity: .9; }
        .empty-inbox { text-align: center; color: #bbb; padding: 24px; font-size: .9em; }

    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <i class="fas fa-heart"></i> Parasocial
            </div>
            <div class="user-info">
                <a href="find_matches.php" style="color:white;text-decoration:none;padding:8px 14px;border-radius:20px;font-size:.95em;"><i class="fas fa-heart"></i> Find Matches</a><a href="messages.php" style="color:white;text-decoration:none;padding:8px 14px;border-radius:20px;font-size:.95em;"><i class="fas fa-comments"></i> Messages</a><a href="edit_profile.php" style="color:white;text-decoration:none;padding:8px 14px;border-radius:20px;font-size:.95em;"><i class="fas fa-user-edit"></i> Profile</a><a href="settings.php" style="color:white;text-decoration:none;padding:8px 14px;border-radius:20px;font-size:.95em;"><i class="fas fa-cog"></i> Settings</a><span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h1>
            <p class="welcome-subtitle">Ready to make meaningful connections today?</p>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div class="stat-label">New Matches</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">8</div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">45</div>
                <div class="stat-label">Profile Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">23</div>
                <div class="stat-label">Likes Received</div>
            </div>
        </div>
        
        <!-- Feature Cards -->
        <div class="card-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="feature-title"><a href="find_matches.php" style="color:inherit;text-decoration:none;">Find Matches</a></h3>
                <p class="feature-text">Discover compatible people based on your interests and preferences.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="feature-title"><a href="messages.php" style="color:inherit;text-decoration:none;">Messages</a></h3>
                <p class="feature-text">Chat with your matches and build meaningful connections.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3 class="feature-title"><a href="edit_profile.php" style="color:inherit;text-decoration:none;">Edit Profile</a></h3>
                <p class="feature-text">Update your profile to showcase your best self.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h3 class="feature-title"><a href="settings.php" style="color:inherit;text-decoration:none;">Settings</a></h3>
                <p class="feature-text">Customize your preferences and privacy settings.</p>
            </div>
        </div>
        

        <!-- Inbox Section -->
        <div class="inbox-section">
            <h2 class="inbox-title">
                <i class="fas fa-inbox" style="color:#667eea"></i> Inbox
                <?php if ($unreadCount > 0): ?>
                    <span class="inbox-badge"><?php echo $unreadCount; ?> new</span>
                <?php endif; ?>
                <a href="messages.php" style="margin-left:auto;font-size:.65em;color:#667eea;font-weight:normal;text-decoration:none;">
                    View all <i class="fas fa-arrow-right"></i>
                </a>
            </h2>

            <?php if (empty($inbox_messages)): ?>
                <div class="empty-inbox"><i class="fas fa-envelope-open" style="font-size:2em;display:block;margin-bottom:8px;"></i>No messages yet. Match with someone and say hi!</div>
            <?php else: ?>
                <?php foreach ($inbox_messages as $im): ?>
                    <?php
                        $isUnread  = ($im['direction'] === 'received' && !$im['read_at']);
                        $rowClass  = $im['direction'] === 'sent' ? 'sent' : ($isUnread ? 'unread' : '');
                        $label     = $im['direction'] === 'sent'
                            ? 'You → ' . htmlspecialchars($im['partner_screenname'])
                            : htmlspecialchars($im['partner_screenname']);
                    ?>
                    <div class="inbox-msg <?php echo $rowClass; ?>">
                        <div class="inbox-avatar"><?php echo strtoupper(substr($im['partner_screenname'],0,1)); ?></div>
                        <div class="inbox-meta">
                            <span class="inbox-sender"><?php echo $label; ?></span>
                            <span class="inbox-timestamp"><?php echo date('M j, g:i a', strtotime($im['sent_at'])); ?></span>
                            <div class="inbox-text"><?php echo htmlspecialchars($im['text']); ?></div>
                        </div>
                        <div class="inbox-actions">
                            <a href="messages.php?with=<?php echo $im['partner_id']; ?>" class="btn-reply">
                                <i class="fas fa-reply"></i> <?php echo $im['direction']==='sent' ? 'View' : 'Reply'; ?>
                            </a>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="message_id" value="<?php echo $im['message_id']; ?>">
                                <button type="submit" name="dash_delete_message" class="btn-del-inbox"
                                        title="Delete" onclick="return confirm('Delete this message?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Quick Send -->
            <?php if (!empty($my_matches)): ?>
            <div class="quick-send">
                <label><i class="fas fa-paper-plane" style="color:#667eea"></i> Send a message</label>
                <form method="POST">
                    <div class="quick-send-row">
                        <select name="receiver_id" class="qs-select" required>
                            <option value="">— Select match —</option>
                            <?php foreach ($my_matches as $mm): ?>
                                <option value="<?php echo $mm['account_id']; ?>">
                                    <?php echo htmlspecialchars($mm['screenname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input class="qs-input" type="text" name="text" placeholder="Type a message…" required>
                        <button type="submit" name="dash_send_message" class="qs-send">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2 class="activity-title"><i class="fas fa-clock"></i> Recent Activity</h2>
            
            <div class="activity-item">
                <strong>New Match!</strong> You matched with Sarah J.
                <div class="activity-time">2 hours ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Message Received</strong> Mike T. sent you a message
                <div class="activity-time">5 hours ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Profile View</strong> Someone viewed your profile
                <div class="activity-time">1 day ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Like Received</strong> Emma R. liked your profile
                <div class="activity-time">2 days ago</div>
            </div>
        </div>
    </div>
</body>
</html>
