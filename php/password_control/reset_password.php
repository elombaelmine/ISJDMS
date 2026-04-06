<?php
session_start();
include("../database.php");

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_SESSION['reset_email'];
    $otp_input = $_POST['otp'];
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
    } else {
        // Verify OTP
        $check = $conn->prepare("SELECT id FROM registration WHERE email = ? AND otp_code = ?");
        $check->bind_param("ss", $email, $otp_input);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE registration SET password = ?, otp_code = NULL WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            
            if ($update->execute()) {
                unset($_SESSION['reset_email']);
                // --- FIXED PATH BELOW ---
                header("Location: ../login.php?msg=reset_success");
                exit();
            }
        } else {
            $msg = "Invalid recovery code.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Password | ISJ Docs</title>
    <link rel="stylesheet" href="../../css/reset_password.css">
</head>
<body>
    <div class="login-card"> 
        <h2>Set New Password</h2>
        <?php if($msg) echo "<p style='color:red;'>$msg</p>"; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="6-digit code" required>
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" class="login-btn">Update Password</button>
        </form>
    </div>
</body>
</html>