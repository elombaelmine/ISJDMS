<?php
session_start();
// Step up one level to reach the database connection
include("../database.php");

// Security: Only admins can toggle user access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 1. Check current status
    $result = $conn->query("SELECT status FROM registration WHERE id = $id");
    
    if ($row = $result->fetch_assoc()) {
        $currentStatus = strtolower($row['status']);
        
        // 2. Logic: If currently enabled, set to Disabled. Otherwise, set to Enabled.
        $newStatus = ($currentStatus == 'enabled') ? 'Disabled' : 'Enabled';
        
        // 3. Update the database
        $update = $conn->prepare("UPDATE registration SET status = ? WHERE id = ?");
        $update->bind_param("si", $newStatus, $id);
        
        if ($update->execute()) {
            // Success: Redirect back to the User Management tab
            header("Location: ../admindashboard.php?tab=users&msg=status_updated");
            exit();
        }
    }
}

// Fallback redirect if something fails
header("Location: ../admindashboard.php?tab=users");
exit();