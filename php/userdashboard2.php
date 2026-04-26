<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$user_role = $_SESSION['role'] ?? ''; 
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);
$can_upload = ($user_role === 'teacher' || $user_role === 'staff');

// --- SEARCH LOGIC SECTION ---
$results = null;
if (isset($_GET['perform_search']) || !empty($_GET['simple_search'])) {
    $is_simple = !empty($_GET['simple_search']);
    
    if ($is_simple) {
        // Flexible search across all metadata fields
        $search_term = $conn->real_escape_string(trim($_GET['simple_search']));
        $query = "SELECT * FROM documents WHERE type = 'file' 
                  AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                  AND (
                      name LIKE '%$search_term%' OR 
                      author LIKE '%$search_term%' OR 
                      description LIKE '%$search_term%' OR 
                      DATE(created_at) LIKE '%$search_term%'
                  )";
    } else {
        // Strict advanced search requiring all fields
        $titre = $conn->real_escape_string(trim($_GET['titre'] ?? ''));
        $auteur = $conn->real_escape_string(trim($_GET['auteur'] ?? ''));
        $desc = $conn->real_escape_string(trim($_GET['description'] ?? ''));
        $folder = $conn->real_escape_string(trim($_GET['filename'] ?? ''));
        $date = $conn->real_escape_string(trim($_GET['date_creation'] ?? ''));

        $query = "SELECT * FROM documents WHERE type = 'file' 
                  AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                  AND name LIKE '%$titre%'
                  AND author LIKE '%$auteur%'
                  AND description LIKE '%$desc%'
                  AND DATE(created_at) = '$date'
                  AND parent_id IN (
                      SELECT id FROM documents 
                      WHERE name LIKE '%$folder%' AND type='folder'
                  )";
    }
    $results = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Docs — Dashboard</title>
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
            <a href="userdashboard.php" class="nav-item <?php echo !isset($_GET['perform_search']) && !isset($_GET['date_creation']) ? 'active' : ''; ?>" id="btn-all-files">
                <i class="fas fa-th-large"></i> All Files
            </a>
            <a href="#" class="nav-item" id="btn-nouveautes">
                <i class="fas fa-bolt"></i> Recent Docs <span class="badge-new">New</span>
            </a>
            
            <a href="#" class="nav-item" id="btn-advanced-search">
                <i class="fas fa-graduation-cap"></i> Advanced search
            </a>
            <a href="#" class="nav-item" id="btn-search-within">
                <i class="fas fa-search"></i> Search Within Files
            </a>
             
            <a href="#" class="nav-item" id="btn-plan"><i class="fas fa-book-reader"></i> Plan</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            <a href="../php/setting.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header-top">
            <form action="userdashboard.php" method="GET" class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" name="simple_search" placeholder="Search by keyword, filename, author..." 
                       value="<?php echo htmlspecialchars($_GET['simple_search'] ?? ''); ?>">
                <button type="submit" style="display:none;"></button>
            </form>
            
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
                <div class="user-avatar" style="text-transform: uppercase;"><?php echo htmlspecialchars($initial); ?></div>
            </div>
        </header>

        <div id="nouveautes-section" style="display: none;">
            <div class="section-title">
                <h2><i class="fas fa-bolt" style="color: var(--gold);"></i> Recent Docs</h2>
            </div>
            <div class="table-card">
                <table class="isj-table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Author</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_sql = "SELECT * FROM documents WHERE type = 'file' 
                                       AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                       ORDER BY created_at DESC LIMIT 10";
                        $recent_res = $conn->query($recent_sql);
                        if ($recent_res && $recent_res->num_rows > 0):
                            while($file = $recent_res->fetch_assoc()): ?>
                            <tr>
                                <td><i class="fas fa-file-pdf pdf-red"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo date('F d, Y', strtotime($file['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                <td class="action-buttons">
                                    <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                    <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" style="text-align: center; padding: 30px;">No recent documents.</td></tr>
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
                <form action="userdashboard.php" method="GET" id="searchForm">
                    <div class="input-row">
                        <div class="field-group"><label>Title (file)</label><input type="text" name="titre" class="search-input" placeholder="Title..." value="<?php echo htmlspecialchars($_GET['titre'] ?? ''); ?>"></div>
                        <div class="field-group"><label>Author</label><input type="text" name="auteur" class="search-input" placeholder="Author..." value="<?php echo htmlspecialchars($_GET['auteur'] ?? ''); ?>"></div>
                    </div>
                    <div class="input-row">
                        <div class="field-group"><label>Description (Keywords)</label><input type="text" name="description" class="search-input" placeholder="Keywords..." value="<?php echo htmlspecialchars($_GET['description'] ?? ''); ?>"></div>
                        <div class="field-group"><label>Name of folder</label><input type="text" name="filename" class="search-input" placeholder="Folder..." value="<?php echo htmlspecialchars($_GET['filename'] ?? ''); ?>"></div>
                    </div>
                    <div class="input-row">
                        <div class="field-group" style="flex: 1;"><label>Date of Creation</label><input type="date" name="date_creation" class="search-input" value="<?php echo $_GET['date_creation'] ?? ''; ?>"></div>
                        <div class="field-group" style="flex: 1; visibility: hidden;"></div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <input type="hidden" name="perform_search" value="1">
                        <button type="submit" class="btn-submit-search" onclick="return validateSearch()">
                            <i class="fas fa-search"></i> Search Documents
                        </button>
                    </div>
                </form>
            </div>
            <div id="search-within-section" style="display: none;">
    <div class="section-title">
        <h2><i class="fas fa-file-alt" style="color: var(--gold);"></i> Search Content Within Files</h2>
        <p style="color: #666; font-size: 0.85rem;">Search for specific text inside PDF documents.</p>
    </div>

    <div class="search-grid-form" style="margin-bottom: 20px;">
        <div class="search-container" style="width: 100%; max-width: 600px; margin: 0 auto;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Type text to find inside files..." style="width: 100%; padding: 12px 40px; border-radius: 8px; border: 1px solid #ddd;">
        </div>
    </div>

    <div class="table-card">
        <table class="isj-table">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Matched Content</th> <th>Page</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #aaa;">
                        <i class="fas fa-keyboard fa-2x" style="display: block; margin-bottom: 10px;"></i>
                        Enter a keyword above to start searching inside documents.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

            <?php if (isset($_GET['perform_search']) && empty($_GET['simple_search'])): ?>
                <div class="table-card" style="margin-top: 30px;">
                    <table class="isj-table">
                        <thead>
                            <tr><th>Title</th><th>Author</th><th>Date Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($results && $results->num_rows > 0): ?>
                                <?php while($file = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td><i class="fas fa-file-pdf pdf-red"></i> <strong><?php echo htmlspecialchars($file['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($file['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                            <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No matches found for your criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="dashboard-default-content">
            <?php if (!empty($_GET['simple_search'])): ?>
                <div class="section-title">
                    <h2>Search Results for: "<?php echo htmlspecialchars($_GET['simple_search']); ?>"</h2>
                    <a href="userdashboard.php" style="color: var(--gold); font-size: 0.9rem;">Clear Search</a>
                </div>
                <div class="table-card">
                    <table class="isj-table">
                        <thead>
                            <tr><th>Title</th><th>Author</th><th>Date Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($results && $results->num_rows > 0): ?>
                                <?php while($file = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td><i class="fas fa-file-pdf pdf-red"></i> <strong><?php echo htmlspecialchars($file['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($file['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                            <a href="../uploads/<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px;">No files matching "<?php echo htmlspecialchars($_GET['simple_search']); ?>"</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="folder-grid">
                    <?php
                    $sql = "SELECT * FROM documents WHERE type = 'folder' AND parent_id IS NULL 
                            AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all') ORDER BY name ASC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $folder_id = $row['id'];
                            $folder_name = $row['name'];
                            // Count files and subfolders logic...
                            ?>
                            <div class="folder-card" onclick="window.location.href='view_folder.php?id=<?php echo $folder_id; ?>'">
                                <i class="fas fa-folder fa-2x" style="color: var(--dark-blue);"></i>
                                <div class="folder-info"><h4><?php echo htmlspecialchars($folder_name); ?></h4></div>
                            </div>
                    <?php } } else { echo "<p>No folders found.</p>"; } ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="plan-section" style="display: none;">
            <div class="section-title"><h2><i class="fas fa-book-reader" style="color: var(--gold);"></i> Organizational Plan</h2></div>
            <div class="tree-card">
                <?php
                if (!function_exists('renderRoleTree')) {
                    function renderRoleTree($conn, $user_role, $parent_id = NULL) {
                        $sql = "SELECT * FROM documents WHERE type = 'folder' 
                                AND (parent_id " . ($parent_id === NULL ? "IS NULL" : "= $parent_id") . ") 
                                AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all') ORDER BY name ASC";
                        $result = $conn->query($sql);
                        if ($result && $result->num_rows > 0) {
                            echo '<ul class="folder-tree" style="list-style: none; padding-left: 20px;">';
                            while ($folder = $result->fetch_assoc()) {
                                echo '<li><i class="fas fa-folder" style="color: #D4AF37;"></i> ' . htmlspecialchars($folder['name']);
                                renderRoleTree($conn, $user_role, $folder['id']);
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                    }
                }
                renderRoleTree($conn, $user_role);
                ?>
            </div>
        </div>
    </main>
</div>

<script>
function validateSearch() {
    const inputs = document.querySelectorAll('#searchForm .search-input');
    let hasEmptyField = false;
    inputs.forEach(input => {
        if (input.value.trim() === "") {
            hasEmptyField = true;
            input.style.borderColor = "red";
        } else {
            input.style.borderColor = "#ddd";
        }
    });
    if (hasEmptyField) { alert("Please fill all metadata fields for Advanced Search!"); return false; }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    

    const sections = {
        nouveautes: document.getElementById('nouveautes-section'),
        advanced: document.getElementById('advanced-search-section'),
        default: document.getElementById('dashboard-default-content'),
        plan: document.getElementById('plan-section'),
        within: document.getElementById('search-within-section') 
    };

    function showSection(id) {
        Object.keys(sections).forEach(key => {
            if (sections[key]) {
                sections[key].style.display = (key === id ? 'block' : 'none');
            }
        });
        
      
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
    }

    
    if (urlParams.has('perform_search')) {
        showSection('advanced');
        document.getElementById('btn-advanced-search').classList.add('active');
    } else if (urlParams.has('simple_search')) {
        showSection('default');
        document.getElementById('btn-all-files').classList.add('active');
    }

    document.getElementById('btn-nouveautes').addEventListener('click', function() {
        showSection('nouveautes');
        this.classList.add('active');
    });

    document.getElementById('btn-advanced-search').addEventListener('click', function() {
        showSection('advanced');
        this.classList.add('active');
    });

    document.getElementById('btn-plan').addEventListener('click', function() {
        showSection('plan');
        this.classList.add('active');
    });

    
    const btnWithin = document.getElementById('btn-search-within');
    if (btnWithin) {
        btnWithin.addEventListener('click', function() {
            showSection('within');
            this.classList.add('active');
        });
    }

  
    document.getElementById('btn-all-files').addEventListener('click', function() {
        showSection('default');
        this.classList.add('active');
    });
});
</script>
</body>
</html>