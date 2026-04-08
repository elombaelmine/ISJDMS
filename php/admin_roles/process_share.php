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
    $admin_name = $_SESSION['fullname'];
    $admin_email = $_SESSION['email']; // Your Gmail address

    // Fetch file details
    $stmt = $conn->prepare("SELECT name, file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    // Use your specific modem IP so your colleague can connect
    $server_ip = '192.168.1.141'; 
    $link = "http://" . $server_ip . "/ISJDMS/" . $file['file_path'];
    // $link = "http://" . $_SERVER['HTTP_HOST'] . "/ISJDMS/" . $file['file_path'];

    $mail = new PHPMailer(true);

    try {
        // --- SMTP Settings ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'elmine0520@gmail.com'; // Your real Gmail
        $mail->Password   = 'vmqd vkuc aqer rrns';   // The 16-character code
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- Recipients ---
        $mail->setFrom('elmine0520@gmail.com', 'ISJ Doc System');
        $mail->addAddress($recipient); 

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = "Document Shared: " . $file['name'];
        $mail->Body    = "
            <h3>Hello,</h3>
            <p><strong>$admin_name</strong> has shared a document with you via the ISJ Management System.</p>
            <p><strong>Message:</strong> $user_msg</p>
            <p><a href='$link' style='padding: 10px 20px; background: #D4AF37; color: #061428; text-decoration: none; border-radius: 5px; font-weight: bold;'>View Document</a></p>
            <br>
            <p>Regards,<br>ISJ Team</p>";

        $mail->send();
        header("Location: ../admindashboard.php?tab=docs&status=success");
        exit();
        
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}