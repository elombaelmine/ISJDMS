<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>settings</title>
    <link rel="stylesheet" href="../css/setting.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <div class="settings-container">
    <div class="section-title">
        <h2>Account Settings</h2>
        <p>Update your institutional identity and security credentials.</p>
    </div>

    <div class="settings-card">
        <form action="update_profile.php" method="POST">
            <div class="settings-group">
                <label><i class="fas fa-user"></i> Change Username</label>
                <input type="text" name="username" placeholder="New Username" required>
            </div>

            <div class="settings-group">
                <label><i class="fas fa-lock"></i> Current Password</label>
                <input type="password" name="current_password" placeholder="••••••••" required>
            </div>

            <div class="settings-grid">
                <div class="settings-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="New Password">
                </div>
                <div class="settings-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm Password">
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>
    
</body>
</html>