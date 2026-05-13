<?php
session_start();
require_once 'auth.php';
require_login(); // guests cannot use messaging
require_once 'DBConnect.php';

$username   = $_SESSION['username'] ?? 'User';
$account_id = $_SESSION['account_id'] ?? 0;
$active_chat = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// ── Send message ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $text        = trim($_POST['text'] ?? '');
    if ($receiver_id && $text !== '') {
        $stmt = $conn->prepare("INSERT INTO message (sender_id, receiver_id, text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $account_id, $receiver_id, $text);
        $stmt->execute();
        $stmt->close();
        header("Location: messages.php?with=$receiver_id"); exit();
    }
}

// ── Delete a message (only if current user is sender OR receiver) ─────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    $with   = (int)($_POST['with_id']    ?? 0);
    if ($msg_id) {
        $del = $conn->prepare("
            DELETE FROM message
            WHERE message_id = ?
              AND (sender_id = ? OR receiver_id = ?)
        ");
        $del->bind_param("iii", $msg_id, $account_id, $account_id);
        $del->execute();
        $del->close();
    }
    header("Location: messages.php?with=$with"); exit();
}

// ── Mark incoming messages as read ────────────────────────────────────────
if ($active_chat) {
    $markRead = $conn->prepare(
        'UPDATE message SET read_at = NOW() WHERE receiver_id = ? AND sender_id = ? AND read_at IS NULL'
    );
    $markRead->bind_param('ii', $account_id, $active_chat);
    $markRead->execute();
    $markRead->close();
}

// ── Conversation list (people we've messaged) ─────────────────────────────
$convQuery = "
    SELECT DISTINCT
        a.account_id,
        p.screenname,
        (SELECT text FROM message
         WHERE (sender_id = a.account_id AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = a.account_id)
         ORDER BY sent_at DESC LIMIT 1) AS last_msg,
        (SELECT sent_at FROM message
         WHERE (sender_id = a.account_id AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = a.account_id)
         ORDER BY sent_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM message
         WHERE sender_id = a.account_id AND receiver_id = ? AND read_at IS NULL) AS unread
    FROM account a
    JOIN profile p ON p.acc_id = a.account_id
    WHERE a.account_id IN (
        SELECT sender_id   FROM message WHERE receiver_id = ?
        UNION
        SELECT receiver_id FROM message WHERE sender_id   = ?
    )
    ORDER BY last_time DESC
";
$cs = $conn->prepare($convQuery);
$cs->bind_param("iiiiiii",
    $account_id,$account_id,$account_id,$account_id,
    $account_id,$account_id,$account_id);
$cs->execute();
$conversations = $cs->get_result()->fetch_all(MYSQLI_ASSOC);
$cs->close();

// ── Also pull mutual matches with no messages yet ─────────────────────────
$ms = $conn->prepare("
    SELECT a.account_id, p.screenname
    FROM matches m
    JOIN account a ON a.account_id =
        CASE WHEN m.user1_id = ? THEN m.user2_id ELSE m.user1_id END
    JOIN profile p ON p.acc_id = a.account_id
    WHERE (m.user1_id = ? OR m.user2_id = ?) AND m.status = 'matched'
");
$ms->bind_param("iii", $account_id, $account_id, $account_id);
$ms->execute();
$matches = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
$ms->close();

$convIds = array_column($conversations, 'account_id');
foreach ($matches as $m) {
    if (!in_array($m['account_id'], $convIds)) {
        $conversations[] = [
            'account_id' => $m['account_id'],
            'screenname' => $m['screenname'],
            'last_msg'   => null,
            'last_time'  => null,
            'unread'     => 0,
        ];
    }
}

// ── Active chat messages (with sender screenname) ─────────────────────────
$chat_messages = [];
$chat_partner  = null;
if ($active_chat) {
    $pStmt = $conn->prepare("
        SELECT p.screenname
        FROM profile p
        JOIN account a ON a.account_id = p.acc_id
        WHERE a.account_id = ?
    ");
    $pStmt->bind_param("i", $active_chat);
    $pStmt->execute();
    $chat_partner = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    // Get my own screenname for display
    $mySnStmt = $conn->prepare("SELECT screenname FROM profile WHERE acc_id = ?");
    $mySnStmt->bind_param("i", $account_id);
    $mySnStmt->execute();
    $myProfile = $mySnStmt->get_result()->fetch_assoc();
    $mySnStmt->close();
    $my_screenname = $myProfile['screenname'] ?? $username;

    $mStmt = $conn->prepare("
        SELECT m.*,
               CASE WHEN m.sender_id = ? THEN 'me' ELSE 'them' END AS direction,
               CASE WHEN m.sender_id = ? THEN ? ELSE ? END AS sender_screenname
        FROM message m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $mStmt->bind_param("iissiiiii",
        $account_id, $account_id,
        $my_screenname, $chat_partner['screenname'],
        $account_id, $active_chat,
        $active_chat, $account_id
    );

    // Fix: bind_param count — recount carefully
    $mStmt->close();

    // Simpler approach: fetch then tag in PHP
    $mStmt2 = $conn->prepare("
        SELECT m.*
        FROM message m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $mStmt2->bind_param("iiii", $account_id, $active_chat, $active_chat, $account_id);
    $mStmt2->execute();
    $raw = $mStmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $mStmt2->close();

    foreach ($raw as $msg) {
        $msg['direction']        = ($msg['sender_id'] == $account_id) ? 'me' : 'them';
        $msg['sender_screenname']= ($msg['sender_id'] == $account_id)
            ? ($myProfile['screenname'] ?? $username)
            : ($chat_partner['screenname'] ?? 'Them');
        $chat_messages[] = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages – Parasocial</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;}
        .navbar{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.1);}
        .navbar-content{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:1.5em;font-weight:bold;}
        .nav-links{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
        .nav-link{color:white;text-decoration:none;padding:8px 16px;border-radius:20px;transition:all .3s;font-size:.95em;}
        .nav-link:hover,.nav-link.active{background:rgba(255,255,255,.25);}
        .logout-btn{background:rgba(255,255,255,.2);border:2px solid white;color:white;padding:8px 20px;border-radius:20px;text-decoration:none;transition:all .3s;font-weight:bold;}
        .logout-btn:hover{background:white;color:#667eea;}

        .msg-layout{max-width:1150px;margin:30px auto;padding:0 20px;display:grid;grid-template-columns:290px 1fr;gap:20px;height:calc(100vh - 130px);}
        .sidebar{background:white;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;display:flex;flex-direction:column;}
        .sidebar-header{padding:16px 20px;border-bottom:1px solid #f0f0f0;font-size:1em;font-weight:bold;color:#333;}
        .convo-list{flex:1;overflow-y:auto;}
        .convo-item{display:flex;align-items:center;gap:12px;padding:13px 16px;text-decoration:none;border-bottom:1px solid #f8f8f8;transition:background .2s;}
        .convo-item:hover,.convo-item.active{background:#f0f2ff;}
        .convo-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:1.1em;flex-shrink:0;}
        .convo-info{flex:1;min-width:0;}
        .convo-name{font-weight:bold;color:#333;font-size:.9em;}
        .convo-preview{color:#999;font-size:.8em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;}
        .badge{background:#667eea;color:white;border-radius:12px;padding:2px 8px;font-size:.73em;font-weight:bold;flex-shrink:0;}
        .empty-sidebar{padding:30px;text-align:center;color:#aaa;font-size:.88em;}

        /* Chat panel */
        .chat-panel{background:white;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.07);display:flex;flex-direction:column;overflow:hidden;}
        .chat-header{padding:16px 22px;border-bottom:1px solid #f0f0f0;font-size:1.05em;font-weight:bold;color:#333;display:flex;align-items:center;gap:12px;}
        .chat-messages{flex:1;overflow-y:auto;padding:18px 20px;display:flex;flex-direction:column;gap:14px;}

        /* Bubble wrapper — used to align left/right */
        .msg-row{display:flex;flex-direction:column;}
        .msg-row.me{align-items:flex-end;}
        .msg-row.them{align-items:flex-start;}

        /* Sender name above bubble */
        .sender-name{font-size:.75em;font-weight:bold;color:#888;margin-bottom:3px;padding:0 4px;}
        .msg-row.me .sender-name{color:#764ba2;}

        .bubble{max-width:62%;padding:11px 15px;border-radius:18px;line-height:1.5;font-size:.93em;word-break:break-word;position:relative;}
        .bubble.me{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-bottom-right-radius:4px;}
        .bubble.them{background:#f1f0f0;color:#333;border-bottom-left-radius:4px;}

        .bubble-meta{display:flex;align-items:center;gap:8px;margin-top:5px;justify-content:flex-end;}
        .bubble-time{font-size:.72em;opacity:.65;}
        .bubble.them .bubble-meta{justify-content:flex-start;}

        /* Delete button inside bubble */
        .del-msg-btn{background:none;border:none;cursor:pointer;padding:0;font-size:.75em;opacity:.55;transition:opacity .2s;color:inherit;line-height:1;}
        .del-msg-btn:hover{opacity:1;}

        .chat-input-area{padding:14px 18px;border-top:1px solid #f0f0f0;display:flex;gap:10px;align-items:center;}
        .chat-input{flex:1;border:2px solid #e0e0e0;border-radius:24px;padding:11px 18px;font-size:.93em;outline:none;transition:border .2s;font-family:inherit;}
        .chat-input:focus{border-color:#667eea;}
        .send-btn{background:linear-gradient(135deg,#667eea,#764ba2);border:none;color:white;width:44px;height:44px;border-radius:50%;cursor:pointer;font-size:1.05em;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .send-btn:hover{opacity:.85;}
        .no-chat{flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#bbb;gap:12px;}
        .no-chat i{font-size:3.5em;}
        @media(max-width:700px){.msg-layout{grid-template-columns:1fr;height:auto;}}
    </style>
</head>
<body>
<div class="navbar">
    <div class="navbar-content">
        <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="find_matches.php" class="nav-link"><i class="fas fa-heart"></i> Find Matches</a>
            <a href="messages.php" class="nav-link active"><i class="fas fa-comments"></i> Messages</a>
            <a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="msg-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header"><i class="fas fa-inbox" style="color:#667eea"></i> Inbox</div>
        <div class="convo-list">
            <?php if (empty($conversations)): ?>
                <div class="empty-sidebar">No conversations yet.<br>Mutual matches can message each other!</div>
            <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                    <a href="messages.php?with=<?php echo $c['account_id']; ?>"
                       class="convo-item <?php echo $active_chat == $c['account_id'] ? 'active' : ''; ?>">
                        <div class="convo-avatar"><?php echo strtoupper(substr($c['screenname'],0,1)); ?></div>
                        <div class="convo-info">
                            <div class="convo-name"><?php echo htmlspecialchars($c['screenname']); ?></div>
                            <div class="convo-preview">
                                <?php echo $c['last_msg']
                                    ? htmlspecialchars(substr($c['last_msg'],0,38))
                                    : '<em>Say hello!</em>'; ?>
                            </div>
                        </div>
                        <?php if ($c['unread'] > 0): ?>
                            <span class="badge"><?php echo $c['unread']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat panel -->
    <div class="chat-panel">
        <?php if ($active_chat && $chat_partner): ?>
            <div class="chat-header">
                <div class="convo-avatar" style="width:36px;height:36px;font-size:.95em;flex-shrink:0;">
                    <?php echo strtoupper(substr($chat_partner['screenname'],0,1)); ?>
                </div>
                <?php echo htmlspecialchars($chat_partner['screenname']); ?>
            </div>

            <div class="chat-messages" id="chatBox">
                <?php if (empty($chat_messages)): ?>
                    <div style="text-align:center;color:#bbb;margin:auto;">No messages yet — say hello!</div>
                <?php else: ?>
                    <?php foreach ($chat_messages as $msg): ?>
                        <div class="msg-row <?php echo $msg['direction']; ?>">
                            <!-- Sender screenname above bubble -->
                            <div class="sender-name">
                                <?php echo htmlspecialchars($msg['sender_screenname']); ?>
                            </div>
                            <div class="bubble <?php echo $msg['direction']; ?>">
                                <?php echo htmlspecialchars($msg['text']); ?>
                                <div class="bubble-meta">
                                    <span class="bubble-time">
                                        <?php echo date('M j, g:i a', strtotime($msg['sent_at'])); ?>
                                    </span>
                                    <!-- Delete button: anyone in the conversation can delete -->
                                    <form method="POST" style="margin:0;display:inline;">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                        <input type="hidden" name="with_id"    value="<?php echo $active_chat; ?>">
                                        <button type="submit" name="delete_message" class="del-msg-btn"
                                                title="Delete message"
                                                onclick="return confirm('Delete this message?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <form method="POST" style="display:flex;flex:1;gap:10px;align-items:center;">
                    <input type="hidden" name="receiver_id" value="<?php echo $active_chat; ?>">
                    <input class="chat-input" type="text" name="text"
                           placeholder="Type a message…" autocomplete="off" required>
                    <button type="submit" name="send_message" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="no-chat">
                <i class="fas fa-comments"></i>
                <p>Select a conversation from your inbox</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const box = document.getElementById('chatBox');
    if (box) box.scrollTop = box.scrollHeight;
</script>
</body>
</html>
