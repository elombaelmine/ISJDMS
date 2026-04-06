<?php
session_start();
require_once 'phpGangsta/GoogleAuthenticator.php';
include("database.php");

$ga = new PHPGangsta_GoogleAuthenticator();
$error = "";

// 1. SECURITY CHECK: If they didn't come from login.php, send them back
if (!isset($_SESSION['temp_admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_code = $_POST['auth_code'];
    $admin_id = $_SESSION['temp_admin_id'];

    // 2. Fetch the Secret Key for this specific Admin from the DB
    $stmt = $conn->prepare("SELECT google_auth_secret FROM registration WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $secret = $row['google_auth_secret'];

    // 3. Verify the 6-digit code from their phone
    // '2' allows a small time window in case the phone clock is slightly off
    $checkResult = $ga->verifyCode($secret, $user_code, 2); 

    if ($checkResult) {
        // SUCCESS! Promote them to a full session
        $_SESSION['user_id'] = $admin_id;
        $_SESSION['role'] = 'admin';
        
        // Clean up the temporary ID
        unset($_SESSION['temp_admin_id']); 
        
        header("Location: admindashboard.php");
        exit();
    } else {
        $error = "Invalid code. Please check your Google Authenticator app.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Admin | ISJ Docs</title>
    <link rel="stylesheet" href="../css/login.css"> <style>
        .auth-container { text-align: center; margin-top: 100px; font-family: sans-serif; }
        .auth-box { display: inline-block; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-top: 5px solid #007bff; }
        input[type="text"] { font-size: 2rem; width: 200px; text-align: center; letter-spacing: 8px; margin: 20px 0; border: 2px solid #ddd; border-radius: 5px; }
        .btn-verify { background: #007bff; color: white; border: none; padding: 12px 30px; font-size: 1rem; cursor: pointer; border-radius: 5px; }
        .btn-verify:hover { background: #0056b3; }
        .error-msg { color: #d9534f; background: #f2dede; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body style="background: #f4f7f6;">
    <div class="auth-container">
        <div class="auth-box">
            <h2>🛡️ 2-Step Verification</h2>
            <p>Please enter the 6-digit code from your phone app.</p>
            
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="auth_code" placeholder="000000" maxlength="6" required autocomplete="off" autofocus>
                <br>
                <button type="submit" class="btn-verify">Verify & Login</button>
            </form>
            <p><a href="logout.php" style="color: #666; font-size: 0.9rem;">Cancel Login</a></p>
        </div>
    </div>
</body>
</html>