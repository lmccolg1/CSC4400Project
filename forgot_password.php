<?php
session_start();
require_once 'DBConnect.php';

$message = '';
$error = '';

// Handle password recovery request
if (isset($_POST['request_reset'])) {
    $username = $_POST['username'] ?? '';
    
    if (!empty($username)) {
        // Generate a temporary password
        $temp_password = bin2hex(random_bytes(4)); // 8 character temp password
        
        // In a real application, you would:
        // 1. Verify the username exists
        // 2. Send the temp password via email
        // 3. Store it securely (hashed) with an expiration time
        
        // For this demo, we'll just display it
        $_SESSION['temp_password'] = $temp_password;
        $_SESSION['reset_username'] = $username;
        
        $message = "Your temporary password is: <strong>$temp_password</strong><br>Please use this to log in and set a new password.";
    } else {
        $error = "Please enter your username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - HeartConnect</title>
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
        
        .recovery-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }
        
        .recovery-title {
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
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="recovery-card">
        <h2 class="recovery-title"><i class="fas fa-key"></i> Password Recovery</h2>
        <p class="subtitle">Enter your username to receive a temporary password</p>
        
        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="w3-margin-bottom">
                <label class="w3-text-grey"><b><i class="fas fa-user"></i> Username</b></label>
                <input class="w3-input" type="text" name="username" placeholder="Enter your username" required>
            </div>
            
            <button type="submit" name="request_reset" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Request Password Reset
            </button>
        </form>
        
        <?php if ($message): ?>
            <div class="w3-margin-top">
                <a href="reset_password.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none;">
                    <i class="fas fa-arrow-right"></i> Continue to Reset Password
                </a>
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
