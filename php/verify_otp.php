<?php
session_start();
include("database.php");

// 1. Redirect to login if no email is in session
if (!isset($_SESSION['pending_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['pending_email'];
$error = "";

// 2. Cleanup logic for users who realize the email is wrong
if (isset($_GET['action']) && $_GET['action'] == 'cleanup') {
    // Only delete if it's a fresh signup (optional check)
    $cleanup = $conn->prepare("DELETE FROM registration WHERE email = ? AND otp_code IS NOT NULL");
    $cleanup->bind_param("s", $email);
    $cleanup->execute();
    
    unset($_SESSION['pending_email']);
    header("Location: signup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = mysqli_real_escape_string($conn, $_POST['otp_code']);
    
    // Check if code matches
    $query = $conn->prepare("SELECT id FROM registration WHERE email = ? AND otp_code = ?");
    $query->bind_param("ss", $email, $user_otp);
    $query->execute();
    
    if ($query->get_result()->num_rows > 0) {
        // SUCCESS: Clear only the otp_code to "Open the Gate"
        $update = $conn->prepare("UPDATE registration SET otp_code = NULL WHERE email = ?");
        $update->bind_param("s", $email);
        $update->execute();
        
        unset($_SESSION['pending_email']);
        
        // Return to login. Now that otp_code is NULL, they will log in normally.
        header("Location: login.php?success=verified");
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity — ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/verify_otp.css"></link>
</head>
<body>

    <div class="verify-card">
        <div class="icon-box">
            <i class="fas fa-user-check"></i>
        </div>
        
        <h2>Verify Account</h2>
        <p>A code has been sent to:</p>
        <span class="email-highlight"><?php echo htmlspecialchars($email); ?></span>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="verify_otp.php" method="POST">
            <input type="text" name="otp_code" class="otp-input" 
                   placeholder="000000" maxlength="6" required autofocus autocomplete="off">
            
            <button type="submit" class="verify-btn">Verify Now</button>
        </form>

        <div class="timer-container">
            <span id="timer-text">Resend code in <span id="seconds" style="color:var(--gold); font-weight:bold;">60</span>s</span>
            <a href="verify_otp.php?action=cleanup" id="cleanup-link">Wrong Email? Restart Registration</a>
        </div>
    </div>

    <script>
        let timeLeft = 60;
        const secondsSpan = document.getElementById('seconds');
        const timerText = document.getElementById('timer-text');
        const cleanupLink = document.getElementById('cleanup-link');

        const countdown = setInterval(() => {
            timeLeft--;
            secondsSpan.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerText.style.display = 'none';
                cleanupLink.style.display = 'inline-block';
            }
        }, 1000);
    </script>

</body>
</html>