<?php
session_start();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HeartConnect</title>
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <i class="fas fa-shield-alt"></i> HeartConnect
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
            <p class="welcome-subtitle">Manage users, monitor activity, and oversee platform operations</p>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number">2,547</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-number">142</div>
                <div class="stat-label">New Users (Today)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-heart"></i></div>
                <div class="stat-number">8,923</div>
                <div class="stat-label">Total Matches</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div class="stat-number">45,678</div>
                <div class="stat-label">Messages Sent</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-number">23</div>
                <div class="stat-label">Pending Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-robot"></i></div>
                <div class="stat-number">156</div>
                <div class="stat-label">Active Bots</div>
            </div>
        </div>
        
        <!-- Admin Action Cards -->
        <div class="card-grid">
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-users-cog"></i></div>
                <h3 class="card-title">User Management</h3>
                <p class="card-text">View, edit, suspend, or delete user accounts. Manage user permissions and roles.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> Manage Users</button>
            </div>
            
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-flag"></i></div>
                <h3 class="card-title">Review Reports</h3>
                <p class="card-text">Review and take action on user-reported content, profiles, and behavior.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> View Reports</button>
            </div>
            
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-robot"></i></div>
                <h3 class="card-title">Bot Management</h3>
                <p class="card-text">Configure, deploy, and monitor AI bots to enhance user engagement.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> Manage Bots</button>
            </div>
            
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                <h3 class="card-title">Analytics</h3>
                <p class="card-text">View detailed analytics, user engagement metrics, and platform statistics.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> View Analytics</button>
            </div>
            
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-database"></i></div>
                <h3 class="card-title">Database Tools</h3>
                <p class="card-text">Perform database maintenance, backups, and data integrity checks.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> Database Tools</button>
            </div>
            
            <div class="admin-card">
                <div class="card-icon"><i class="fas fa-cogs"></i></div>
                <h3 class="card-title">System Settings</h3>
                <p class="card-text">Configure platform settings, features, and global preferences.</p>
                <button class="action-btn"><i class="fas fa-arrow-right"></i> Settings</button>
            </div>
        </div>
        
        <!-- Recent User Activity Table -->
        <div class="table-card">
            <h2 class="table-title"><i class="fas fa-clock"></i> Recent User Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Account Type</th>
                        <th>Status</th>
                        <th>Last Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1001</td>
                        <td>sarah_jones</td>
                        <td>Regular User</td>
                        <td><span class="status-badge status-active">Active</span></td>
                        <td>2 minutes ago</td>
                        <td><i class="fas fa-edit" style="cursor: pointer; color: #667eea; margin-right: 10px;"></i> <i class="fas fa-ban" style="cursor: pointer; color: #e74c3c;"></i></td>
                    </tr>
                    <tr>
                        <td>1002</td>
                        <td>mike_thompson</td>
                        <td>Regular User</td>
                        <td><span class="status-badge status-active">Active</span></td>
                        <td>15 minutes ago</td>
                        <td><i class="fas fa-edit" style="cursor: pointer; color: #667eea; margin-right: 10px;"></i> <i class="fas fa-ban" style="cursor: pointer; color: #e74c3c;"></i></td>
                    </tr>
                    <tr>
                        <td>1003</td>
                        <td>emma_wilson</td>
                        <td>Regular User</td>
                        <td><span class="status-badge status-pending">Pending Review</span></td>
                        <td>1 hour ago</td>
                        <td><i class="fas fa-edit" style="cursor: pointer; color: #667eea; margin-right: 10px;"></i> <i class="fas fa-ban" style="cursor: pointer; color: #e74c3c;"></i></td>
                    </tr>
                    <tr>
                        <td>1004</td>
                        <td>john_spam</td>
                        <td>Regular User</td>
                        <td><span class="status-badge status-banned">Banned</span></td>
                        <td>2 days ago</td>
                        <td><i class="fas fa-edit" style="cursor: pointer; color: #667eea; margin-right: 10px;"></i> <i class="fas fa-undo" style="cursor: pointer; color: #27ae60;"></i></td>
                    </tr>
                    <tr>
                        <td>1005</td>
                        <td>bot_friendly01</td>
                        <td>Bot Account</td>
                        <td><span class="status-badge status-active">Active</span></td>
                        <td>5 minutes ago</td>
                        <td><i class="fas fa-edit" style="cursor: pointer; color: #667eea; margin-right: 10px;"></i> <i class="fas fa-ban" style="cursor: pointer; color: #e74c3c;"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
