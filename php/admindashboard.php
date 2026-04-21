<?php
// Get all currently "open" folders from the URL (e.g., ?open=1,5,12)
$open_folders = isset($_GET['open']) ? explode(',', $_GET['open']) : [];

function renderAccordionTree($conn, $parent_id = NULL, $open_folders = []) {
    $sql = ($parent_id === NULL) 
            ? "SELECT * FROM documents WHERE parent_id IS NULL AND type = 'folder' ORDER BY name ASC" 
            : "SELECT * FROM documents WHERE parent_id = $parent_id AND type = 'folder' ORDER BY name ASC";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<ul style="list-style: none; padding-left: 20px; border-left: 1px dashed #061428; margin-top: 5px;">';
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $isOpen = in_array($id, $open_folders);
            
            // Build the toggle URL for the icon
            if ($isOpen) {
                $new_open = array_diff($open_folders, [$id]);
            } else {
                $new_open = array_merge($open_folders, [$id]);
            }
            $toggle_url = "?tab=plan&open=" . implode(',', $new_open);

            // INTELLIGENT CHECK: Does this folder have sub-folders?
            $check_sub = $conn->query("SELECT id FROM documents WHERE parent_id = $id AND type = 'folder' LIMIT 1");
            $hasSubFolders = ($check_sub->num_rows > 0);

            // Determine if clicking the name opens a page or expands the tree
            $name_link = $hasSubFolders ? $toggle_url : "admin_view_folder.php?id=" . $id;

            echo '<li style="margin: 8px 0;">';
            echo '<div style="display: flex; align-items: center; justify-content: space-between; max-width: 550px;">';
                
                echo '<div style="display: flex; align-items: center;">';
                    // The icon ALWAYS toggles the accordion
                    echo '<a href="'.$toggle_url.'" style="text-decoration: none; margin-right: 10px;">';
                        echo '<i class="fas '.($isOpen ? 'fa-folder-open' : 'fa-folder').'" style="color: #D4AF37;"></i>';
                    echo '</a>';

                    // The name opens the table ONLY if it's a leaf folder
                    echo '<a href="'.$name_link.'" style="text-decoration: none; color: #061428; font-weight: 500;">';
                        echo htmlspecialchars($row['name']);
                    echo '</a>';
                echo '</div>';

                // Action Buttons (Edit/Delete)
                echo '<div style="display: flex; gap: 10px; opacity: 0.5;">';
                    echo '<a href="admin_roles/edit_item.php?id='.$id.'" style="color: #3498db;"><i class="fas fa-edit fa-xs"></i></a>';
                    echo '<a href="admin_roles/delete_item.php?id='.$id.'" onclick="return confirm(\'Delete?\')" style="color: #e74c3c;"><i class="fas fa-trash fa-xs"></i></a>';
                echo '</div>';
            echo '</div>';

            // Only show sub-folders recursively; the table is now in admin_view_folder.php
            if ($isOpen) {
                renderAccordionTree($conn, $id, $open_folders);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
?>
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

// NEW: Counts for the User Management Tab
$enabledCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin' AND status = 'enabled'")->fetch_assoc()['total'];
$disabledCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin' AND status = 'disabled'")->fetch_assoc()['total'];


// Total Files only (for the Home overview)
$fileCountQuery = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'file'");
$totalFiles = ($fileCountQuery) ? $fileCountQuery->fetch_assoc()['total'] : 0;

// Detailed counts for the Manage Docs tab
$mainFolderCount = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'folder' AND parent_id IS NULL")->fetch_assoc()['total'];
$subFolderCount = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'folder' AND parent_id IS NOT NULL")->fetch_assoc()['total'];
$pendingReview = 0; // Placeholder for future logic

// 2. Fetch Users for the table
$query = "SELECT id, fullname, role, status FROM registration WHERE role != 'admin' ORDER BY id DESC";
$result = $conn->query($query);

// 3. Fetch Documents/Folders for the table
// Update this query in your PHP section
$docs_query = "SELECT * FROM documents WHERE type = 'file' ORDER BY created_at DESC";
$docs_result = $conn->query($docs_query);
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
        <span>📄</span> Manage Docs
    </button>
    <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'plan') ? 'active' : ''; ?>" onclick="location.href='?tab=plan'">
        <span>🌲</span> Heading Plan
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
        <h3><?php echo $totalFiles; ?></h3>
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
    <div class="page-header-flex" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <h2 class="page-title" style="margin: 0;">User Management</h2>
            
            <div class="user-stats-badges" style="display: flex; gap: 10px;">
                <span style="background: #f1f3f5; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #495057;">
                    Total: <?php echo $userCount; ?>
                </span>
                <span style="background: #e6fffa; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #088f8f; border: 1px solid #b2f5ea;">
                    Enabled: <?php echo $enabledCount; ?>
                </span>
                <span style="background: #fff5f5; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #e53e3e; border: 1px solid #feb2b2;">
                    Disabled: <?php echo $disabledCount; ?>
                </span>
            </div>
        </div>
        
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
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
    <?php if(isset($_GET['tab']) && $_GET['tab'] == 'docs'): ?>
<section id="tab-docs" class="admin-tab">
<?php if(isset($_GET['status'])): ?>
    <div class="alert-box" 
         style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; 
                background: <?php echo ($_GET['status'] == 'success' || $_GET['status'] == 'updated') ? '#e6fffa' : '#fff5f5'; ?>; 
                border: 1px solid <?php echo ($_GET['status'] == 'success' || $_GET['status'] == 'updated') ? '#b2f5ea' : '#feb2b2'; ?>;
                color: <?php echo ($_GET['status'] == 'success' || $_GET['status'] == 'updated') ? '#088f8f' : '#e53e3e'; ?>;">
        
        <i class="fas <?php echo ($_GET['status'] == 'success' || $_GET['status'] == 'updated') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span>
            <?php 
                if($_GET['status'] == 'success') echo "Excellent! The document has been delivered successfully.";
                elseif($_GET['status'] == 'updated') echo "The item has been modified successfully.";
                else echo "Something went wrong. Please check your internet or mail settings.";
            ?>
        </span>
    </div>
<?php endif; ?>

<?php if(isset($_GET['status'])): ?>
    <div class="alert-box <?php echo $_GET['status'] == 'success' ? 'alert-success' : 'alert-error'; ?>" 
         style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; 
                background: <?php echo $_GET['status'] == 'success' ? '#e6fffa' : '#fff5f5'; ?>; 
                border: 1px solid <?php echo $_GET['status'] == 'success' ? '#b2f5ea' : '#feb2b2'; ?>;
                color: <?php echo $_GET['status'] == 'success' ? '#088f8f' : '#e53e3e'; ?>;">
        
        <i class="fas <?php echo $_GET['status'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span>
            <?php 
                if($_GET['status'] == 'success') echo "Excellent! The document has been delivered successfully.";
                else echo "Something went wrong. Please check your internet or mail settings.";
            ?>
        </span>
    </div>
<?php endif; ?>

    <div class="page-header-flex">
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <h2 class="page-title" style="margin: 0;">File Management</h2>
            <div style="display: flex; gap: 10px; margin-top: 5px;">
                <span class="count-badge">📂 Main Folders: <?php echo $mainFolderCount; ?></span>
                <span class="count-badge">📁 Sub-Folders: <?php echo $subFolderCount; ?></span>
                <span class="count-badge">📄 Files: <?php echo $totalFiles; ?></span>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-create" onclick="location.href='admin_roles/create_folder.php'" style="background: var(--isj-blue); color: var(--isj-gold); border: 1px solid var(--isj-gold);">
                <i class="fas fa-folder-plus"></i> New Folder
            </button>
            
            <button class="btn-create" onclick="location.href='admin_roles/upload_doc.php'">
                <i class="fas fa-upload"></i> Upload Document
            </button>
        </div>
    </div>

    <div class="data-table-container">
        <table class="isj-table">
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>TYPE</th>
                    <th>VIEWED BY</th>
                    <th>AUTHOR</th>
                    <th>DATE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($docs_result && $docs_result->num_rows > 0): ?>
                    <?php while($item = $docs_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if($item['type'] == 'folder'): ?>
                                <i class="fas fa-folder" style="color: #f1c40f; margin-right: 8px;"></i>
                            <?php else: ?>
                                <i class="fas fa-file-pdf" style="color: #e74c3c; margin-right: 8px;"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </td>
                        <td><span class="type-badge <?php echo $item['type']; ?>"><?php echo strtoupper($item['type']); ?></span></td>
                        <td><span class="role-pill"><?php echo strtoupper($item['viewed_by']); ?></span></td>
                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                        <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                        <td class="action-cell">
    <?php if($item['type'] == 'file'): ?>
        <a href="../<?php echo $item['file_path']; ?>" class="btn-icon" target="_blank" title="View">
            <i class="fas fa-eye"></i> 
        </a>
        <a href="admin_roles/share_doc.php?id=<?php echo $item['id']; ?>" class="btn-icon" style="color: #D4AF37;" title="Share">
            <i class="fas fa-paper-plane"></i>
        </a>
    <?php endif; ?>

    <a href="admin_roles/edit_item.php?id=<?php echo $item['id']; ?>" class="btn-icon" style="color: #3498db;" title="Modify">
        <i class="fas fa-edit"></i>
    </a>

    <a href="admin_roles/delete_item.php?id=<?php echo $item['id']; ?>" 
       class="btn-icon delete" 
       onclick="return confirm('Delete this item?')" 
       title="Delete">
        <i class="fas fa-trash-alt"></i>
    </a>
</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px;">No documents or folders found. Start by creating a folder!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<?php if(isset($_GET['tab']) && $_GET['tab'] == 'plan'): ?>
<section id="tab-plan" class="admin-tab">
    <div class="page-header-flex">
        <h2 class="page-title">Organizational Heading Plan</h2>
        <button class="btn-create" onclick="location.href='admin_roles/create_folder.php'">
            <i class="fas fa-folder-plus"></i> New Folder
        </button>
    </div>

    <div class="data-table-container" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div class="folder-hierarchy">
            <?php renderAccordionTree($conn, NULL, $open_folders); ?>
        </div>
    </div>
</section>
<?php endif; ?>
</main>
        </div>
    </div>
</body>
</html>