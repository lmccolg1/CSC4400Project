<?php
session_start();
require_once 'auth.php';
require_login();
require_once 'DBConnect.php';

$username   = $_SESSION['username'] ?? 'User';
$account_id = $_SESSION['account_id'] ?? 0;

$success = '';
$error   = '';

// Load account data
$stmt = $conn->prepare("SELECT username, security_question FROM account WHERE account_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Change Password ---
if (isset($_POST['change_password'])) {
    $current  = $_POST['current_password']  ?? '';
    $new_pw   = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    $chk = $conn->prepare("SELECT password FROM account WHERE account_id = ?");
    $chk->bind_param("i", $account_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    $storedHash = $row['password'];
    $passwordOk = str_starts_with($storedHash, '$2y$')
        ? password_verify($current, $storedHash)
        : ($current === $storedHash);

    if (!$passwordOk) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pw) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new_pw !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE account SET password = ? WHERE account_id = ?");
        $upd->bind_param("si", $hashed_pw, $account_id);
        $upd->execute();
        $upd->close();
        $success = "Password updated successfully.";
    }
}

// --- Update Security Question ---
if (isset($_POST['update_security'])) {
    $question = trim($_POST['security_question'] ?? '');
    $answer   = strtolower(trim($_POST['security_answer'] ?? ''));
    if (empty($question) || empty($answer)) {
        $error = "Both security question and answer are required.";
    } else {
        $upd = $conn->prepare("UPDATE account SET security_question=?, security_answer=? WHERE account_id=?");
        $upd->bind_param("ssi", $question, $answer, $account_id);
        $upd->execute();
        $upd->close();
        $account['security_question'] = $question;
        $success = "Security question updated.";
    }
}

// --- Delete Account ---
if (isset($_POST['delete_account'])) {
    $confirm_del = $_POST['confirm_delete'] ?? '';
    if ($confirm_del !== $username) {
        $error = "Username confirmation did not match. Account not deleted.";
    } else {
        $del = $conn->prepare("DELETE FROM account WHERE account_id = ?");
        $del->bind_param("i", $account_id);
        $del->execute();
        $del->close();
        session_destroy();
        header('Location: index.php?deleted=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Parasocial</title>
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
        .container{max-width:680px;margin:40px auto;padding:0 20px 60px;}
        .page-title{font-size:1.8em;font-weight:bold;color:#333;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
        .section{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:30px;margin-bottom:24px;}
        .section-title{font-size:1.1em;font-weight:bold;color:#333;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f0f0;display:flex;align-items:center;gap:8px;}
        .section-title i{color:#667eea;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-weight:bold;color:#555;margin-bottom:6px;font-size:.9em;}
        .form-control{width:100%;border:2px solid #e0e0e0;border-radius:10px;padding:12px 14px;font-size:.9em;font-family:inherit;transition:border .2s;outline:none;}
        .form-control:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);}
        select.form-control{background:white;}
        .btn{border:none;border-radius:10px;padding:12px 22px;font-size:.95em;font-weight:bold;cursor:pointer;transition:all .2s;}
        .btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;width:100%;}
        .btn-primary:hover{opacity:.9;}
        .btn-danger{background:#b00020;color:white;width:100%;}
        .btn-danger:hover{background:#8c0019;}
        .flash{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:bold;}
        .flash.success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
        .flash.error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}
        .danger-zone{border:2px solid #f5c6cb;}
        .danger-zone .section-title{color:#b00020;}
        .danger-zone .section-title i{color:#b00020;}
        .info-text{font-size:.85em;color:#888;margin-top:6px;}
    </style>
</head>
<body>
<div class="navbar">
    <div class="navbar-content">
        <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="find_matches.php" class="nav-link"><i class="fas fa-heart"></i> Find Matches</a>
            <a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="settings.php" class="nav-link active"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-cog" style="color:#667eea"></i> Settings</h1>

    <?php if ($success): ?>
        <div class="flash success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Account Info -->
    <div class="section">
        <div class="section-title"><i class="fas fa-user"></i> Account Info</div>
        <div class="form-group">
            <label>Username</label>
            <input class="form-control" type="text" value="<?php echo htmlspecialchars($username); ?>" disabled style="background:#f8f8f8;color:#888;">
            <p class="info-text">Your username cannot be changed.</p>
        </div>
    </div>

    <!-- Change Password -->
    <div class="section">
        <div class="section-title"><i class="fas fa-lock"></i> Change Password</div>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input class="form-control" type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input class="form-control" type="password" name="new_password" minlength="8" required>
                <p class="info-text">Must be at least 8 characters.</p>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input class="form-control" type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-primary">
                <i class="fas fa-key"></i> Update Password
            </button>
        </form>
    </div>

    <!-- Security Question -->
    <div class="section">
        <div class="section-title"><i class="fas fa-shield-alt"></i> Security Question</div>
        <?php if (!empty($account['security_question'])): ?>
            <p style="color:#555;margin-bottom:16px;">Current question: <strong><?php echo htmlspecialchars($account['security_question']); ?></strong></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>New Security Question</label>
                <select class="form-control" name="security_question" required>
                    <option value="">-- Select a question --</option>
                    <?php
                    $questions = [
                        "What was the name of your first pet?",
                        "What is your mother's maiden name?",
                        "What city were you born in?",
                        "What was the name of your elementary school?",
                        "What is the name of your childhood best friend?",
                        "What was the make of your first car?"
                    ];
                    foreach ($questions as $q):
                    ?>
                        <option value="<?php echo htmlspecialchars($q); ?>" <?php echo ($account['security_question'] === $q) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($q); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Answer</label>
                <input class="form-control" type="text" name="security_answer" placeholder="Your answer (case-insensitive)" required>
            </div>
            <button type="submit" name="update_security" class="btn btn-primary">
                <i class="fas fa-shield-alt"></i> Update Security Question
            </button>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="section danger-zone">
        <div class="section-title"><i class="fas fa-exclamation-triangle"></i> Danger Zone</div>
        <p style="color:#721c24;margin-bottom:16px;font-size:.95em;">Deleting your account is permanent and cannot be undone. All your matches and messages will be lost.</p>
        <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
            <div class="form-group">
                <label>Type your username to confirm: <strong><?php echo htmlspecialchars($username); ?></strong></label>
                <input class="form-control" type="text" name="confirm_delete" placeholder="Enter your username" required style="border-color:#f5c6cb;">
            </div>
            <button type="submit" name="delete_account" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Delete My Account
            </button>
        </form>
    </div>
</div>
</body>
</html>
