<?php
session_start();
include("database.php"); 

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Added phone_number to the SELECT statement
    $sql = "SELECT id, fullname, email, phone_number, username, password, role, status, otp_code FROM registration WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. Password Verification
        if (password_verify($password, $user['password'])) {
            
            // 3. Check if Account is Disabled
            if ($user['status'] === 'disabled') {
                $error_msg = "Your account is currently disabled. Please contact the Admin.";
            } 
            // 4. THE ADMIN GATE: Check for unverified OTP
            elseif (!empty($user['otp_code'])) {
                $_SESSION['pending_email'] = $user['email'];
                $otp = $user['otp_code']; // Get the code the Admin created
                $fullname = $user['fullname'];

                // --- START PHPMAILER LOGIC ---
                // Remove the ../ because the folder is right there!
                  require 'PHPMailer/Exception.php';
                  require 'PHPMailer/PHPMailer.php';
                  require 'PHPMailer/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'elmine0520@gmail.com'; 
                    $mail->Password   = 'vmqd vkuc aqer rrns'; 
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('elmine0520@gmail.com', 'ISJ Docs System');
                    $mail->addAddress($user['email'], $fullname);

                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Admin-Created Account';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                            <h2 style='color: #061428;'>Welcome to ISJ Docs</h2>
                            <p>Hello <strong>$fullname</strong>,</p>
                            <p>Your account has been created by the Admin. Please use the verification code below to log in:</p>
                            <div style='background: #f4f4f4; padding: 15px; font-size: 24px; font-weight: bold; color: #D4AF37; text-align: center;'>
                                $otp
                            </div>
                        </div>";

                    $mail->send();
                    header("Location: verify_otp.php");
                    exit();

                } catch (Exception $e) {
                    $error_msg = "Mail error: Account created but code could not be sent.";
                }
                exit();
            }
            else {
                // 5. Normal Login Flow - Added phone_number to Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone_number'] = $user['phone_number']; // <--- New Session Variable
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admindashboard.php");
                } else {
                    header("Location: userdashboard.php");
                }
                exit();
            }
        } else {
            $error_msg = "Incorrect password. Please try again.";
        }
    } else {
        $error_msg = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ISJ Docs</title>
    <link rel="stylesheet" href="../css/login.css?v=3.0">
</head>
<body>
    <div class="container">      
        <div class="left-panel">
            <div class="brand"> 
                <a href="../php/welcome.php" class="back-link">Back to Welcome Page</a>
            </div>
            <div class="illustration">
                <img src="../images/applogo.png" class="main-logo" alt="ISJ Logo">
            </div>
            <p class="description">Discover the document of your choice.</p>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Log in</h2>
                
                <?php if($error_msg): ?>
                    <p style="color: #e74c3c; font-size: 0.9rem; margin-bottom: 15px; font-weight: bold; text-align: center;">
                        <?php echo $error_msg; ?>
                    </p>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    
                    <button type="submit" class="login-btn">Log in</button>
                </form>
                
                <a href="#" class="forgot-pass">Forgotten password?</a>
                <p class="signup-link">Need an account? <a href="../php/signup.php">Sign up</a></p>
            </div>
        </div>
    </div>

    <div class="footer">
        <footer class="welcome-footer">
            <p class="textfoot">&copy; 2026 ISJ DOCUMENTATION SYSTEM — Developed for the ISJ Integration Project.</p>
        </footer>
    </div>
</body>
</html>