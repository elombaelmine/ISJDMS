<?php
session_start();
include("../database.php");

// --- 1. PHPMailer Requirements ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure these paths match your folder structure exactly
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

// --- 2. Security Check ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'staff'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];
$message = "";

// --- 3. Fetch User & Admin Data ---
$user_res = $conn->query("SELECT email, fullname FROM registration WHERE id = $user_id");
$user_info = $user_res->fetch_assoc();
$sender_email = $user_info['email'];
$sender_name = $user_info['fullname'];

$admin_res = $conn->query("SELECT email FROM registration WHERE role = 'admin' LIMIT 1");
$admin_info = $admin_res->fetch_assoc();
$admin_email = $admin_info['email'] ?? 'admin@isj-is.org';

// --- 4. Handle AJAX Email Request via PHPMailer ---
if (isset($_POST['send_admin_request'])) {
    $request_note = mysqli_real_escape_string($conn, $_POST['note']);
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elmine0520@gmail.com'; // YOUR SMTP EMAIL
        $mail->Password   = 'vmqd vkuc aqer rrns';   // YOUR APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('elmine0520@gmail.com', 'ISJ Doc System');
        $mail->addAddress($admin_email); 
        $mail->addReplyTo($sender_email, $sender_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Folder Modification Request from $sender_name";
        $mail->Body    = "<h3>Modification Request</h3>
                          <p><strong>From:</strong> $sender_name ($current_role)</p>
                          <p><strong>Message:</strong><br>$request_note</p>";

        $mail->send();
        echo "success";
    } catch (Exception $e) {
        echo "error"; 
    }
    exit(); 
}

// --- 5. Fetch folders based on permissions ---
if ($current_role === 'admin') {
    $folder_query = "SELECT id, name FROM documents WHERE type = 'folder' ORDER BY name ASC";
} else {
    $folder_query = "SELECT id, name FROM documents 
                     WHERE type = 'folder' 
                     AND (FIND_IN_SET('$current_role', viewed_by) OR viewed_by = 'all')
                     ORDER BY name ASC";
}
$folders = $conn->query($folder_query);

// --- 6. Handle Document Upload ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['send_admin_request'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "NULL";

    $viewed_by_str = isset($_POST['view_roles']) ? 
        (in_array('all', $_POST['view_roles']) ? 'all' : implode(',', $_POST['view_roles'])) : 'all';

    $author = mysqli_real_escape_string($conn, $_SESSION['fullname']);
    $target_dir = "../../uploads/";
    
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION);
    $file_name = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "uploads/" . $file_name; 

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO documents (name, description, author, type, parent_id, file_path, viewed_by, created_at) 
                VALUES ('$name', '$description', '$author', 'file', $parent_id, '$db_path', '$viewed_by_str', NOW())";

        if ($conn->query($sql)) {
            $redirect = ($current_role === 'admin') ? "../admindashboard.php?tab=docs&success=1" : "../userdashboard.php?success=1";
            header("Location: $redirect");
            exit();
        } else { $message = "Database Error: " . $conn->error; }
    } else { $message = "Error: File could not be saved."; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Document - ISJ Docs</title>
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
        .role-badge { display: inline-block; padding: 4px 10px; background: #eee; border-radius: 4px; font-size: 0.8rem; margin-bottom: 15px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 10% auto; padding: 25px; border-radius: 10px; width: 400px; border-top: 4px solid #D4AF37; position: relative; }
        .close-modal { position: absolute; right: 15px; top: 10px; cursor: pointer; font-size: 24px; color: #888; }
    </style>
</head>
<body>

<div class="upload-container">
    <h2 style="color: #061428;"><i class="fas fa-file-upload"></i> Upload Document</h2>
    <span class="role-badge">Logged in as: <?php echo ucfirst($current_role); ?></span>

    <?php if($message): ?>
        <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Document Title</label>
            <input type="text" name="name" required placeholder="e.g. Financial Report">
        </div>

        <div class="form-group">
            <label>Target Folder</label>
            <select name="parent_id" id="folderSelect" required>
                <?php if($current_role === 'admin'): ?>
                    <option value="">-- Root Level --</option>
                <?php else: ?>
                    <option value="" disabled selected>-- Select Destination Folder --</option>
                <?php endif; ?>
                
                <?php while($f = $folders->fetch_assoc()): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endwhile; ?>
            </select>
            <?php if($current_role !== 'admin'): ?>
                <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                    Don't see the folder you need? 
                    <a href="javascript:void(0)" onclick="openRequestModal()" style="color: #061428; font-weight: bold; text-decoration: underline;">Request Admin to create it</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Who can view this? (Hold Ctrl to multi-select)</label>
            <select name="view_roles[]" multiple required style="height: 120px;">
                <option value="all" selected>Everyone</option>
                <option value="teacher">Teachers</option>
                <option value="staff">Staff</option>
                <option value="student">Students</option>
                <option value="parent">Parents</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select File</label>
            <input type="file" name="document" required>
        </div>

        <div class="form-group">
            <label>Description (Optional)</label>
            <textarea name="description" rows="2" placeholder="Brief details about the document..."></textarea>
        </div>

        <button type="submit" class="btn-upload">Upload to System</button>
        <a href="javascript:history.back()" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9rem;">Cancel</a>
    </form>
</div>

<div id="requestModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeRequestModal()">&times;</span>
        <h3 style="color: #061428; margin-bottom: 15px;"><i class="fas fa-tools"></i> Request Modification</h3>
        <p style="font-size: 0.85rem; color: #555; margin-bottom: 15px;">
            The Administrator manages the document hierarchy. Please specify the folder you need.
        </p>
        <form id="requestForm">
            <div class="form-group">
                <textarea id="requestNote" placeholder="e.g. Please create a 'Parent Notices' folder..." rows="4"></textarea>
            </div>
            <button type="button" class="btn-upload" onclick="submitRequest()">Send to Admin</button>
        </form>
    </div>
</div>

<script>
    function openRequestModal() { document.getElementById('requestModal').style.display = 'block'; }
    function closeRequestModal() { document.getElementById('requestModal').style.display = 'none'; }
    
    function submitRequest() {
        const note = document.getElementById('requestNote').value;
        if(note.trim() === "") {
            alert("Please enter a description for your request.");
            return;
        }

        const btn = document.querySelector("#requestForm button");
        btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Sending...";
        btn.disabled = true;

        const formData = new FormData();
        formData.append('send_admin_request', '1');
        formData.append('note', note);

        fetch('upload_doc.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if(data.trim() === "success") {
                alert("Your request has been emailed to the Administrator.");
                closeRequestModal();
            } else {
                alert("Email failed. Please verify PHPMailer SMTP settings.");
            }
            btn.innerHTML = "Send to Admin";
            btn.disabled = false;
        });
    }

    window.onclick = function(event) {
        let modal = document.getElementById('requestModal');
        if (event.target == modal) { closeRequestModal(); }
    }
</script>

</body>
</html>