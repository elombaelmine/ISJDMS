<?php
session_start();
include("../database.php");

// Only Admin can access this specific version
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../admindashboard.php?tab=docs");
    exit();
}

$doc_id = $_GET['id'];
$query = $conn->prepare("SELECT name FROM documents WHERE id = ?");
$query->bind_param("i", $doc_id);
$query->execute();
$doc = $query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Share Document - ISJ Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --isj-blue: #061428; --isj-gold: #D4AF37; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .share-box { background: var(--isj-blue); color: white; padding: 40px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); width: 100%; max-width: 400px; border-top: 4px solid var(--isj-gold); }
        h2 { color: var(--isj-gold); margin-top: 0; }
        .doc-name { background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #BDC3C7; }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 20px; border: none; border-radius: 6px; background: #1a2a44; color: white; border: 1px solid #2c3e50; box-sizing: border-box;}
        input:focus { outline: none; border-color: var(--isj-gold); }
        .btn-send { background: var(--isj-gold); color: var(--isj-blue); border: none; padding: 14px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        .btn-send:hover { background: #c5a02c; }
    </style>
</head>
<body>
    <div class="share-box">
        <h2><i class="fas fa-share-alt"></i> Share File</h2>
        <div class="doc-name">
            <i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($doc['name']); ?>
        </div>
        
        <form action="process_share.php" method="POST">
            <input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
            
            <label>Recipient Email</label>
            <input type="email" name="recipient_email" placeholder="student@example.com" required>

            <label>Message (Optional)</label>
            <textarea name="message" rows="3" placeholder="Please find the attached document."></textarea>

            <button type="submit" class="btn-send" id="sendBtn">
    <span id="btnText">Send via Email</span>
    <span id="loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
</button>

<script>
    // Prevent double clicking and show status
    document.querySelector('form').onsubmit = function() {
        document.getElementById('btnText').style.display = 'none';
        document.getElementById('loader').style.display = 'inline-block';
        document.getElementById('sendBtn').disabled = true;
        document.getElementById('sendBtn').style.opacity = '0.7';
    };
</script>
            <a href="../admindashboard.php?tab=docs" style="display: block; text-align: center; margin-top: 15px; color: #BDC3C7; text-decoration: none; font-size: 0.8rem;">Cancel</a>

        </form>
    </div>
</body>
</html>