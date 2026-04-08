<?php
session_start();
include("../database.php");

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Fetch folders for the dropdown
$folder_query = "SELECT id, name FROM documents WHERE type = 'folder' ORDER BY name ASC";
$folders = $conn->query($folder_query);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "NULL";
    
    // --- INTEGRATED MULTIPLE ROLES LOGIC HERE ---
    if (isset($_POST['viewed_by'])) {
        $roles_array = $_POST['viewed_by'];
        
        if (in_array('all', $roles_array)) {
            $viewed_by_str = 'all';
        } else {
            // sanitize each role in the array before imploding
            $sanitized_roles = array_map(function($role) use ($conn) {
                return mysqli_real_escape_string($conn, $role);
            }, $roles_array);
            $viewed_by_str = implode(',', $sanitized_roles);
        }
    } else {
        $viewed_by_str = 'all'; 
    }
    // --------------------------------------------

    $author = isset($_SESSION['fullname']) ? mysqli_real_escape_string($conn, $_SESSION['fullname']) : "Admin";

    // 3. File Handling Logic
    $target_dir = "../../uploads/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION);
    $clean_title = str_replace(' ', '_', $name);
    $file_name = time() . "_" . $clean_title . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "uploads/" . $file_name; 

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        // 4. Database Insertion (Using $viewed_by_str)
        $sql = "INSERT INTO documents (name, description, author, type, parent_id, file_path, viewed_by) 
                VALUES ('$name', '$description', '$author', 'file', $parent_id, '$db_path', '$viewed_by_str')";

        if ($conn->query($sql)) {
            header("Location: ../admindashboard.php?tab=docs&success=1");
            exit();
        } else {
            $message = "Database Error: " . $conn->error;
        }
    } else {
        $message = "Error: Could not move the file to the uploads folder.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Document - ISJ</title>
    <link rel="stylesheet" href="../../css/admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .upload-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border-top: 5px solid #061428; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-upload { background: #061428; color: #D4AF37; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-upload:hover { background: #0a2245; transform: translateY(-2px); }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="upload-container">
    <h2 style="color: #061428; margin-bottom: 25px;"><i class="fas fa-file-upload"></i> New Document</h2>
    
    <?php if($message): ?>
        <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Document Title</label>
            <input type="text" name="name" required placeholder="e.g. Semester 1 Timetable">
        </div>

        <div class="form-group">
            <label>Description (Optional)</label>
            <textarea name="description" rows="2" placeholder="Brief details..."></textarea>
        </div>

        <div class="form-group">
            <label>Select File</label>
            <input type="file" name="document" required>
        </div>

        <div class="form-group">
            <label>Target Folder</label>
            <select name="parent_id">
                <option value="">-- Root Level --</option>
                <?php while($f = $folders->fetch_assoc()): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo $f['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
    <label>Who can view this document? (Hold Ctrl/Cmd to select multiple)</label>
    <select name="viewed_by[]" class="form-control" multiple required>
        <option value="all">Everyone</option>
        <option value="teacher">Teachers</option>
        <option value="staff">Staff Members</option>
        <option value="student">Students</option>
        <option value="parent">Parents</option>
    </select>
    <small class="text-muted">If you select "Everyone", other choices will be ignored.</small>
</div>

        <button type="submit" class="btn-upload">Upload to System</button>
        <a href="../admindashboard.php?tab=docs" class="cancel-link">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>