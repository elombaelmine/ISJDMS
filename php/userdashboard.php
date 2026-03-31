<?php
session_start();
include("database.php");

// 1. Security Check: Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Get User Data from the Session (Set during login)
$fullname = $_SESSION['fullname'];
// Extract first name and convert to lowercase for the "wilson" style
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Docs — Dashboard</title>
    <link rel="stylesheet" href="../css/userdashboard.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../images/image.png" alt="ISJ Logo">
            </div>
            
            <button class="btn-upload"><i class="fas fa-plus"></i> New Upload</button>

            <nav class="sidebar-nav">
                <p class="nav-label">DOCUMENTATION</p>
                <a href="#" class="nav-item active"><i class="fas fa-th-large"></i> All Files</a>
                <a href="#" class="nav-item">
                    <i class="fas fa-bolt"></i> Nouveautés <span class="badge-new">New</span>
                </a>
                <a href="#" class="nav-item"><i class="fas fa-graduation-cap"></i> Cycles</a>
                <a href="#" class="nav-item"><i class="fas fa-book-reader"></i> Official Guides</a>
            </nav>

            <div class="sidebar-bottom">
                <a href="logout.php" class="nav-item logout-link">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
                <a href="../php/setting.php" class="nav-item" id="settings-btn">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header-top">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search by title, author, or subject...">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
                </div>
            </header>

            <div class="section-title"><h2>Categories</h2></div>
            <div class="folder-grid">
                <div class="folder-card">
                    <i class="fas fa-folder fa-2x"></i>
                    <div class="folder-info">
                        <h4>Academic Cycles</h4>
                        <span>150 Files</span>
                    </div>
                </div>
                <div class="folder-card">
                    <i class="fas fa-folder fa-2x"></i>
                    <div class="folder-info">
                        <h4>Administrative</h4>
                        <span>42 Files</span>
                    </div>
                </div>
            </div>

            <div class="section-title"><h2>Recent Documents</h2></div>
            <div class="file-grid">
                <div class="file-card">
                    <div class="file-preview pdf"><i class="fas fa-file-pdf"></i></div>
                    <div class="file-details">
                        <p class="file-name">Guide_Electeur.pdf</p>
                        <p class="file-meta">Author: Admin • 24.03.2024</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>