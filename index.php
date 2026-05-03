<?php
session_start();

$login_error = "";

// Show timeout / guest-blocked messages
if (isset($_GET['timeout'])) {
    $login_error = "Your session expired due to inactivity. Please log in again.";
}
if (isset($_GET['guest_blocked'])) {
    $login_error = "You need a full account to access that page.";
}

// Handle guest login
if (isset($_POST['guest_login'])) {
    // Guest gets no account_id — protected pages that need a DB user will redirect them
    unset($_SESSION['account_id']);
    $_SESSION['username']      = 'guest#1234';
    $_SESSION['user_type']     = 'guest';
    $_SESSION['logged_in']     = true;
    $_SESSION['last_activity'] = time();
    session_regenerate_id(true); // prevent fixation even for guests
    header('Location: dashboard.php');
    exit();
}

// Handle regular login
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        $conn = new mysqli("localhost", "root", "", "dating_app");

        if ($conn->connect_error) {
            $login_error = "Database connection failed. Please try again later.";
        } else {
            // Use prepared statement — parameterised query prevents SQL injection
            $stmt = $conn->prepare("SELECT account_id, username, password, utype FROM account WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($db_account_id, $db_username, $db_password, $db_utype);
                $stmt->fetch();

                // Support both bcrypt hashes (from hash.php) and legacy plain-text passwords
                $passwordOk = str_starts_with($db_password, '$2y$')
                    ? password_verify($password, $db_password)
                    : ($password === $db_password);

                if ($passwordOk) {
                    // Prevent session fixation: regenerate ID before writing privileged data
                    session_regenerate_id(true);

                    $_SESSION['account_id']    = (int)$db_account_id;
                    $_SESSION['username']      = $db_username;
                    $_SESSION['user_type']     = $db_utype;
                    $_SESSION['logged_in']     = true;
                    $_SESSION['last_activity'] = time();

                    $stmt->close();
                    $conn->close();

                    header('Location: ' . ($db_utype === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
                    exit();
                } else {
                    $login_error = "Invalid username or password.";
                }
            } else {
                $login_error = "Invalid username or password.";
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parasocial | Make Believe You're Making Friends</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; position: relative; overflow: hidden; }
        .hero-pattern { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 80%, white 1px, transparent 1px); background-size: 50px 50px; }
        .hero-content { position: relative; z-index: 1; padding-top: 80px; }
        .logo { font-size: 3em; font-weight: bold; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .logo i { color: #ff6b9d; }
        .tagline { font-size: 1.5em; color: white; margin-top: 10px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); }
        .login-card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; max-width: 450px; margin: 40px auto; }
        .login-title { color: #667eea; font-size: 2em; margin-bottom: 30px; font-weight: bold; }
        .w3-input { border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px; font-size: 1em; transition: all 0.3s; }
        .w3-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 14px; color: white; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: all 0.3s; width: 100%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-guest { background: white; border: 2px solid #667eea; border-radius: 8px; padding: 14px; color: #667eea; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: all 0.3s; width: 100%; }
        .btn-guest:hover { background: #f8f9ff; transform: translateY(-2px); }
        .divider { display: flex; align-items: center; text-align: center; margin: 25px 0; color: #999; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e0e0e0; }
        .divider span { padding: 0 15px; font-size: 0.9em; }
        .alert-info { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .9em; }
    </style>
</head>
<body>
<div class="hero-section">
    <div class="hero-pattern"></div>
    <div class="hero-content">
        <div class="w3-container w3-center">
            <div class="logo"><i class="fas fa-heart"></i> Parasocial</div>
            <p class="tagline">Where meaningless connections spawn</p>
        </div>

        <div class="login-card">
            <h2 class="login-title w3-center">Welcome to Parasocial!</h2>

            <?php if (!empty($login_error)): ?>
                <div style="color:#b00020;text-align:center;margin-bottom:14px;font-weight:bold;background:#fdecea;padding:12px;border-radius:8px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert-info"><i class="fas fa-info-circle"></i> Your account has been deleted.</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="w3-margin-bottom">
                    <label class="w3-text-grey"><b>Username</b></label>
                    <input class="w3-input" type="text" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>
                <div class="w3-margin-bottom">
                    <label class="w3-text-grey"><b>Password</b></label>
                    <input class="w3-input" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                <div class="w3-center w3-margin-top">
                    <a href="forgot_password.php" style="color:#667eea;text-decoration:none;font-size:0.9em;">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
            </form>

            <div class="divider"><span>OR</span></div>

            <form method="POST" action="">
                <button type="submit" name="guest_login" class="btn-guest">
                    <i class="fas fa-user-circle"></i> Continue as Guest
                </button>
            </form>

            <div class="w3-center w3-margin-top">
                <p class="w3-text-grey">Don't have an account?
                    <a href="signup.php" style="color:#667eea;text-decoration:none;font-weight:bold;">Sign Up</a>
                </p>
            </div>
        </div>
    </div>
</div>
<footer class="w3-container w3-padding-32 w3-center w3-light-grey">
    <p>&copy; 2026 Parasocial. All rights reserved.</p>
</footer>
</body>
</html>
