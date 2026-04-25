<?php
session_start();

$login_error = "";

// Handle guest login
if (isset($_POST['guest_login'])) {
    $_SESSION['username'] = 'guest#1234';
    $_SESSION['user_type'] = 'guest';
    $_SESSION['logged_in'] = true;
    header('Location: dashboard.php');
    exit();
}
//
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
            $stmt = $conn->prepare("SELECT account_id, username, password, utype FROM account WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($db_account_id, $db_username, $db_password, $db_utype);
                $_SESSION['account_id'] = $db_account_id;
                $stmt->fetch();

                if ($password === $db_password) {
                    $_SESSION['username'] = $db_username;
                    $_SESSION['user_type'] = $db_utype;
                    $_SESSION['logged_in'] = true;

                    $stmt->close();
                    $conn->close();

                    if ($db_utype === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
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
        <title>Para social- Find Your Perfect Match</title>
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
                    <div class="logo">
                        <i class="fas fa-heart"></i> Para social

                    </div>
                    <p class="tagline">Where meaningful connections begin</p>
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

                        <?php if (!empty($login_error)): ?>
                            <div style="color:#b00020; text-align:center; margin-top:12px; font-weight:bold;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($login_error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="w3-center w3-margin-top">
                            <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 0.9em;">
                                <i class="fas fa-key"></i> Forgot Password?
                            </a>
                        </div>
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

        <!-- Features Section -->
        <div class="features">
            <div class="w3-container">
                <h2 class="w3-center" style="font-size: 2.5em; color: #333; margin-bottom: 50px;">Why Choose HeartConnect?</h2>

                <div class="w3-row-padding">
                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="feature-title">Smart Matching</h3>
                            <p class="feature-text">Our advanced algorithm connects you with compatible people based on your interests, values, and preferences.</p>
                        </div>
                    </div>

                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 class="feature-title">Safe & Secure</h3>
                            <p class="feature-text">Your privacy matters. We use industry-leading security measures to protect your personal information.</p>
                        </div>
                    </div>

                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 class="feature-title">Real Conversations</h3>
                            <p class="feature-text">Start meaningful conversations with verified profiles and build genuine connections.</p>
                        </div>
                    </div>
                </div>

                <div class="w3-row-padding">
                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <h3 class="feature-title">Video Dating</h3>
                            <p class="feature-text">Take your connection to the next level with secure video chat features.</p>
                        </div>
                    </div>

                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3 class="feature-title">Mobile First</h3>
                            <p class="feature-text">Stay connected on the go with our fully responsive design that works on any device.</p>
                        </div>
                    </div>

                    <div class="w3-col l4 m6 s12 w3-margin-bottom">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3 class="feature-title">Success Stories</h3>
                            <p class="feature-text">Join thousands of happy couples who found their perfect match through HeartConnect.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="w3-container">
                <div class="w3-row-padding">
                    <div class="w3-col l3 m6 s12">
                        <div class="stat-box">
                            <div class="stat-number">2M+</div>
                            <div class="stat-label">Active Members</div>
                        </div>
                    </div>
                    <div class="w3-col l3 m6 s12">
                        <div class="stat-box">
                            <div class="stat-number">500K+</div>
                            <div class="stat-label">Matches Made</div>
                        </div>
                    </div>
                    <div class="w3-col l3 m6 s12">
                        <div class="stat-box">
                            <div class="stat-number">50K+</div>
                            <div class="stat-label">Success Stories</div>
                        </div>
                    </div>
                    <div class="w3-col l3 m6 s12">
                        <div class="stat-box">
                            <div class="stat-number">4.8/5</div>
                            <div class="stat-label">User Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="w3-container w3-padding-32 w3-center w3-light-grey">
            <p>&copy; 2026 HeartConnect. All rights reserved.</p>
            <p>
                <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Privacy Policy</a> |
                <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Terms of Service</a> |
                <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px;">Contact Us</a>
            </p>
        </footer>
    </body>
</html>
