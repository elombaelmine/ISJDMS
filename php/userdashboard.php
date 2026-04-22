<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
// Retrieve the role from the session
$user_role = $_SESSION['role'] ?? ''; 
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);

// Flag for upload permissions (Staff and Teachers only)
$can_upload = ($user_role === 'teacher' || $user_role === 'staff');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Docs — Dashboard</title>
    <!-- <link rel="stylesheet" href="../css/userdashboard.css?v=1.1"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/userdashboard.css?v=1.2">
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../images/image.png" alt="ISJ Logo">
        </div>
        
        <button class="btn-upload" 
    <?php if (!$can_upload) echo 'disabled style="opacity: 0.5; cursor: not-allowed;"'; ?>
    onclick="window.location.href='admin_roles/upload_doc.php'">
    <i class="fas <?php echo $can_upload ? 'fa-plus' : 'fa-lock'; ?>"></i> New Upload
</button>

        <nav class="sidebar-nav">
            <p class="nav-label">DOCUMENTATION</p>
            <a href="#" class="nav-item active" id="btn-all-files">
                <i class="fas fa-th-large"></i> All Files
            </a>
            <a href="#" class="nav-item" id="btn-nouveautes">
                <i class="fas fa-bolt"></i> Recent Docs <span class="badge-new">New</span>
            </a>
            <a href="#" class="nav-item" id="btn-advanced-search">
                <i class="fas fa-graduation-cap"></i> Advanced search
            </a>
           <a href="#" class="nav-item" id="btn-plan"><i class="fas fa-book-reader"></i> Plan</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            <a href="../php/setting.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </aside>

    <main class="main-content">

    <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
    <div style="background: #000; color: #fff; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 5px solid #D4AF37; display: flex; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <i class="fas fa-check-circle" style="margin-right: 12px; color: #D4AF37;"></i>
        <span style="font-weight: 500; font-size: 0.95rem;">Your account settings have been successfully updated.</span>
    </div>
<?php endif; ?>

        <header class="header-top">
    <form action="" method="GET" class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" name="simple_search" placeholder="Search documents..." 
               value="<?php echo htmlspecialchars($_GET['simple_search'] ?? ''); ?>">
        <button type="submit" name="perform_search" style="display:none;"></button>
    </form>
    
    <div class="user-info">
        <span class="user-name"><?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
        <div class="user-avatar" style="text-transform: uppercase;"><?php echo htmlspecialchars($initial); ?></div>
    </div>
</header>
<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert-box" 
         style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; 
                background: #e6fffa; border: 1px solid #b2f5ea; color: #088f8f; font-family: 'Segoe UI', sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>Excellent! The document has been delivered successfully.</span>
        </div>
        
        <button onclick="this.parentElement.style.display='none'" 
                style="background:none; border:none; color:inherit; cursor:pointer; font-size: 1.2rem; font-weight:bold;">&times;</button>
    </div>
<?php endif; ?>

        <div id="nouveautes-section" style="display: none;">
            <div class="section-title">
                <h2><i class="fas fa-bolt" style="color: var(--gold);"></i> Recent Docs</h2>
            </div>
            <div class="table-card">
                <table class="isj-table">
                    <thead>
    <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Author</th>
        <th>Actions</th> </tr>
</thead>
                    <tbody>
    <?php
    // Fetch the 10 most recent files allowed for this role
    $recent_sql = "SELECT * FROM documents 
                   WHERE type = 'file' 
                   AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                   ORDER BY created_at DESC 
                   LIMIT 10";
    $recent_res = $conn->query($recent_sql);

    if ($recent_res && $recent_res->num_rows > 0):
        while($file = $recent_res->fetch_assoc()): ?>
        <tr>
            <td>
                <i class="fas fa-file-pdf pdf-red"></i> 
                <?php echo htmlspecialchars($file['name']); ?>
            </td>
            <td><?php echo date('F d, Y g:i A', strtotime($file['created_at'])); ?></td>
            <td><?php echo htmlspecialchars($file['author'] ?? 'System Administrator'); ?></td>
            <td class="action-buttons">
                <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" 
                   target="_blank" class="btn-icon" title="View">
                    <i class="fas fa-eye" style="color: var(--dark-blue);"></i>
                </a>
                
                <a href="admin_roles/share_doc.php?id=<?php echo $file['id']; ?>" 
                   class="btn-icon" title="Share">
                    <i class="fas fa-share-alt" style="color: #D4AF37;"></i>
                </a>
                
                <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" 
                   download="<?php echo htmlspecialchars($file['name']); ?>" 
                   class="btn-icon" title="Download">
                    <i class="fas fa-download" style="color: green;"></i>
                </a>
            </td>
        </tr>
    <?php endwhile; 
    else: ?>
        <tr>
            <td colspan="4" style="text-align: center; padding: 30px; color: #888;">
                No recent documents found.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
                </table>
            </div>
        </div>

       <div id="advanced-search-section" style="display: none;">
    <div class="search-grid-form">
        <div class="section-title">
            <h2><i class="fas fa-search-plus" style="color: var(--gold);"></i> Advanced Search</h2>
        </div>
        
        <form action="" method="GET" id="searchForm">
            <div class="input-row">
                <div class="field-group">
                    <label>Title (file)</label>
                    <input type="text" name="titre" class="search-input" placeholder="Enter title..." value="<?php echo htmlspecialchars($_GET['titre'] ?? ''); ?>">
                </div>
                <div class="field-group">
                    <label>Author</label>
                    <input type="text" name="auteur" class="search-input" placeholder="Enter author name..." value="<?php echo htmlspecialchars($_GET['auteur'] ?? ''); ?>">
                </div>
            </div>
            <div class="input-row">
                <div class="field-group">
                    <label>Description (Keywords)</label>
                    <input type="text" name="description" class="search-input" placeholder="e.g. 'exam', 'finance', 'report'..." value="<?php echo htmlspecialchars($_GET['description'] ?? ''); ?>">
                </div>
                <div class="field-group">
                    <label>Name of folder</label>
                    <input type="text" name="filename" class="search-input" placeholder="Enter folder name..." value="<?php echo htmlspecialchars($_GET['filename'] ?? ''); ?>">
                </div>
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <button type="submit" name="perform_search" class="btn-submit-search" onclick="return validateSearch()">
                    <i class="fas fa-search"></i> Search Documents
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['perform_search'])): 
    // 1. Get all possible inputs
    $titre = trim($_GET['titre'] ?? '');
    $auteur = trim($_GET['auteur'] ?? '');
    $desc = trim($_GET['description'] ?? '');
    $folder = trim($_GET['filename'] ?? '');
    $simple = trim($_GET['simple_search'] ?? ''); // New: catch header search

    // 2. Base query filtered by role
    $query = "SELECT * FROM documents WHERE type = 'file' AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";

    // 3. Apply Simple Search if present
    if (!empty($simple)) {
        $s = $conn->real_escape_string($simple);
        $query .= " AND (name LIKE '%$s%' OR author LIKE '%$s%' OR description LIKE '%$s%')";
    }

    // 4. Apply Advanced Filters if present
    if (!empty($titre)) { $query .= " AND name LIKE '%" . $conn->real_escape_string($titre) . "%'"; }
    if (!empty($auteur)) { $query .= " AND author LIKE '%" . $conn->real_escape_string($auteur) . "%'"; }
    if (!empty($desc)) { $query .= " AND description LIKE '%" . $conn->real_escape_string($desc) . "%'"; }

    if (!empty($folder)) {
        $query .= " AND parent_id IN (SELECT id FROM documents WHERE name LIKE '%" . $conn->real_escape_string($folder) . "%' AND type='folder')";
    }

    $results = $conn->query($query);
?>
    <div class="table-card" style="margin-top: 30px;">
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
                            <td><i class="fas fa-file-pdf pdf-red"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                            <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                            <td><small><?php echo htmlspecialchars($file['description']); ?></small></td>
                            <td class="action-buttons">
                                <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No matches found for your criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
        <div id="dashboard-default-content">
            <div class="folder-grid">
    <?php
    // 1. Fetch top-level folders authorized for this user's role
    $sql = "SELECT * FROM documents 
            WHERE type = 'folder' 
            AND parent_id IS NULL 
            AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
            ORDER BY name ASC";
    
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $folder_id = $row['id'];
            $folder_name = $row['name'];

            // 2. Count ONLY sub-folders the user is allowed to see [Updated Filter]
            $sub_sql = "SELECT COUNT(*) as sub_count FROM documents 
                        WHERE parent_id = $folder_id 
                        AND type = 'folder'
                        AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";
            $sub_res = $conn->query($sub_sql)->fetch_assoc();

            // 3. Count ONLY files in this branch that the user is allowed to see
            $total_files_sql = "SELECT COUNT(*) as total_files FROM documents 
                                WHERE type = 'file' 
                                AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                AND (
                                    parent_id = $folder_id 
                                    OR parent_id IN (
                                        SELECT id FROM documents 
                                        WHERE parent_id = $folder_id 
                                        AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                    )
                                )";
            $files_res = $conn->query($total_files_sql)->fetch_assoc();

            $display_sub = $sub_res['sub_count'];
            $display_files = $files_res['total_files'];

            // Icon logic based on folder name
            $icon = "fa-folder"; 
            if (stripos($folder_name, 'Academic') !== false) $icon = "fa-user-graduate";
            elseif (stripos($folder_name, 'Finance') !== false) $icon = "fa-chart-line";
            elseif (stripos($folder_name, 'Governance') !== false) $icon = "fa-landmark";
            elseif (stripos($folder_name, 'Human') !== false) $icon = "fa-user-tie";
            ?>
            
            <div class="folder-card" onclick="window.location.href='view_folder.php?id=<?php echo $folder_id; ?>'">
                <i class="fas <?php echo $icon; ?> fa-2x" style="color: var(--dark-blue);"></i>
                <div class="folder-info">
                    <h4><?php echo htmlspecialchars($folder_name); ?></h4>
                    <small style="color: #666; font-size: 0.8rem;">
                        <?php echo $display_sub; ?> Sub-folders • <?php echo $display_files; ?> Files
                    </small>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<p style='grid-column: 1/-1; text-align: center; color: #888;'>No authorized folders found.</p>";
    }
    ?>
</div>
        </div>
<div id="plan-section" style="display: none;">
    <div class="section-title">
        <h2><i class="fas fa-book-reader" style="color: var(--gold);"></i> Organizational Plan</h2>
        <p style="color: #666; font-size: 0.9rem;">Hierarchy for role: <strong><?php echo ucfirst($user_role); ?></strong></p>
    </div>

    <div class="tree-card">
        <?php
        // Function to render the tree specifically for the authorized role
        if (!function_exists('renderRoleTree')) {
            function renderRoleTree($conn, $user_role, $parent_id = NULL) {
                // Select only folders authorized for this role
                $sql = "SELECT * FROM documents 
                        WHERE type = 'folder' 
                        AND (parent_id " . ($parent_id === NULL ? "IS NULL" : "= $parent_id") . ") 
                        AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                        ORDER BY name ASC";
                
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    echo '<ul class="folder-tree" style="list-style: none; padding-left: 20px; border-left: 1px dashed #ccc; margin: 10px 0;">';
                    while ($folder = $result->fetch_assoc()) {
                        $id = $folder['id'];
                        echo '<li style="margin: 8px 0;">';
                        echo '<div class="tree-item" onclick="window.location.href=\'view_folder.php?id=' . $id . '\'" style="cursor: pointer; display: flex; align-items: center;">';
                        echo '<i class="fas fa-folder" style="color: #D4AF37; margin-right: 10px;"></i>';
                        echo '<span class="folder-name">' . htmlspecialchars($folder['name']) . '</span>';
                        echo '</div>';
                        
                        // Recursive call to find authorized subfolders
                        renderRoleTree($conn, $user_role, $id);
                        
                        echo '</li>';
                    }
                    echo '</ul>';
                }
            }
        }

        // Execution of the role-specific tree
        renderRoleTree($conn, $user_role);
        ?>
    </div>
</div>
           
    </main>
</div>

<script>
// 1. Keep your validation function here
function validateSearch() {
    const inputs = document.querySelectorAll('.search-input');
    let allEmpty = true;

    inputs.forEach(input => {
        if (input.value.trim() !== "") {
            allEmpty = false;
            input.style.borderColor = "#ddd"; 
        }
    });

    if (allEmpty) {
        alert("Please enter at least one search criteria!");
        inputs.forEach(input => {
            input.style.borderColor = "red"; 
        });
        return false; 
    }
    return true; 
}

// 2. Place the new logic here
document.addEventListener('DOMContentLoaded', function() {
    // Selectors
    const nouveautesBtn = document.getElementById('btn-nouveautes');
    const allFilesBtn = document.getElementById('btn-all-files');
    const searchBtn = document.getElementById('btn-advanced-search');
    const planBtn = document.getElementById('btn-plan');

    const nouveautesSection = document.getElementById('nouveautes-section');
    const defaultContent = document.getElementById('dashboard-default-content');
    const searchSection = document.getElementById('advanced-search-section');
    const planSection = document.getElementById('plan-section');
    const topSearchBar = document.querySelector('.search-container');

    const urlParams = new URLSearchParams(window.location.search);

    function hideAllSections() {
        if(nouveautesSection) nouveautesSection.style.display = 'none';
        if(defaultContent) defaultContent.style.display = 'none';
        if(searchSection) searchSection.style.display = 'none';
        if(planSection) planSection.style.display = 'none';
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
    }

    // Initial Load Check for Search Results
    // Initial Load Check for Search Results
if (urlParams.has('perform_search')) {
    hideAllSections();
    searchSection.style.display = 'block';
    searchBtn.classList.add('active');

    // If it was a simple search from the top, keep the top bar visible
    // If it was an advanced search, hide it as you originally intended
    if (urlParams.has('simple_search') && urlParams.get('simple_search') !== "") {
        topSearchBar.style.display = 'flex';
    } else {
        topSearchBar.style.display = 'none';
    }
}

    // Event Listeners
    nouveautesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllSections();
        nouveautesSection.style.display = 'block';
        topSearchBar.style.display = 'flex';
        nouveautesBtn.classList.add('active');
    });

    allFilesBtn.addEventListener('click', (e) => {
        if (urlParams.has('perform_search')) {
            window.location.href = 'userdashboard.php';
        } else {
            e.preventDefault();
            hideAllSections();
            defaultContent.style.display = 'block';
            topSearchBar.style.display = 'flex';
            allFilesBtn.classList.add('active');
        }
    });

    searchBtn.addEventListener('click', (e) => {
    e.preventDefault();
    hideAllSections();
    searchSection.style.display = 'block';
    
    // TWEAK: Only hide the top bar if the user clicked "Advanced Search" 
    // and hasn't already performed a simple search.
    if (!urlParams.has('simple_search') || urlParams.get('simple_search') === "") {
        topSearchBar.style.display = 'none';
    }
    
    searchBtn.classList.add('active');
});

    planBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllSections();
        planSection.style.display = 'block';
        topSearchBar.style.display = 'none';
        planBtn.classList.add('active');
    });
});
</script>
</body>
</html>