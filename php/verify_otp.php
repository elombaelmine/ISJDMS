<?php
session_start();
include("database.php");

if (!isset($_SESSION['pending_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['pending_email'];
$error = "";

if (isset($_GET['action']) && $_GET['action'] == 'cleanup') {
    $cleanup = $conn->prepare("DELETE FROM registration WHERE email = ? AND status = 'Pending' AND otp_code IS NOT NULL");
    $cleanup->bind_param("s", $email);
    $cleanup->execute();
    
    unset($_SESSION['pending_email']);
    header("Location: signup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = mysqli_real_escape_string($conn, $_POST['otp_code']);
    
    $query = $conn->prepare("SELECT id FROM registration WHERE email = ? AND otp_code = ?");
    $query->bind_param("ss", $email, $user_otp);
    $query->execute();
    
    if ($query->get_result()->num_rows > 0) {
        $update = $conn->prepare("UPDATE registration SET otp_code = NULL, status = 'Verified' WHERE email = ?");
        $update->bind_param("s", $email);
        $update->execute();
        
        unset($_SESSION['pending_email']);
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
    <link rel="stylesheet" href="../css/verify_otp.css">
    <style>
        .timer-container { 
            margin-top: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
            align-items: center;
        }
        #resend-link { 
            color: #D4AF37; 
            text-decoration: none; 
            font-weight: bold; 
            display: none; 
        }
        #resend-link:hover { text-decoration: underline; }
        
        /* Changed to display: none so it stays hidden during the countdown */
        #cleanup-link { 
            color: #e74c3c; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: bold;
            display: none; 
            animation: fadeIn 0.4s ease-in-out;
        }
        #cleanup-link:hover { text-decoration: underline; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(3px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
            <div class="error-msg" style="color: red; margin: 10px 0;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="verify_otp.php" method="POST">
            <input type="text" name="otp_code" class="otp-input" 
                   placeholder="000000" maxlength="6" required autofocus autocomplete="off"
                   style="text-align: center; font-size: 1.5rem; letter-spacing: 5px; padding: 10px; margin-bottom: 15px; width: 80%;">
            <br>
            <button type="submit" class="verify-btn">Verify Now</button>
        </form>

        <div class="timer-container">
            <span id="timer-text">Resend code in <span id="seconds" style="color:#D4AF37; font-weight:bold;">60</span>s</span>
            <a href="#" id="resend-link">Resend Verification Code</a>
            <a href="verify_otp.php?action=cleanup" id="cleanup-link"><i class="fas fa-undo"></i> Wrong Email? Restart Registration</a>
        </div>
    </div>

    <script>
        let timeLeft = 60;
        const secondsSpan = document.getElementById('seconds');
        const timerText = document.getElementById('timer-text');
        const resendLink = document.getElementById('resend-link');
        const cleanupLink = document.getElementById('cleanup-link');

        const countdown = setInterval(() => {
            timeLeft--;
            secondsSpan.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerText.style.display = 'none';
                
                // Both options display cleanly side-by-side or stacked now
                resendLink.style.display = 'inline-block';
                cleanupLink.style.display = 'inline-block';
            }
        }, 1000);
    </script>

</body>
</html>