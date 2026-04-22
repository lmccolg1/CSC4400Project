<?php
session_start();
require_once 'DBConnect.php';

$message = '';
$error   = '';

// Step 1 — User submits their username; we look up their security question
if (isset($_POST['lookup_user'])) {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        $error = "Please enter your username.";
    } else {
        $stmt = $conn->prepare("SELECT account_id, security_question FROM account WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with that username.";
        } else {
            $row = $result->fetch_assoc();
            if (empty($row['security_question'])) {
                $error = "This account does not have a security question set up. Please contact support.";
            } else {
                $_SESSION['reset_username']          = $username;
                $_SESSION['reset_account_id']        = $row['account_id'];
                $_SESSION['reset_security_question'] = $row['security_question'];
                unset($_SESSION['reset_verified']);
            }
        }
        $stmt->close();
    }
}

// Step 2 — User answers the security question
if (isset($_POST['verify_answer'])) {
    $answer = strtolower(trim($_POST['security_answer'] ?? ''));

    if (empty($answer)) {
        $error = "Please provide an answer.";
    } elseif (!isset($_SESSION['reset_account_id'])) {
        $error = "Session expired. Please start over.";
        unset($_SESSION['reset_username'], $_SESSION['reset_account_id'], $_SESSION['reset_security_question']);
    } else {
        $stmt = $conn->prepare("SELECT security_answer FROM account WHERE account_id = ?");
        $stmt->bind_param("i", $_SESSION['reset_account_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();

        if (strtolower(trim($row['security_answer'])) !== $answer) {
            $error = "Incorrect answer. Please try again.";
        } else {
            $_SESSION['reset_verified'] = true;
            header('Location: reset_password.php');
            exit();
        }
    }
}

// Clear session if user wants to start over
if (isset($_GET['restart'])) {
    unset($_SESSION['reset_username'], $_SESSION['reset_account_id'], $_SESSION['reset_security_question'], $_SESSION['reset_verified']);
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - Parasocial</title>
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
            font-weight: bold;
        }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #667eea; text-decoration: none; font-weight: bold; }
        .back-link a:hover { text-decoration: underline; }
        .w3-margin-bottom { margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="recovery-card">
    <h2 class="recovery-title"><i class="fas fa-key"></i> Password Recovery</h2>

    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['reset_security_question'])): ?>
        <!-- STEP 1: Enter username -->
        <p class="subtitle">Enter your username to get your security question.</p>
        <form method="POST" action="">
            <div class="w3-margin-bottom">
                <label><b><i class="fas fa-user"></i> Username</b></label>
                <input class="w3-input" type="text" name="username" placeholder="Enter your username" required>
            </div>
            <button type="submit" name="lookup_user" class="btn-primary">
                <i class="fas fa-search"></i> Find My Account
            </button>
        </form>

    <?php else: ?>
        <!-- STEP 2: Answer security question -->
        <p class="subtitle">Answer your security question to verify your identity.</p>
        <div class="info-box">
            <i class="fas fa-question-circle"></i>
            <?php echo htmlspecialchars($_SESSION['reset_security_question']); ?>
        </div>
        <form method="POST" action="">
            <div class="w3-margin-bottom">
                <label><b><i class="fas fa-lock"></i> Your Answer</b></label>
                <input class="w3-input" type="text" name="security_answer" placeholder="Enter your answer" required autocomplete="off">
            </div>
            <button type="submit" name="verify_answer" class="btn-primary">
                <i class="fas fa-check"></i> Verify Answer
            </button>
        </form>
        <div class="back-link" style="margin-top:12px;">
            <a href="forgot_password.php?restart=1"><i class="fas fa-redo"></i> Try a different username</a>
        </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
</body>
</html>
