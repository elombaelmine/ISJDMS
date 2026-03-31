<?php
session_start();
include("database.php"); 

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 1. Fetch Counts for the Overview Cards
// Total Users
$userCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin'")->fetch_assoc()['total'];

// Total Documents - Safe check using try-catch
try {
    $docResult = $conn->query("SELECT COUNT(*) as total FROM documents");
    $totalDocs = ($docResult) ? $docResult->fetch_assoc()['total'] : 0;
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, default to 0 instead of crashing
    $totalDocs = 0;
}

$pendingReview = 0; // Placeholder for future logic

// 2. Fetch Users for the table
$query = "SELECT id, fullname, role, status FROM registration WHERE role != 'admin' ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ISJ Admin - Dashboard</title>
    <link rel="stylesheet" href="../css/admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <h2 class="brand">ISJ Admin</h2>
            <nav class="sidebar-nav">
                <button class="nav-btn <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'home' ? 'active' : ''; ?>" onclick="location.href='?tab=home'">
                    <span>🏠</span> Home
                </button>
                <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'users') ? 'active' : ''; ?>" onclick="location.href='?tab=users'">
                    <span>👥</span> Manage Users
                </button>
                <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'docs') ? 'active' : ''; ?>" onclick="location.href='?tab=docs'">
                    <span>📂</span> Manage Docs
                </button>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link exit-btn">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </aside>

        <div class="main-layout">
            <header class="admin-header">
                <div class="header-search"><input type="text" placeholder="Search system..."></div>
                <div class="admin-profile">
                    <span class="role-badge">ADMIN</span>
                    <div class="avatar">AD</div>
                </div>
            </header>

            <main class="content-body">
    <?php if(!isset($_GET['tab']) || $_GET['tab'] == 'home'): ?>
    <h2 class="section-title">Dashboard Overview</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $userCount; ?></h3>
                <p>Total Users</p>
            </div>
            <i class="fas fa-user-friends"></i>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $totalDocs; ?></h3>
                <p>Total Documents</p>
            </div>
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?php echo $pendingReview; ?></h3>
                <p>Pending Review</p>
            </div>
            <i class="fas fa-hourglass-half"></i>
        </div>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'users'): ?>
    <section id="tab-users" class="admin-tab">
        <div class="page-header-flex">
            <h2 class="page-title">User Management</h2>
            <button class="btn-create" onclick="location.href='admin_roles/add_user.php'">+ Create New User</button>
        </div>

        <div class="data-table-container">
            <table class="isj-table">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>ROLE</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($user['status']); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="action-cell">
                            <a href="admin_roles/edit_user.php?id=<?php echo $user['id']; ?>" class="btn-icon">
                                <i class="fas fa-edit"></i>
                            </a>

                            <a href="admin_roles/toggle_status.php?id=<?php echo $user['id']; ?>" class="btn-icon">
                                <?php if(strtolower($user['status']) == 'enabled'): ?>
                                    <i class="fas fa-ban" style="color: #e74c3c;" title="Disable"></i>
                                <?php else: ?>
                                    <i class="fas fa-check-square" style="color: #2ecc71;" title="Enable"></i>
                                <?php endif; ?>
                            </a>

                            <a href="admin_roles/delete_user.php?id=<?php echo $user['id']; ?>" class="btn-icon" 
                               onclick="return confirm('Are you sure?')" style="color: #e74c3c;">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</main>
        </div>
    </div>
</body>
</html>