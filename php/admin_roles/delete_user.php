<?php
session_start();
// Use ../ to go back to the php folder for the database
include("../database.php"); 

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM registration WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Use ../ to go back to the dashboard
        header("Location: ../admindashboard.php?tab=users&msg=deleted");
    }
}
exit();