<?php
session_start();
include("../database.php");

// --- 1. PHPMailer Requirements ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elmine0520@gmail.com'; 
        $mail->Password   = 'vmqd vkuc aqer rrns'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('elmine0520@gmail.com', 'ISJ Doc System');
        $mail->addAddress($admin_email); 
        $mail->addReplyTo($sender_email, $sender_name);

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

// --- 6. Handle Document Upload with Content Extraction ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['send_admin_request'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "NULL";

    $viewed_by_str = isset($_POST['view_roles']) ? 
        (in_array('all', $_POST['view_roles']) ? 'all' : implode(',', $_POST['view_roles'])) : 'all';

    $author = mysqli_real_escape_string($conn, $_SESSION['fullname']);
    $target_dir = "../../uploads/";
    
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = strtolower(pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION));
    $file_name = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "uploads/" . $file_name; 

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        
        $extracted_text = "";

        // --- EXTRACTION LOGIC ---
        if ($file_ext == 'txt') {
            $extracted_text = file_get_contents($target_file);
        } 
        elseif ($file_ext == 'docx') {
            $extracted_text = read_docx($target_file);
        }
        elseif ($file_ext == 'pdf') {
            try {
                // Pointing to: ISJDMS/php/libs/pdfparser/src/Smalot/PdfParser/
                $lib_path = realpath(dirname(__DIR__) . '/libs/pdfparser/src/Smalot/PdfParser/');

                if ($lib_path && is_dir($lib_path)) {
                    // This autoloader handles all the internal sub-classes automatically
                    spl_autoload_register(function ($class) use ($lib_path) {
                        $prefix = 'Smalot\\PdfParser\\';
                        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
                            $relative_class = substr($class, strlen($prefix));
                            $file = $lib_path . '/' . str_replace('\\', '/', $relative_class) . '.php';
                            if (file_exists($file)) {
                                require_once $file;
                            }
                        }
                    });

                    // Check if the main entry file exists
                    if (file_exists($lib_path . '/Parser.php')) {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($target_file);
                        $extracted_text = $pdf->getText();
                    } else {
                        $extracted_text = "PDF Error: Parser.php missing in " . $lib_path;
                    }
                } else {
                    $extracted_text = "PDF Error: Library directory not found.";
                }
            } catch (Exception $e) {
                $extracted_text = "PDF Extraction failed: " . $e->getMessage();
            }
        }
        
        $extracted_text = mysqli_real_escape_string($conn, $extracted_text);

        $sql = "INSERT INTO documents (name, description, author, type, parent_id, file_path, viewed_by, file_content, created_at) 
                VALUES ('$name', '$description', '$author', 'file', $parent_id, '$db_path', '$viewed_by_str', '$extracted_text', NOW())";

        if ($conn->query($sql)) {
            $redirect = ($current_role === 'admin') ? "../admindashboard.php?tab=docs&success=1" : "../userdashboard.php?success=1";
            header("Location: $redirect");
            exit();
        } else { $message = "Database Error: " . $conn->error; }
    } else { $message = "Error: File could not be saved."; }
}

// --- 7. Helper Function for Word Content ---
function read_docx($filename) {
    $striped_content = '';
    if(!$filename || !file_exists($filename)) return false;
    $zip = new ZipArchive;
    if ($zip->open($filename) === TRUE) {
        $xml_content = $zip->getFromName("word/document.xml");
        if($xml_content) {
            $content = str_replace(['</w:r></w:p></w:tc><w:tc>', '</w:r></w:p>'], [" ", "\r\n"], $xml_content);
            $striped_content = strip_tags($content);
        }
        $zip->close();
    }
    return $striped_content;
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
    </style>
</head>
<body>

<div class="upload-container">
    <h2 style="color: #061428;"><i class="fas fa-file-upload"></i> Upload & Index</h2>
    <span class="role-badge">Session: <?php echo ucfirst($current_role); ?></span>

    <?php if($message): ?>
        <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Document Title</label>
            <input type="text" name="name" required placeholder="e.g. Thesis Draft">
        </div>

        <div class="form-group">
            <label>Target Folder</label>
            <select name="parent_id" required>
                <?php if($current_role === 'admin'): ?>
                    <option value="">-- Root Level --</option>
                <?php else: ?>
                    <option value="" disabled selected>-- Select Folder --</option>
                <?php endif; ?>
                <?php while($f = $folders->fetch_assoc()): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Permissions (Viewable by:)</label>
            <select name="view_roles[]" multiple required style="height: 80px;">
                <option value="all" selected>Everyone</option>
                <option value="teacher">Teachers</option>
                <option value="staff">Staff</option>
                <option value="student">Students</option>
            </select>
        </div>

        <div class="form-group">
            <label>Select File (PDF, DOCX, TXT)</label>
            <input type="file" name="document" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2"></textarea>
        </div>

        <button type="submit" class="btn-upload">Start Upload & Search-Indexing</button>
        <a href="javascript:history.back()" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Back</a>
    </form>
</div>

</body>
</html>