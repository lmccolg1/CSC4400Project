<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php'); exit();
}
require_once 'DBConnect.php';

$username   = $_SESSION['username'] ?? 'User';
$account_id = $_SESSION['account_id'] ?? 0;

$message = '';
$error   = '';

// Handle Like / Pass actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['target_id'])) {
    $target_id = (int)$_POST['target_id'];
    $action    = $_POST['action']; // 'like' or 'pass'

    if ($action === 'like') {
        // Check if they already liked us back → it's a match
        $chk = $conn->prepare("SELECT match_id FROM matches WHERE user1_id = ? AND user2_id = ? AND status = 'liked'");
        $chk->bind_param("ii", $target_id, $account_id);
        $chk->execute();
        $chk->store_result();
        $mutual = $chk->num_rows > 0;
        $chk->close();

        $status = $mutual ? 'matched' : 'liked';

        // Insert our like
        $ins = $conn->prepare("INSERT IGNORE INTO matches (user1_id, user2_id, status) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $account_id, $target_id, $status);
        $ins->execute();
        $ins->close();

        if ($mutual) {
            // Update the other record to matched too
            $upd = $conn->prepare("UPDATE matches SET status = 'matched' WHERE user1_id = ? AND user2_id = ?");
            $upd->bind_param("ii", $target_id, $account_id);
            $upd->execute();
            $upd->close();
            $message = "It's a match! 🎉";
        } else {
            $message = "Like sent!";
        }
    } elseif ($action === 'pass') {
        $ins = $conn->prepare("INSERT IGNORE INTO matches (user1_id, user2_id, status) VALUES (?, ?, 'passed')");
        $ins->bind_param("ii", $account_id, $target_id);
        $ins->execute();
        $ins->close();
    }
}

// Fetch profiles we haven't seen yet (not liked/passed/matched)
$sql = "
    SELECT a.account_id, p.screenname, p.summary, p.likes, p.dislikes
    FROM account a
    JOIN profile p ON p.acc_id = a.account_id
    WHERE a.account_id != ?
      AND a.utype != 'admin'
      AND a.account_id NOT IN (
          SELECT user2_id FROM matches WHERE user1_id = ?
      )
    ORDER BY RAND()
    LIMIT 10
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $account_id, $account_id);
$stmt->execute();
$result  = $stmt->get_result();
$profiles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Matches – Parasocial</title>
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
        .container{max-width:700px;margin:40px auto;padding:0 20px;}
        .page-title{font-size:1.8em;font-weight:bold;color:#333;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
        .flash{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:bold;font-size:1.05em;}
        .flash.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
        .flash.info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;}
        .profile-card{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.08);padding:32px;margin-bottom:24px;transition:transform .2s;}
        .profile-card:hover{transform:translateY(-3px);}
        .profile-header{display:flex;align-items:center;gap:18px;margin-bottom:20px;}
        .avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:white;font-size:2em;font-weight:bold;flex-shrink:0;}
        .profile-name{font-size:1.5em;font-weight:bold;color:#333;}
        .profile-section{margin-bottom:12px;}
        .profile-section label{font-size:.85em;text-transform:uppercase;letter-spacing:.05em;color:#888;font-weight:bold;}
        .profile-section p{color:#444;margin:4px 0 0;line-height:1.5;}
        .action-row{display:flex;gap:12px;margin-top:24px;}
        .btn-pass{flex:1;padding:14px;border-radius:10px;border:2px solid #e0e0e0;background:white;color:#666;font-size:1em;font-weight:bold;cursor:pointer;transition:all .3s;}
        .btn-pass:hover{border-color:#b00020;color:#b00020;}
        .btn-like{flex:1;padding:14px;border-radius:10px;border:none;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-size:1em;font-weight:bold;cursor:pointer;transition:all .3s;}
        .btn-like:hover{opacity:.9;transform:translateY(-1px);}
        .empty-state{text-align:center;padding:60px 20px;background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.08);}
        .empty-icon{font-size:4em;margin-bottom:16px;color:#ccc;}
        .empty-state h3{color:#555;margin-bottom:8px;}
        .empty-state p{color:#888;}
    </style>
</head>
<body>
<div class="navbar">
    <div class="navbar-content">
        <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="find_matches.php" class="nav-link active"><i class="fas fa-heart"></i> Find Matches</a>
            <a href="messages.php" class="nav-link"><i class="fas fa-comments"></i> Messages</a>
            <a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-heart" style="color:#667eea"></i> Find Matches</h1>

    <?php if ($message): ?>
        <div class="flash success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (empty($profiles)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-search-heart"></i></div>
            <h3>You've seen everyone!</h3>
            <p>Check back later for new members, or see your matches in Messages.</p>
        </div>
    <?php else: ?>
        <?php foreach ($profiles as $p): ?>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar"><?php echo strtoupper(substr($p['screenname'],0,1)); ?></div>
                    <div class="profile-name"><?php echo htmlspecialchars($p['screenname']); ?></div>
                </div>
                <?php if (!empty($p['summary'])): ?>
                <div class="profile-section">
                    <label>About</label>
                    <p><?php echo htmlspecialchars($p['summary']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($p['likes'])): ?>
                <div class="profile-section">
                    <label><i class="fas fa-thumbs-up"></i> Likes</label>
                    <p><?php echo htmlspecialchars($p['likes']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($p['dislikes'])): ?>
                <div class="profile-section">
                    <label><i class="fas fa-thumbs-down"></i> Dislikes</label>
                    <p><?php echo htmlspecialchars($p['dislikes']); ?></p>
                </div>
                <?php endif; ?>
                <div class="action-row">
                    <form method="POST">
                        <input type="hidden" name="target_id" value="<?php echo $p['account_id']; ?>">
                        <input type="hidden" name="action" value="pass">
                        <button type="submit" class="btn-pass"><i class="fas fa-times"></i> Pass</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="target_id" value="<?php echo $p['account_id']; ?>">
                        <input type="hidden" name="action" value="like">
                        <button type="submit" class="btn-like"><i class="fas fa-heart"></i> Like</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
