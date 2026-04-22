<?php
session_start();
require_once 'DBConnect.php';

$message = '';
$error   = '';

// Must have passed the security question check
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'] || !isset($_SESSION['reset_account_id'])) {
    header('Location: forgot_password.php');
    exit();
}

if (isset($_POST['reset_password'])) {
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password in database (stored plain-text to match existing app convention)
        $stmt = $conn->prepare("UPDATE account SET password = ? WHERE account_id = ?");
        $stmt->bind_param("si", $new_password, $_SESSION['reset_account_id']);

        if ($stmt->execute()) {
            $message = "Password successfully reset! You can now log in with your new password.";
            // Clear all reset session variables
            unset($_SESSION['reset_username'], $_SESSION['reset_account_id'], $_SESSION['reset_security_question'], $_SESSION['reset_verified']);
            header("refresh:2;url=index.php");
        } else {
            $error = "Database error. Could not update password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Parasocial</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }
        .reset-title {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .w3-input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        .w3-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 14px;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .password-requirements { font-size: 0.9em; color: #666; margin-top: 5px; }
        .w3-margin-bottom { margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="reset-card">
    <h2 class="reset-title"><i class="fas fa-lock"></i> Reset Password</h2>
    <p class="subtitle">Create your new password</p>

    <?php if ($message): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <br><small>Redirecting to login page...</small>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!$message): ?>
        <form method="POST" action="">
            <div class="w3-margin-bottom">
                <label><b><i class="fas fa-lock"></i> New Password</b></label>
                <input class="w3-input" type="password" name="new_password" placeholder="Enter new password" required>
                <p class="password-requirements"><i class="fas fa-info-circle"></i> Must be at least 8 characters long.</p>
            </div>
            <div class="w3-margin-bottom">
                <label><b><i class="fas fa-lock"></i> Confirm New Password</b></label>
                <input class="w3-input" type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit" name="reset_password" class="btn-primary">
                <i class="fas fa-check"></i> Reset Password
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
