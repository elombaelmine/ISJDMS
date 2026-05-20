<?php
session_start();
include("database.php");

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error_message = "";

function getProfileImageUrl($user_id) {
    $profile_dir = dirname(__DIR__) . '/uploads/profiles';
    $profile_url = '../uploads/profiles';
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $file = $profile_dir . '/user_' . $user_id . '.' . $ext;
        if (file_exists($file)) {
            return $profile_url . '/user_' . $user_id . '.' . $ext;
        }
    }
    return '';
}

// 2. Fetch data from the 'registration' table
$res = $conn->query("SELECT fullname, username, email, phone_number, password FROM registration WHERE id = $user_id");
$user_data = $res->fetch_assoc();
$display_name = $user_data['fullname'] ?? $user_data['username'] ?? 'User';
$initial = substr(trim($display_name), 0, 1);
$profile_image = getProfileImageUrl($user_id);

// 3. Handle the POST logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $profile_file = $_FILES['profile_image'] ?? null;
    $has_profile_upload = $profile_file && $profile_file['error'] !== UPLOAD_ERR_NO_FILE;
    $profile_ext = "";

    // Verify current password using the hash from 'registration'
    if (password_verify($current_pass, $user_data['password'])) {

        if (!empty($new_pass) && $new_pass !== $confirm_pass) {
            $error_message = "New passwords do not match.";
        }

        if ($has_profile_upload && empty($error_message)) {
            if ($profile_file['error'] !== UPLOAD_ERR_OK) {
                $error_message = "Profile picture could not be uploaded. Please try again.";
            } elseif ($profile_file['size'] > 2 * 1024 * 1024) {
                $error_message = "Profile picture must be 2MB or smaller.";
            } else {
                $profile_ext = strtolower(pathinfo($profile_file['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $image_info = getimagesize($profile_file['tmp_name']);

                if (!in_array($profile_ext, $allowed_ext, true) || $image_info === false) {
                    $error_message = "Please upload a valid image file.";
                }
            }
        }

        if (empty($error_message)) {
            if ($has_profile_upload) {
                $profile_dir = dirname(__DIR__) . '/uploads/profiles';
                if (!is_dir($profile_dir)) {
                    mkdir($profile_dir, 0777, true);
                }

                foreach (glob($profile_dir . '/user_' . $user_id . '.*') ?: [] as $old_profile) {
                    unlink($old_profile);
                }

                $target_profile = $profile_dir . '/user_' . $user_id . '.' . $profile_ext;
                if (!move_uploaded_file($profile_file['tmp_name'], $target_profile)) {
                    $error_message = "Profile picture could not be saved.";
                }
            }
        }

        if (empty($error_message)) {
            // Update the 'registration' table
            $conn->query("UPDATE registration SET username = '$username', phone_number = '$phone' WHERE id = $user_id");
            $_SESSION['username'] = $username;

            // Update Password if a new one is provided
            if (!empty($new_pass)) {
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE registration SET password = '$hashed_pass' WHERE id = $user_id");
            }

            header("Location: userdashboard.php?status=updated");
            exit();
        }
    } else {
        $error_message = "Invalid current password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — ISJ</title>
    <link rel="stylesheet" href="../css/setting.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #888;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: #000;
        }
        .read-only-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
            color: #777;
        }
    </style>
</head>
<body class="settings-page profile-page">
    <div class="settings-container">
        <div class="section-title">
            <h2>Profile</h2>
            <p>Update your institutional identity, profile picture, and security credentials.</p>
        </div>

        <div class="settings-card">
            <form action="" method="POST" enctype="multipart/form-data">
                
                <?php if($error_message): ?>
                    <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </p>
                <?php endif; ?>

                <!-- <div class="profile-picture-panel">
                    <div class="profile-picture-preview">
                        ?php if ($profile_image): ?>
                            <img src="?php echo htmlspecialchars($profile_image); ?>" alt="Profile picture">
                        ?php else: ?>
                            ?php echo htmlspecialchars(strtoupper($initial)); ?>
                        ?php endif; ?>
                    </div>
                    <div class="profile-picture-copy">
                        <label for="profile_image"><i class="fas fa-camera"></i> Profile Picture</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/png, image/jpeg, image/gif, image/webp">
                        <small>PNG, JPG, GIF, or WEBP. Maximum size: 2MB.</small>
                    </div>
                </div> -->

                <div class="settings-grid">
                    <div class="settings-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                    </div>
                    <div class="settings-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" placeholder="+237...">
                    </div>
                </div>

                <div class="settings-group">
                    <label><i class="fas fa-envelope"></i> Email (Verified)</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="read-only-field" readonly>
                    <small style="color: #888;">Email is locked to maintain OTP verification integrity.</small>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">

                <div class="settings-group">
                    <label><i class="fas fa-lock"></i> Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" class="password-input" placeholder="Confirm identity to save changes" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="settings-group">
                        <label>New Password (optional)</label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" class="password-input" placeholder="New Password">
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>
                    <div class="settings-group">
                        <label>Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" class="password-input" placeholder="Confirm Password">
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <a href="userdashboard.php" class="btn-cancel" style="text-decoration: none; display: inline-block; text-align: center; line-height: 40px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.password-input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>
