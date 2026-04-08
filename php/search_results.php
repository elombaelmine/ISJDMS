<!-- <?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? '';
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);

// Get search terms
$titre = $_GET['titre'] ?? '';
$auteur = $_GET['auteur'] ?? '';
$desc = $_GET['description'] ?? '';
$folder = $_GET['filename'] ?? '';

// BUILD THE QUERY BASED ON YOUR ACTUAL COLUMNS
// We select from 'documents' directly since 'author' is already a column there.
$query = "SELECT * FROM documents 
          WHERE type = 'file' 
          AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";

// Dynamically add search filters using your column names: name, author, description
if (!empty($titre)) {
    $query .= " AND name LIKE '%" . $conn->real_escape_string($titre) . "%'";
}
if (!empty($auteur)) {
    $query .= " AND author LIKE '%" . $conn->real_escape_string($auteur) . "%'";
}
if (!empty($desc)) {
    $query .= " AND description LIKE '%" . $conn->real_escape_string($desc) . "%'";
}
if (!empty($folder)) {
    // Subquery to find files within a specific folder name
    $query .= " AND parent_id IN (SELECT id FROM documents WHERE name LIKE '%" . $conn->real_escape_string($folder) . "%' AND type='folder')";
}

$results = $conn->query($query);

// Error handling to help you debug if something else is wrong
if (!$results) {
    die("Database Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results — ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/userdashboard.css?v=1.6">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../images/image.png" alt="ISJ Logo">
            </div>
            <nav class="sidebar-nav">
                <p class="nav-label">DOCUMENTATION</p>
                <a href="userdashboard.php" class="nav-item">
                    <i class="fas fa-th-large"></i> All Files
                </a>
                <a href="#" class="nav-item active">
                    <i class="fas fa-search"></i> Search Results
                </a>
            </nav>
            <div class="sidebar-bottom">
                <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header-top">
                <div class="breadcrumb">
                    <a href="userdashboard.php" style="text-decoration:none; color: var(--isj-gold); font-weight:bold;">Dashboard</a> 
                    <span style="color: #888; margin: 0 10px;">/</span> 
                    <span>Search Results</span>
                </div>
                
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
                    <div class="user-avatar"><?php echo htmlspecialchars(strtoupper($initial)); ?></div>
                </div>
            </header>

            <div class="section-title">
                <h2><i class="fas fa-search" style="color: var(--isj-gold);"></i> Document Results</h2>
            </div>
            
            <div class="table-card">
                <table class="isj-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results && $results->num_rows > 0): ?>
                            <?php while($file = $results->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-pdf" style="color: #e74c3c; margin-right: 10px;"></i> 
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                    <td>
                                        <small style="color: #666;">
                                            <?php echo htmlspecialchars($file['description'] ?: 'No description provided'); ?>
                                        </small>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>#toolbar=0" 
                                           target="_blank" class="btn-icon" title="View">
                                            <i class="fas fa-eye" style="color: var(--isj-blue);"></i>
                                        </a>
                                        
                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" 
                                           download="<?php echo htmlspecialchars($file['name']); ?>" 
                                           class="btn-icon" title="Download">
                                            <i class="fas fa-download" style="color: #27ae60;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 50px; color: #888;">
                                    <i class="fas fa-folder-open fa-3x" style="display: block; margin-bottom: 15px; opacity: 0.5;"></i>
                                    No documents found matching your search criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px;">
                <a href="userdashboard.php" class="btn-submit-search" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fas fa-arrow-left"></i> New Search
                </a>
            </div>
        </main>
    </div>
</body>
</html> -->