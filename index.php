<?php
session_start();

$error = "";

// Database connection
$conn = new mysqli("localhost", "root", "", "dating_app");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle guest login
if (isset($_POST['guest_login'])) {
    $_SESSION['account_id'] = 0;
    $_SESSION['username'] = 'guest#1234';
    $_SESSION['utype'] = 'guest';
    $_SESSION['isbot'] = 0;
    $_SESSION['logged_in'] = true;

    header('Location: dashboard.php');
    exit();
}

// Handle regular login
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Both username and password are required.";
    } else {
        $stmt = $conn->prepare("
            SELECT account_id, username, password, utype, isbot
            FROM account
            WHERE username = ?
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Plain-text comparison for now because schema sample stores plain text
            if ($password === $row['password']) {
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['utype'] = $row['utype'];
                $_SESSION['isbot'] = $row['isbot'];
                $_SESSION['logged_in'] = true;

                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
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
    <title>Parasocial</title>
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

        .error-message {
            color: #b00020;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-pattern"></div>
        <div class="hero-content">
            <div class="w3-container w3-center">
                <p class="tagline"></p>
            </div>

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

                <?php if (!empty($error)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <div class="divider">
                    <span>OR</span>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="guest_login" class="btn-guest">
                        <i class="fas fa-user-circle"></i> Continue as Guest #1234
                    </button>
                </form>

                <div class="w3-center w3-margin-top">
                    <p class="w3-text-grey">
                        Don't have an account?
                        <a href="signup.php" style="color: #667eea; text-decoration: none; font-weight: bold;">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="w3-container w3-padding-32 w3-center w3-light-grey">
        <p>&copy; 2026. All rights reserved.</p>
    </footer>
</body>
</html>