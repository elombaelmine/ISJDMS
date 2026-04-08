<?php
session_start();
include("../database.php");

// 1. Security Check: Block Students/Parents, Allow Admin/Teacher/Staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'staff'])) {
    header("Location: ../login.php");
    exit();
}

$current_role = $_SESSION['role'];
$message = "";

// 2. Fetch folders based on permissions (Admin sees all, Staff see authorized)
if ($current_role === 'admin') {
    $folder_query = "SELECT id, name FROM documents WHERE type = 'folder' ORDER BY name ASC";
} else {
    // Teachers/Staff only see folders they have permission to view in the Plan
    $folder_query = "SELECT id, name FROM documents 
                     WHERE type = 'folder' 
                     AND (FIND_IN_SET('$current_role', viewed_by) OR viewed_by = 'all')
                     ORDER BY name ASC";
}
$folders = $conn->query($folder_query);

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "NULL";
    
    // Role-Based Visibility Logic
    if ($current_role === 'admin') {
        // Admin manually selects who sees it
        if (isset($_POST['view_roles'])) {
            $roles_array = $_POST['view_roles'];
            $viewed_by_str = in_array('all', $roles_array) ? 'all' : implode(',', array_map(function($r) use ($conn){ return mysqli_real_escape_string($conn, $r); }, $roles_array));
        } else {
            $viewed_by_str = 'all';
        }
    } else {
        // Teachers/Staff uploads are visible to 'all' so students can access the lessons
        $viewed_by_str = 'all'; 
    }

    $author = mysqli_real_escape_string($conn, $_SESSION['fullname']);
    $target_dir = "../../uploads/";
    
    // Ensure directory exists
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION);
    $file_name = time() . "_" . str_replace(' ', '_', $name) . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "uploads/" . $file_name; 

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO documents (name, description, author, type, parent_id, file_path, viewed_by, created_at) 
                VALUES ('$name', '$description', '$author', 'file', $parent_id, '$db_path', '$viewed_by_str', NOW())";

        if ($conn->query($sql)) {
            // Redirect back to their respective dashboards
            $redirect = ($current_role === 'admin') ? "../admindashboard.php?tab=docs&success=1" : "../userdashboard.php?success=1";
            header("Location: $redirect");
            exit();
        } else {
            $message = "Database Error: " . $conn->error;
        }
    } else {
        $message = "Error: File could not be saved.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Document - ISJ Docs</title>
    <link rel="stylesheet" href="../../css/admindashboard.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .upload-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border-top: 5px solid #061428; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-upload { background: #061428; color: #D4AF37; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-upload:hover { background: #0a2245; transform: translateY(-2px); }
        .role-badge { display: inline-block; padding: 4px 10px; background: #eee; border-radius: 4px; font-size: 0.8rem; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="upload-container">
    <h2 style="color: #061428; margin-bottom: 5px;"><i class="fas fa-file-upload"></i> Upload Document</h2>
    <span class="role-badge">Logged in as: <?php echo ucfirst($current_role); ?></span>
    
    <?php if($message): ?>
        <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Document Title</label>
            <input type="text" name="name" required placeholder="e.g. Course Syllabus">
        </div>

        <div class="form-group">
            <label>Target Folder</label>
            <select name="parent_id" <?php echo ($current_role !== 'admin') ? 'required' : ''; ?>>
                <?php if($current_role === 'admin'): ?>
                    <option value="">-- Root Level --</option>
                <?php else: ?>
                    <option value="">-- Select Destination Folder --</option>
                <?php endif; ?>
                
                <?php while($f = $folders->fetch_assoc()): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endwhile; ?>
            </select>
            <?php if($current_role !== 'admin'): ?>
                <small style="color: #888;">Note: You can only upload to folders created by the Admin.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Select File (PDF, Docx, etc.)</label>
            <input type="file" name="document" required>
        </div>

        <div class="form-group">
            <label>Description (Optional)</label>
            <textarea name="description" rows="2"></textarea>
        </div>

        <?php if ($current_role === 'admin'): ?>
        <div class="form-group">
            <label>Who can view this? (Hold Ctrl/Cmd to multi-select)</label>
            <select name="view_roles[]" multiple required style="height: 100px;">
                <option value="all" selected>Everyone</option>
                <option value="teacher">Teachers</option>
                <option value="staff">Staff</option>
                <option value="student">Students</option>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-upload">Upload to System</button>
        
        <?php $back = ($current_role === 'admin') ? "../admindashboard.php?tab=docs" : "../userdashboard.php"; ?>
        <a href="<?php echo $back; ?>" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
    </form>
</div>

</body>
</html>