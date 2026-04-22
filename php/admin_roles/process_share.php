<?php
session_start();
include("../database.php");

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doc_id = $_POST['doc_id'];
    $recipient = $_POST['recipient_email'];
    $user_msg = $_POST['message'];
    
    // Use session data for the person sending the mail
    $sender_name = $_SESSION['fullname'] ?? 'User';
    $user_role = $_SESSION['role'] ?? ''; 

    // Fetch file details
    $stmt = $conn->prepare("SELECT name, file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    // Use your specific modem IP
    $server_ip = '192.168.137.160'; 
    $link = "http://" . $server_ip . "/ISJDMS/" . $file['file_path'];

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
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                <h3 style='color: #061428;'>Hello,</h3>
                <p><strong>$sender_name</strong> has shared a document with you via the ISJ Management System.</p>
                <p><strong>Message:</strong> $user_msg</p>
                <p style='margin-top: 20px;'>
                    <a href='$link' style='padding: 10px 20px; background: #D4AF37; color: #061428; text-decoration: none; border-radius: 5px; font-weight: bold;'>View Document</a>
                </p>
                <br>
                <p>Regards,<br>ISJ Team</p>
            </div>";

        $mail->send();

        // --- DYNAMIC REDIRECT BASED ON ROLE ---
        if ($user_role === 'admin') {
            header("Location: ../admindashboard.php?tab=docs&status=success");
        } else {
            header("Location: ../userdashboard.php?status=success");
        }
        exit();
        
    } catch (Exception $e) {
        // Redirect back with an error status if it fails
        if ($user_role === 'admin') {
            header("Location: ../admindashboard.php?tab=docs&status=error");
        } else {
            header("Location: ../userdashboard.php?status=error");
        }
        exit();
    }
}
?>