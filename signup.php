<?php //
session_start();

$error = "";
$success = "";

// Connect to database
$conn = new mysqli("localhost", "root", "", "dating_app");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer   = strtolower(trim($_POST['security_answer'] ?? ''));

    $screenname = trim($_POST['screenname'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $likes = trim($_POST['likes'] ?? '');
    $dislikes = trim($_POST['dislikes'] ?? '');
    $isprivate = isset($_POST['isprivate']) ? 1 : 0;

    // New: admin checkbox
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $utype = $isAdmin ? 'admin' : 'user';

    // Validation
    if (
        empty($username) ||
        empty($password) ||
        empty($confirm) ||
        empty($screenname) ||
        empty($summary) ||
        empty($likes) ||
        empty($dislikes) ||
        empty($security_question) ||
        empty($security_answer)
    ) {
        $error = "All fields except privacy setting are required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Extra admin-only password rule
        if ($isAdmin) {
            preg_match_all('/[^a-zA-Z]/', $password, $matches);
            $nonLetterCount = count($matches[0]);

            
        }

        if (empty($error)) {
            // Check if username already exists
            $checkUser = $conn->prepare("SELECT account_id FROM account WHERE username = ?");
            $checkUser->bind_param("s", $username);
            $checkUser->execute();
            $checkUser->store_result();

            if ($checkUser->num_rows > 0) {
                $error = "Username already taken.";
            } else {
                // Check if screenname already exists
                $checkScreen = $conn->prepare("SELECT profile_id FROM profile WHERE screenname = ?");
                $checkScreen->bind_param("s", $screenname);
                $checkScreen->execute();
                $checkScreen->store_result();

                if ($checkScreen->num_rows > 0) {
                    $error = "Screenname already taken.";
                } else {
                    $conn->begin_transaction();

                    try {
                        // Insert account
                        $stmtAccount = $conn->prepare("
                            INSERT INTO account (username, password, utype, isbot, security_question, security_answer)
                            VALUES (?, ?, ?, 0, ?, ?)
                        ");
                        $stmtAccount->bind_param("sssss", $username, $password, $utype, $security_question, $security_answer);

                        if (!$stmtAccount->execute()) {
                            throw new Exception("Failed to create account.");
                        }

                        $account_id = $conn->insert_id;
                        $stmtAccount->close();

                        // Insert profile
                        $stmtProfile = $conn->prepare("
                            INSERT INTO profile (acc_id, screenname, summary, likes, dislikes, isprivate)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmtProfile->bind_param(
                            "issssi",
                            $account_id,
                            $screenname,
                            $summary,
                            $likes,
                            $dislikes,
                            $isprivate
                        );

                        if (!$stmtProfile->execute()) {
                            throw new Exception("Failed to create profile.");
                        }

                        $stmtProfile->close();

                        $conn->commit();

                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = $e->getMessage();
                    }
                }

                $checkScreen->close();
            }

            $checkUser->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 560px;
            margin: 50px auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .title {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
        }

        .w3-input,
        .w3-textarea {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
        }
		
		.brand {
			position: fixed;
			top: 20px;
			left: 50px;
			color: white;
			font-size: 3em;
			font-weight: bold;
			letter-spacing: 1px;
			z-index: 1000;
			text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
		}
		
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.95;
        }

        .error {
            color: #b00020;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }

        .success {
            color: green;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }

        .checkbox-row {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .helper-text {
            color: #666;
            font-size: 0.95em;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<div class="brand">Parasocial</div>
<div class="card">
    <h2 class="title">Create Account</h2>

    <form method="POST">
        <div class="w3-margin-bottom">
            <label><b>Username</b></label>
            <input class="w3-input" type="text" name="username" required>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Password</b></label>
            <input class="w3-input" type="password" name="password" minlength="8" required>
            <p class="helper-text">
                Password must be at least 8 characters. Admin passwords must also include at least 2 non-letter characters.
            </p>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Confirm Password</b></label>
            <input class="w3-input" type="password" name="confirm_password" minlength="8" required>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Screenname</b></label>
            <input class="w3-input" type="text" name="screenname" required>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Summary</b></label>
            <textarea class="w3-input w3-textarea" name="summary" rows="3" required></textarea>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Likes</b></label>
            <textarea class="w3-input w3-textarea" name="likes" rows="3" required></textarea>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Dislikes</b></label>
            <textarea class="w3-input w3-textarea" name="dislikes" rows="3" required></textarea>
        </div>

        <div class="checkbox-row">
            <label>
                <input type="checkbox" name="isprivate" value="1">
                Make profile private
            </label>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Security Question</b></label>
            <select class="w3-input" name="security_question" required style="border:2px solid #e0e0e0; border-radius:8px; padding:12px;">
                <option value="">-- Select a security question --</option>
                <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                <option value="What city were you born in?">What city were you born in?</option>
                <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
                <option value="What is the name of your childhood best friend?">What is the name of your childhood best friend?</option>
                <option value="What was the make of your first car?">What was the make of your first car?</option>
            </select>
        </div>

        <div class="w3-margin-bottom">
            <label><b>Security Answer</b></label>
            <input class="w3-input" type="text" name="security_answer" placeholder="Your answer (case-insensitive)" required>
            <p class="helper-text">This will be used to verify your identity if you forget your password.</p>
        </div>

        <div class="checkbox-row">
            <label>
                <input type="checkbox" name="is_admin" value="1">
                Register as admin
            </label>
        </div>

        <button type="submit" name="register" class="btn">
            Register
        </button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <div class="w3-center w3-margin-top">
        <p>Already have an account?
            <a href="index.php" style="color:#667eea;">Login</a>
        </p>
    </div>
</div>

</body>
</html>