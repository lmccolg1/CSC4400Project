<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$user_type = $_SESSION['user_type'] ?? 'user';

// Redirect to admin dashboard if admin
if ($user_type === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Para Social</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5em;
            font-weight: bold;
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
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-title {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .feature-icon {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .feature-text {
            color: #666;
            line-height: 1.5;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1em;
            opacity: 0.9;
        }
        
        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .activity-item {
            padding: 15px;
            border-left: 3px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <i class="fas fa-heart"></i> Parasocial
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?></span>
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
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h1>
            <p class="welcome-subtitle">Ready to make meaningful connections today?</p>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div class="stat-label">New Matches</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">8</div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">45</div>
                <div class="stat-label">Profile Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">23</div>
                <div class="stat-label">Likes Received</div>
            </div>
        </div>
        
        <!-- Feature Cards -->
        <div class="card-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="feature-title">Find Matches</h3>
                <p class="feature-text">Discover compatible people based on your interests and preferences.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="feature-title">Messages</h3>
                <p class="feature-text">Chat with your matches and build meaningful connections.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3 class="feature-title">Edit Profile</h3>
                <p class="feature-text">Update your profile to showcase your best self.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h3 class="feature-title">Settings</h3>
                <p class="feature-text">Customize your preferences and privacy settings.</p>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2 class="activity-title"><i class="fas fa-clock"></i> Recent Activity</h2>
            
            <div class="activity-item">
                <strong>New Match!</strong> You matched with Sarah J.
                <div class="activity-time">2 hours ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Message Received</strong> Mike T. sent you a message
                <div class="activity-time">5 hours ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Profile View</strong> Someone viewed your profile
                <div class="activity-time">1 day ago</div>
            </div>
            
            <div class="activity-item">
                <strong>Like Received</strong> Emma R. liked your profile
                <div class="activity-time">2 days ago</div>
            </div>
        </div>
    </div>
</body>
</html>
