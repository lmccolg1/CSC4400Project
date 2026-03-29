<?php
session_start();
 
// Handle guest login
if (isset($_POST['guest_login'])) {
    $_SESSION['username'] = 'guest#1234';
    $_SESSION['user_type'] = 'guest';
    $_SESSION['logged_in'] = true;
    header('Location: dashboard.php'); // Redirect to dashboard after login
    exit();
}
 
// Handle regular login (if needed)
if (isset($_POST['login'])) {
    // Add your login logic here
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // For now, just a placeholder
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dating app</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 20% 50%, white 1px, transparent 1px),
                radial-gradient(circle at 80% 80%, white 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            padding-top: 80px;
        }
        
        .logo {
            font-size: 3em;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .logo i {
            color: #ff6b9d;
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        
      
    
        
        .tagline {
            font-size: 1.5em;
            color: white;
            margin-top: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            margin: 40px auto;
        }
        
        .login-title {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 30px;
            font-weight: bold;
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
        
        .btn-guest {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 14px;
            color: #667eea;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-guest:hover {
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #999;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .divider span {
            padding: 0 15px;
            font-size: 0.9em;
        }
        
        .features {
            padding: 80px 20px;
            background: #f8f9fa;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .feature-icon {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .feature-text {
            color: #666;
            line-height: 1.6;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 20px;
            color: white;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.2em;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-pattern"></div>
        <div class="hero-content">
            <div class="w3-container w3-center">
                <p class="tagline"></p>
            </div>
            
            <!-- Login Card -->
            <div class="login-card">
                <h2 class="login-title w3-center">Welcome Back</h2>
                
                <form method="POST" action="">
                    <div class="w3-margin-bottom">
                        <label class="w3-text-grey"><b>Username</b></label>
                        <input class="w3-input" type="text" name="username" placeholder="Enter your username" required>
                    </div>
                    
                    <div class="w3-margin-bottom">
                        <label class="w3-text-grey"><b>Password</b></label>
                        <input class="w3-input" type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <!-- Guest Login Button -->
                <form method="POST" action="">
                    <button type="submit" name="guest_login" class="btn-guest">
                        <i class="fas fa-user-circle"></i> Continue as Guest #1234
                    </button>
                </form>
                
                <div class="w3-center w3-margin-top">
                    <p class="w3-text-grey">Don't have an account? <a href="signup.php" style="color: #667eea; text-decoration: none; font-weight: bold;">Sign Up</a></p>
                </div>
            </div>
        </div>
    </div>
    
   
    
    <!-- Footer -->
    <footer class="w3-container w3-padding-32 w3-center w3-light-grey">
        <p>&copy; 2026 . All rights reserved.</p>
        <p>
            <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Privacy Policy</a> |
            <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Terms of Service</a> |
            <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Contact Us</a>
        </p>
    </footer>
</body>
</html>