<?php
session_start();
require_once __DIR__ . '/DBConnect.php';

if (!isset($conn) || $conn->connect_error) {
    die('Database connection failed.');
}


// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';
$user_type = $_SESSION['user_type'] ?? 'user';

// Redirect to user dashboard if not admin
if ($user_type !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$account_id = (int)($_SESSION['account_id'] ?? 0);
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_request_action'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['admin_request_action'];

    if ($request_id <= 0 || !in_array($action, ['approved', 'denied'], true)) {
        $error = 'Invalid admin request action.';
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                SELECT account_id
                FROM admin_requests
                WHERE request_id = ? AND status = 'pending'
                FOR UPDATE
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            $stmt->close();

            if (!$request) {
                throw new Exception('Admin request not found or already handled.');
            }

            $requested_account_id = (int)$request['account_id'];

            $updateRequest = $conn->prepare("
                UPDATE admin_requests
                SET status = ?
                WHERE request_id = ?
            ");
            $updateRequest->bind_param("si", $action, $request_id);
            $updateRequest->execute();
            $updateRequest->close();

            if ($action === 'approved') {
                $updateAccount = $conn->prepare("
                    UPDATE account
                    SET utype = 'admin'
                    WHERE account_id = ?
                ");
                $updateAccount->bind_param("i", $requested_account_id);
                $updateAccount->execute();
                $updateAccount->close();
            }

            $conn->commit();
            $notice = $action === 'approved'
                ? 'Admin request approved.'
                : 'Admin request denied.';
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
if ($account_id <= 0 && !empty($username)) {
    $idStmt = $conn->prepare("SELECT account_id FROM account WHERE username = ? LIMIT 1");
    if ($idStmt) {
        $idStmt->bind_param("s", $username);
        $idStmt->execute();
        $idResult = $idStmt->get_result();
        if ($idRow = $idResult->fetch_assoc()) {
            $account_id = (int)$idRow['account_id'];
            $_SESSION['account_id'] = $account_id;
        }
        $idStmt->close();
    }
}

if ($account_id <= 0) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message_text = trim($_POST['message_text'] ?? '');

    if ($receiver_id <= 0) {
        $error = 'Please select a valid recipient.';
    } elseif ($receiver_id === $account_id) {
        $error = 'You cannot send a message to yourself.';
    } elseif ($message_text === '') {
        $error = 'Message text cannot be empty.';
    } else {
        $receiverCheck = $conn->prepare("SELECT account_id FROM account WHERE account_id = ? LIMIT 1");
        if ($receiverCheck) {
            $receiverCheck->bind_param("i", $receiver_id);
            $receiverCheck->execute();
            $receiverResult = $receiverCheck->get_result();

            if ($receiverResult->num_rows === 0) {
                $error = 'Recipient account does not exist.';
            } else {
                $sendStmt = $conn->prepare("
                    INSERT INTO message (sender_id, receiver_id, text)
                    VALUES (?, ?, ?)
                ");

                if ($sendStmt) {
                    $sendStmt->bind_param("iis", $account_id, $receiver_id, $message_text);
                    if ($sendStmt->execute()) {
                        $notice = 'Message sent successfully.';
                        $_POST['message_text'] = '';
                        $_POST['receiver_id'] = '';
                    } else {
                        $error = 'Message could not be sent: ' . $sendStmt->error;
                    }
                    $sendStmt->close();
                } else {
                    $error = 'Message query could not be prepared: ' . $conn->error;
                }
            }
            $receiverCheck->close();
        } else {
            $error = 'Recipient check could not be prepared: ' . $conn->error;
        }
    }
}

$accounts = [];
$accountsStmt = $conn->prepare("
    SELECT
        a.account_id,
        a.username,
        a.utype,
        COALESCE(p.screenname, a.username) AS display_name
    FROM account a
    LEFT JOIN profile p ON p.acc_id = a.account_id
    WHERE a.account_id <> ?
    ORDER BY display_name ASC
");

if ($accountsStmt) {
    $accountsStmt->bind_param("i", $account_id);
    $accountsStmt->execute();
    $accountsResult = $accountsStmt->get_result();
    if ($accountsResult) {
        $accounts = $accountsResult->fetch_all(MYSQLI_ASSOC);
    }
    $accountsStmt->close();
}

$messages = [];
$messageStmt = $conn->prepare("
    SELECT
        m.message_id,
        m.text,
        m.sent_at,
        m.read_at,
        sender.account_id AS sender_id,
        receiver.account_id AS receiver_id,
        COALESCE(sender_profile.screenname, sender.username) AS sender_name,
        COALESCE(receiver_profile.screenname, receiver.username) AS receiver_name
    FROM message m
    INNER JOIN account sender ON sender.account_id = m.sender_id
    INNER JOIN account receiver ON receiver.account_id = m.receiver_id
    LEFT JOIN profile sender_profile ON sender_profile.acc_id = sender.account_id
    LEFT JOIN profile receiver_profile ON receiver_profile.acc_id = receiver.account_id
    ORDER BY m.sent_at DESC
    LIMIT 10
");

if ($messageStmt) {
    $messageStmt->execute();
    $messageResult = $messageStmt->get_result();
    if ($messageResult) {
        $messages = $messageResult->fetch_all(MYSQLI_ASSOC);
    }
    $messageStmt->close();
}
$pendingAdminRequests = [];

$stmt = $conn->prepare("
    SELECT
        ar.request_id,
        ar.created_at,
        a.account_id,
        a.username,
        COALESCE(p.screenname, a.username) AS screenname
    FROM admin_requests ar
    INNER JOIN account a ON a.account_id = ar.account_id
    LEFT JOIN profile p ON p.acc_id = a.account_id
    WHERE ar.status = 'pending'
    ORDER BY ar.created_at ASC
");

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingAdminRequests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Parasocial</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
        }
        
        .navbar {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5em;
            font-weight: bold;
        }
        
        .admin-badge {
            background: rgba(255,255,255,0.3);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .logout-btn:hover {
            background: white;
            color: #e74c3c;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .welcome-title {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #e74c3c;
        }
        
        .stat-icon {
            font-size: 2em;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1em;
            color: #666;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.2);
        }
        
        .card-icon {
            font-size: 2.5em;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .card-text {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .action-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            width: 100%;
        }
        
        .action-btn:hover {
            background: #c0392b;
        }
        
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            color: #333;
            font-weight: bold;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-banned {
            background: #f8d7da;
            color: #721c24;
        }

        .form-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            color: #333;
            font-weight: bold;
            margin-bottom: 6px;
        }

        select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 1em;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .notice {
            background: #d4edda;
            color: #155724;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .badge {
            color: #999;
            font-size: 0.85em;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <i class="fas fa-heart"></i> Parasocial
                <span class="admin-badge">ADMIN</span>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <h1 class="welcome-title">Admin Control Panel</h1>
        </div>
		<div class="table-card">
    <h2 class="table-title"><i class="fas fa-user-shield"></i> Pending Admin Requests</h2>

    <?php if (empty($pendingAdminRequests)): ?>
        <p>No pending admin requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Screenname</th>
                    <th>Requested</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingAdminRequests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                        <td><?php echo htmlspecialchars($request['screenname']); ?></td>
                        <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                <button type="submit" name="admin_request_action" value="approved" class="action-btn">
                                    Approve
                                </button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                <button type="submit" name="admin_request_action" value="denied" class="action-btn">
                                    Deny
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
        <div class="form-card">
            <h2 class="table-title"><i class="fas fa-paper-plane"></i> Send Message</h2>

            <?php if ($notice !== ''): ?>
                <div class="notice"><?php echo htmlspecialchars($notice); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_dashboard.php">
                <div class="form-group">
                    <label for="receiver_id">Recipient</label>
                    <select id="receiver_id" name="receiver_id" required>
                        <option value="">Select an account</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo (int)$account['account_id']; ?>"
                                <?php echo ((int)($_POST['receiver_id'] ?? 0) === (int)$account['account_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['display_name']); ?>
                                (<?php echo htmlspecialchars($account['username']); ?>, <?php echo htmlspecialchars($account['utype'] ?? 'user'); ?>, #<?php echo (int)$account['account_id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message_text">Message</label>
                    <textarea id="message_text" name="message_text" required><?php echo htmlspecialchars($_POST['message_text'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="send_message" class="action-btn">Send Message</button>
            </form>
        </div>
        
        <div class="table-card">
            <h2 class="table-title"><i class="fas fa-envelope"></i> Recent Messages</h2>
            <?php if (empty($messages)): ?>
                <p>No messages found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Message</th>
                            <th>Sent</th>
                            <th>Read?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?php echo (int)$message['message_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($message['sender_name'] ?? ''); ?>
                                    <span class="badge">#<?php echo (int)$message['sender_id']; ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($message['receiver_name'] ?? ''); ?>
                                    <span class="badge">#<?php echo (int)$message['receiver_id']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($message['text'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($message['sent_at'] ?? ''); ?></td>
                                <td><?php echo empty($message['read_at']) ? 'No' : 'Yes'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
