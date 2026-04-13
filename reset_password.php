<?php
session_start();
require_once 'DBConnect.php';

$message = '';
$error = '';

// Check if user has a temp password
if (!isset($_SESSION['temp_password']) || !isset($_SESSION['reset_username'])) {
    header('Location: forgot_password.php');
    exit();
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $temp_password = $_POST['temp_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify temporary password
    if ($temp_password !== $_SESSION['temp_password']) {
        $error = "Invalid temporary password.";
    } elseif (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // In a real application, you would:
        // 1. Hash the password
        // 2. Update the database
        // 3. Clear the temporary password
        
        // For demo purposes:
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Simulate database update
        // UPDATE account SET password = '$hashed_password' WHERE username = '{$_SESSION['reset_username']}'
        
        $message = "Password successfully reset! You can now log in with your new password.";
        
        // Clear session variables
        unset($_SESSION['temp_password']);
        unset($_SESSION['reset_username']);
        
        // Redirect after 2 seconds
        header("refresh:2;url=index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - HeartConnect</title>
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
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .w3-input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            font-size: 1em;
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
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
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
        
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .password-requirements {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2 class="reset-title"><i class="fas fa-lock"></i> Reset Password</h2>
        <p class="subtitle">Create your new password</p>
        
        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <br><small>Redirecting to login page...</small>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> Please enter the temporary password you received and create a new password.
            </div>
            
            <form method="POST" action="">
                <div class="w3-margin-bottom">
                    <label class="w3-text-grey"><b><i class="fas fa-key"></i> Temporary Password</b></label>
                    <input class="w3-input" type="text" name="temp_password" placeholder="Enter temporary password" required>
                </div>
                
                <div class="w3-margin-bottom">
                    <label class="w3-text-grey"><b><i class="fas fa-lock"></i> New Password</b></label>
                    <input class="w3-input" type="password" name="new_password" placeholder="Enter new password" required>
                    <p class="password-requirements">
                        <i class="fas fa-info-circle"></i> Must be at least 6 characters long
                    </p>
                </div>
                
                <div class="w3-margin-bottom">
                    <label class="w3-text-grey"><b><i class="fas fa-lock"></i> Confirm New Password</b></label>
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
