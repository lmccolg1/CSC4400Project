<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php'); exit();
}
require_once 'DBConnect.php';

$username   = $_SESSION['username'] ?? 'User';
$account_id = $_SESSION['account_id'] ?? 0;

$success = '';
$error   = '';

// Load current profile
$stmt = $conn->prepare("SELECT p.* FROM profile p WHERE p.acc_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $screenname = trim($_POST['screenname'] ?? '');
    $summary    = trim($_POST['summary']    ?? '');
    $likes      = trim($_POST['likes']      ?? '');
    $dislikes   = trim($_POST['dislikes']   ?? '');
    $isprivate  = isset($_POST['isprivate']) ? 1 : 0;

    if (empty($screenname)) {
        $error = "Screenname cannot be empty.";
    } else {
        // Check screenname uniqueness (excluding current user)
        $chk = $conn->prepare("SELECT profile_id FROM profile WHERE screenname = ? AND acc_id != ?");
        $chk->bind_param("si", $screenname, $account_id);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "That screenname is already taken.";
        } else {
            if ($profile) {
                $upd = $conn->prepare("UPDATE profile SET screenname=?, summary=?, likes=?, dislikes=?, isprivate=? WHERE acc_id=?");
                $upd->bind_param("sssiii", $screenname, $summary, $likes, $dislikes, $isprivate, $account_id);
                $upd->execute();
                $upd->close();
            } else {
                $ins = $conn->prepare("INSERT INTO profile (acc_id, screenname, summary, likes, dislikes, isprivate) VALUES (?,?,?,?,?,?)");
                $ins->bind_param("issssi", $account_id, $screenname, $summary, $likes, $dislikes, $isprivate);
                $ins->execute();
                $ins->close();
            }
            $success  = "Profile updated successfully!";
            $profile['screenname'] = $screenname;
            $profile['summary']    = $summary;
            $profile['likes']      = $likes;
            $profile['dislikes']   = $dislikes;
            $profile['isprivate']  = $isprivate;
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile – Parasocial</title>
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
        .container{max-width:680px;margin:40px auto;padding:0 20px;}
        .page-title{font-size:1.8em;font-weight:bold;color:#333;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
        .card{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.08);padding:36px;}
        .profile-preview{display:flex;align-items:center;gap:20px;margin-bottom:32px;padding-bottom:28px;border-bottom:1px solid #f0f0f0;}
        .big-avatar{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:white;font-size:2.5em;font-weight:bold;flex-shrink:0;}
        .preview-info h2{margin:0 0 4px;color:#333;}
        .preview-info p{margin:0;color:#888;font-size:.9em;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;font-weight:bold;color:#555;margin-bottom:6px;font-size:.95em;}
        .form-control{width:100%;border:2px solid #e0e0e0;border-radius:10px;padding:12px 14px;font-size:.95em;font-family:inherit;transition:border .2s;outline:none;resize:vertical;}
        .form-control:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);}
        .toggle-row{display:flex;align-items:center;gap:12px;padding:14px;background:#f8f8ff;border-radius:10px;border:2px solid #e8e8f0;}
        .toggle-row label{margin:0;color:#555;font-weight:bold;cursor:pointer;}
        .toggle-row input{width:18px;height:18px;cursor:pointer;accent-color:#667eea;}
        .btn-save{background:linear-gradient(135deg,#667eea,#764ba2);border:none;color:white;padding:14px 28px;border-radius:10px;font-size:1em;font-weight:bold;cursor:pointer;width:100%;margin-top:8px;transition:opacity .2s;}
        .btn-save:hover{opacity:.9;}
        .flash{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:bold;}
        .flash.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
        .flash.error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}
    </style>
</head>
<body>
<div class="navbar">
    <div class="navbar-content">
        <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="find_matches.php" class="nav-link"><i class="fas fa-heart"></i> Find Matches</a>
            <a href="messages.php" class="nav-link"><i class="fas fa-comments"></i> Messages</a>
            <a href="edit_profile.php" class="nav-link active"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-user-edit" style="color:#667eea"></i> Edit Profile</h1>

    <?php if ($success): ?>
        <div class="flash success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="profile-preview">
            <div class="big-avatar"><?php echo strtoupper(substr($profile['screenname'] ?? $username, 0, 1)); ?></div>
            <div class="preview-info">
                <h2><?php echo htmlspecialchars($profile['screenname'] ?? $username); ?></h2>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-signature"></i> Screenname</label>
                <input class="form-control" type="text" name="screenname" value="<?php echo htmlspecialchars($profile['screenname'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> About Me</label>
                <textarea class="form-control" name="summary" rows="4" placeholder="Tell others about yourself…"><?php echo htmlspecialchars($profile['summary'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-thumbs-up"></i> Likes</label>
                <textarea class="form-control" name="likes" rows="3" placeholder="Things you enjoy…"><?php echo htmlspecialchars($profile['likes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-thumbs-down"></i> Dislikes</label>
                <textarea class="form-control" name="dislikes" rows="3" placeholder="Things you're not into…"><?php echo htmlspecialchars($profile['dislikes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <div class="toggle-row">
                    <input type="checkbox" name="isprivate" id="isprivate" value="1" <?php echo ($profile['isprivate'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="isprivate"><i class="fas fa-lock"></i> Make profile private (only matches can see it)</label>
                </div>
            </div>
            <button type="submit" name="save_profile" class="btn-save">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>
</body>
</html>
