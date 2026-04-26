<?php
session_start();

// Make sure user is logged in and is an admin
if (!isset($_SESSION['account_id']) || !isset($_SESSION['utype']) || $_SESSION['utype'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = "";

// Connect to database
$conn = new mysqli("localhost", "root", "", "dating_app");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle approve / deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'], $_POST['account_id'])) {
    $request_id = (int) $_POST['request_id'];
    $account_id = (int) $_POST['account_id'];
    $action = $_POST['action'];

    $conn->begin_transaction();

    try {
        if ($action === 'approve') {
            // Promote account to admin
            $stmt1 = $conn->prepare("
                UPDATE account
                SET utype = 'admin'
                WHERE account_id = ?
            ");
            $stmt1->bind_param("i", $account_id);

            if (!$stmt1->execute()) {
                throw new Exception("Failed to approve admin request.");
            }
            $stmt1->close();

            // Mark request approved
            $stmt2 = $conn->prepare("
                UPDATE admin_requests
                SET status = 'approved'
                WHERE request_id = ?
            ");
            $stmt2->bind_param("i", $request_id);

            if (!$stmt2->execute()) {
                throw new Exception("Failed to update request status.");
            }
            $stmt2->close();

            $message = "Admin request approved.";
        } elseif ($action === 'deny') {
            // Mark request denied
            $stmt = $conn->prepare("
                UPDATE admin_requests
                SET status = 'denied'
                WHERE request_id = ?
            ");
            $stmt->bind_param("i", $request_id);

            if (!$stmt->execute()) {
                throw new Exception("Failed to deny admin request.");
            }
            $stmt->close();

            $message = "Admin request denied.";
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
}

// Pull all pending admin requests
$stmt = $conn->prepare("
    SELECT 
        ar.request_id,
        ar.account_id,
        ar.created_at,
        a.username,
        p.screenname,
        p.summary
    FROM admin_requests ar
    INNER JOIN account a ON ar.account_id = a.account_id
    INNER JOIN profile p ON a.account_id = p.acc_id
    WHERE ar.status = 'pending'
    ORDER BY ar.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
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

        .card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 900px;
            margin: 80px auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .title {
            color: #667eea;
            font-size: 2em;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 30px;
        }

        .message {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .notification {
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafafa;
        }

        .notification h3 {
            margin-top: 0;
            color: #333;
        }

        .notification p {
            margin: 8px 0;
            color: #444;
        }

        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn {
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .approve-btn {
            background: #2e7d32;
            color: white;
        }

        .deny-btn {
            background: #c62828;
            color: white;
        }

        .logout-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #666;
            padding: 25px 0;
        }
    </style>
</head>
<body>
    <div class="brand">Parasocial</div>

    <div class="card">
        <h1 class="title">Admin Dashboard</h1>
        <p class="subtitle">Pending administrator requests</p>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($requests) === 0): ?>
            <div class="empty">There are no pending admin requests.</div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="notification">
                    <h3><?php echo htmlspecialchars($request['screenname']); ?></h3>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($request['username']); ?></p>
                    <p><strong>Summary:</strong> <?php echo htmlspecialchars($request['summary']); ?></p>
                    <p><strong>Requested:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>

                    <div class="actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                            <input type="hidden" name="account_id" value="<?php echo (int)$request['account_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn approve-btn">Approve</button>
                        </form>

                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                            <input type="hidden" name="account_id" value="<?php echo (int)$request['account_id']; ?>">
                            <input type="hidden" name="action" value="deny">
                            <button type="submit" class="btn deny-btn">Deny</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a class="logout-link" href="logout.php">Log out</a>
    </div>
</body>
</html>