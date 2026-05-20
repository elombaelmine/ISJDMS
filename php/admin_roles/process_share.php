<?php
session_start();
include("../database.php");

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $recipient = trim($_POST['recipient_email'] ?? '');
    $user_msg = htmlspecialchars($_POST['message'] ?? '');
    
    $sender_name = $_SESSION['fullname'] ?? 'User';
    $user_role = $_SESSION['role'] ?? ''; 

    // 1. Validate the email format before processing anything
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $redirect = ($user_role === 'admin') ? "../admindashboard.php?tab=docs&status=invalid_email" : "../userdashboard.php?status=invalid_email";
        header("Location: $redirect");
        exit();
    }

    // 2. SIMPLIFIED QUERY: Drops the role check blocker so anybody can share an accessible file
    $stmt = $conn->prepare("SELECT name, file_path FROM documents WHERE id = ? AND type = 'file'");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if (!$file) {
        $redirect = ($user_role === 'admin') ? "../admindashboard.php?tab=docs&status=not_found" : "../userdashboard.php?status=not_found";
        header("Location: $redirect");
        exit();
    }

    // 3. AUTOMATED ENVIRONMENT SWITCH (Works Locally AND Live across all networks)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST']; 

    // If you are testing locally on XAMPP, force the link to your live domain 
    // so external devices on cellular data / other networks can open it.
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $protocol = "https://";
        $host = "isjdms.kesug.com";
    }
    
    $link = $protocol . $host . "/" . $file['file_path'];

    $mail = new PHPMailer(true);

    try {
        // --- SMTP Settings ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elmine0520@gmail.com'; 
        $mail->Password   = 'vmqd vkuc aqer rrns'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Recipients ---
        $mail->setFrom('elmine0520@gmail.com', 'ISJ Doc System');
        $mail->addAddress($recipient); 

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = "Document Shared: " . $file['name'];
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; max-width: 600px; margin: 0 auto;'>
                <h3 style='color: #061428;'>Hello,</h3>
                <p><strong>$sender_name</strong> has shared a document with you via the ISJ Management System.</p>
                <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #D4AF37; margin: 15px 0;'>
                    <strong>Message:</strong><br>$user_msg
                </div>
                <p style='margin-top: 25px; text-align: center;'>
                    <a href='$link' target='_blank' style='padding: 12px 25px; background: #061428; color: #D4AF37; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Document</a>
                </p>
                <br>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 0.8rem; color: #777;'>Regards,<br>ISJ Team</p>
            </div>";

        $mail->send();

        $redirect = ($user_role === 'admin') ? "../admindashboard.php?tab=docs&status=success" : "../userdashboard.php?status=success";
        header("Location: $redirect");
        exit();
        
    } catch (Exception $e) {
        $redirect = ($user_role === 'admin') ? "../admindashboard.php?tab=docs&status=error" : "../userdashboard.php?status=error";
        header("Location: $redirect");
        exit();
    }
}
?>